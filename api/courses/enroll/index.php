<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (isset($JSON_DATA) && key_exists("u_id", $JSON_DATA) && key_exists("c_id", $JSON_DATA)) {
                $db->where("c_id", $JSON_DATA["c_id"]);
                $hashed_password = $db->getValue("courses", "c_hashed_password");
                if ($hashed_password != null && !key_exists("c_password", $JSON_DATA)) {
                    echo jsonResponse(400, "Invalid input"); //Course ตั้งรหัสแต่ User ไม่ได้ใส่มา
                } else if ($hashed_password != null && key_exists("c_password", $JSON_DATA) && !password_verify($JSON_DATA["c_password"], $hashed_password)) {
                    echo jsonResponse(400, "The course's password incorrect");//Course ตั้งรหัสแต่ User ใส่ผิด
                } 
                else {
                    $db->where("u_id", $JSON_DATA['u_id']);
                    $role = $db->getValue("users", "u_role");
                    $data = array(
                        "u_id" => $JSON_DATA["u_id"],
                        "c_id" => $JSON_DATA["c_id"],
                        "u_role" => $role
                    );
                    $u_id = $db->insert('enrollments', $data);
                    echo ($u_id) ? jsonResponse(message: "Enrollment success") : jsonResponse(400, "Enrollment failed") ;
                }
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
