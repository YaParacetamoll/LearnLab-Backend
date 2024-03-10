<?php
require_once '../../../../initialize.php';

try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'PUT':
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (!isset($_PUT) && !key_exists("u_email", $_PUT) && !key_exists("c_id", $_PUT)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->where("u_email", $_PUT['u_email']);
            $course = $db->getOne("users", "u_id, u_role");
            if ($course) {
                $enroll_data = array(
                    "c_id" => $_PUT['c_id'],
                    "u_id" => $course["u_id"],
                    "u_role" => $course['u_role'] == 'INSTRUCTOR' ? "INSTRUCTOR" : "STUDENT"
                );
                if ($db->insert('enrollments', $enroll_data)) {
                    echo jsonResponse(200, "เพิ่มผู้ใช้สำเร็จ");
                }
            } else {
                echo jsonResponse(404, "ไม่พบผู้ใช้");
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
