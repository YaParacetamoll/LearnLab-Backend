<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if (isset($_POST) && key_exists("u_id", $_POST) && key_exists("c_id", $_POST)) {
                $db->where("c_id", $_POST["c_id"]);
                $hashed_password = $db->getValue("courses", "c_hashed_password");
                if ($hashed_password != null && key_exists("c_password", $_POST) && !password_verify($_POST["c_password"], $hashed_password)) {
                    http_response_code(400);
                    echo json_encode(array(
                        "status" => http_response_code(),
                        "message" => "Incorrect Password"
                    ));
                } else {
                    $db->where("u_id", $_POST);
                    $role = $db->getValue("users", "u_role");
                    $data = array(
                        "u_id" => $_POST["u_id"],
                        "c_id" => $_POST["c_id"],
                        "u_role" => $role
                    );
                    $u_id = $db->insert('enrollments', $data);
                    if ($u_id) {
                        echo json_encode(array(
                            "status" => http_response_code(),
                            "message" => "Enrollment success"
                        ));
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            "status" => http_response_code(),
                            "message" => "Enrollment failed"
                        ));
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "status" => http_response_code(),
                    "message" => "Invalid Input"
                ));
            }
            break;
        default:
            echo json_encode(array(
                "status" => http_response_code(),
                "message" => ""
            ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => http_response_code(),
        "message" => $e->getMessage()
    ));
}
