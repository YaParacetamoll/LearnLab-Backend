<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if (isset($_POST) && key_exists("u_id", $_POST) && key_exists("c_id", $_POST)) {
                $db->where("c_id", $_POST["c_id"]);
                $hashed_password = $db->getValue("courses", "c_hashed_password");
                if ($hashed_password != null && !key_exists("c_password", $_POST)) {
                    echo jsonResponse(400, "Invalid input"); //Course ตั้งรหัสแต่ User ไม่ได้ใส่มา
                } else if ($hashed_password != null && key_exists("c_password", $_POST) && !password_verify($_POST["c_password"], $hashed_password)) {
                    echo jsonResponse(400, "The course's password incorrect");//Course ตั้งรหัสแต่ User ใส่ผิด
                } 
                else {
                    $db->where("u_id", $_POST);
                    $role = $db->getValue("users", "u_role");
                    $data = array(
                        "u_id" => $_POST["u_id"],
                        "c_id" => $_POST["c_id"],
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
