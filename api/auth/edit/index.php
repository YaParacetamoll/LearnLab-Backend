<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if (isset($_SESSION['u_id']) && count($_POST) > 0) {
                $keys = ["u_firstname", "u_lastname", "u_tel"];
                $data = array();
                foreach ($keys as $key) {
                    if (key_exists($key, $_POST)) {
                        $data[$key] = $_POST[$key];
                    }
                }
                if (isset($_FILES["u_avatar"]) && $_FILES["u_avatar"]["error"] == 0) {
                    $image = $_FILES["u_avatar"]["tmp_name"];
                    $imgContent = file_get_contents($image);
                    $mime_type = mime_content_type($image);
                    if (!strcmp(explode("/", $mime_type)[0], "image")) {
                        $data["u_avatar"] = $imgContent;
                        $data["u_avatar_mime_type"] = $mime_type;
                    }
                }
                $db->where("u_id", intval($_SESSION['u_id']));
                echo ($db->update('users', $data)) ? jsonResponse(message: "User profile's edited successfully") : jsonResponse(400, "Fail to edit user profile.");
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
