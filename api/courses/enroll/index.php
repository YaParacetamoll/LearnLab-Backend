<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($JSON_DATA) && key_exists("c_id", $JSON_DATA)) {
                $db->where("c_id", $JSON_DATA["c_id"]);
                $hashed_password = $db->getValue("courses", "c_hashed_password");
                if ($hashed_password != null && !key_exists("c_password", $JSON_DATA)) {
                    echo jsonResponse(400, "กรุณาใส่รหัส"); //Course ตั้งรหัสแต่ User ไม่ได้ใส่มา
                } else if ($hashed_password != null && key_exists("c_password", $JSON_DATA) && !password_verify($JSON_DATA["c_password"], $hashed_password)) {
                    echo jsonResponse(400, "รหัสคอร์สผิด กรุณาลองใหม่"); //Course ตั้งรหัสแต่ User ใส่ผิด
                } else {
                    $db->where("u_id", $_SESSION['u_id']);
                    $role = $db->getValue("users", "u_role");
                    $data = array(
                        "u_id" => $_SESSION['u_id'],
                        "c_id" => $JSON_DATA["c_id"],
                        "u_role" => $role
                    );
                    $u_id = $db->insert('enrollments', $data);
                    echo ($u_id) ? jsonResponse(message: "เข้าร่วมคอร์สสำเร็จ") : jsonResponse(400, "เข้าร่วมคอร์สล้มเหลว");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case 'DELETE':
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($_DELETE) && key_exists("c_id", $_DELETE)) {
                $db->where("u_id", $_SESSION['u_id']);
                $db->where("c_id", $_DELETE['c_id']);
                echo ($db->delete("enrollments")) ? jsonResponse(message: "User unenroll the course successfully") : jsonResponse(400, "Fail to unenroll");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    $error = explode(" ", $e->getMessage());
    if ($error[0] === 'Duplicate' && $error[1] === 'entry') {
        echo jsonResponse(500, "คุณเป็นสมาชิกของคอร๋สนี้อยู่แล้ว");
    } else {
        echo jsonResponse(500, $e->getMessage());
    }
}
