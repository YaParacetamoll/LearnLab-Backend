<?php
require_once '../../vendor/autoload.php';
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_SESSION['u_id'])) {
                $db->where("u_id", intval($_SESSION['u_id']));
                $user = $db->getOne("users");
                unset($user['u_hashed_password']);
                if (!is_null($user['u_avatar'])) {
                    $user['u_avatar'] =  base64_encode($user['u_avatar']);
                } 
                echo json_encode(array(
                    "status" => http_response_code(),
                    "data" => $user
                ));
            } else {
                echo jsonResponse(403, "Unauthorized");
            }
            break;
        case 'PUT':
            if (file_get_contents('php://input') == null) {
                echo jsonResponse(400, "Invalid input");
            } else {
                parse_str(file_get_contents('php://input'), $_PUT);
                if (key_exists("u_firstname", $_PUT) && key_exists("u_lastname", $_PUT) && key_exists("u_tel", $_PUT) && key_exists("u_email", $_PUT) && key_exists("u_password", $_PUT) && key_exists("u_gender", $_PUT) && key_exists("u_role", $_PUT)) {
                    $hash_password = password_hash($_PUT["u_password"], PASSWORD_DEFAULT);
                    $data = array(
                        "u_firstname" => $_PUT["u_firstname"],
                        "u_lastname" => $_PUT["u_lastname"],
                        "u_tel" => $_PUT["u_tel"],
                        "u_email" => $_PUT["u_email"],
                        "u_hashed_password" => $hash_password,
                        "u_gender" => $_PUT["u_gender"],
                        "u_role" => $_PUT["u_role"]
                    );
                    $id = $db->insert('users', $data);
                    echo ($id) ? jsonResponse(message: 'User was created successfully! Id = ' . $id) : jsonResponse(400, "Fail to create user.");
                } else {
                    jsonResponse(400, "Invalid input");
                }
            }
            break;
        case 'POST':
            if (isset($_POST) && key_exists("u_email", $_POST) && key_exists("u_password", $_POST)) {
                $db->where("u_email", $_POST['u_email']);
                $user = $db->getOne("users");
                echo ($db->count > 0 && password_verify($_POST['u_password'], $user['u_hashed_password'])) ? jsonResponse(message: "Authentication success") : jsonResponse(400, "Authentication failed");
                if (!is_null($user)) {
                    $_SESSION['u_id'] = intval($user['u_id']);
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
