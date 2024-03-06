<?php
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_SESSION['u_id'])) {
                // if requested with 'image' query it will return image base64 encoded blob with mime type
                $db->where("u_id", intval($_SESSION['u_id']));
                if (!isset($_GET['image'])) $user = $db->getOne("users", 'u_id, u_firstname, u_lastname, u_tel, u_email, u_gender, u_role, u_created_at, u_updated_on');
                else if (isset($_GET['image'])) $user = $db->getOne("users", 'u_avatar, u_avatar_mime_type');
                if (isset($user['u_avatar']) && !is_null($user['u_avatar'])) {
                    $user['u_avatar'] =  base64_encode($user['u_avatar']);
                }
                echo json_encode(
                    $user
                );
            } else {
                echo jsonResponse(403, "Unauthorized");
            }
            break;
        case 'POST':
            if (isset($_POST) && key_exists("u_firstname", $_POST) && key_exists("u_lastname", $_POST) && key_exists("u_tel", $_POST) && key_exists("u_email", $_POST) && key_exists("u_password", $_POST) && key_exists("u_gender", $_POST) && key_exists("u_role", $_POST)) {
                $hash_password = password_hash($_POST["u_password"], PASSWORD_DEFAULT);
                $data = array(
                    "u_firstname" => $_POST["u_firstname"],
                    "u_lastname" => $_POST["u_lastname"],
                    "u_tel" => $_POST["u_tel"],
                    "u_email" => $_POST["u_email"],
                    "u_hashed_password" => $hash_password,
                    "u_gender" => $_POST["u_gender"],
                    "u_role" => $_POST["u_role"]
                );
                if (isset($_FILES["u_avatar"]) && $_FILES["u_avatar"]["error"] == 0) {
                    $image = $_FILES["u_avatar"]["tmp_name"];
                    $imgContent = file_get_contents($image);
                    $mime_type = mime_content_type($image);
                    if (!strcmp(explode("/", $mime_type)[0], "image")) {
                        $data["u_avatar"] = $imgContent;
                        $data["u_avatar_mime_type"] = $mime_type;
                    }
                }
                $id = $db->insert('users', $data);
                if ($id) {
                    echo jsonResponse(message: 'User was created successfully! Id = ' . $id);
                } else {
                    echo jsonResponse(400, "Fail to create user.");
                }
            } else {
                jsonResponse(400, "Invalid input");
            }
            break;
        case 'PUT':
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (isset($_PUT) && key_exists("u_email", $_PUT) && key_exists("u_password", $_PUT)) {
                $db->where("u_email", $_PUT['u_email']);
                $user = $db->getOne("users");
                echo ($db->count > 0 && password_verify($_PUT['u_password'], $user['u_hashed_password'])) ? jsonResponse(message: "Authentication success") : jsonResponse(400, "Authentication failed");
                if (!is_null($user)) {
                    $_SESSION['u_id'] = intval($user['u_id']);
                    $_SESSION['u_role'] = $user['u_role'];
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
