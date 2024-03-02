<?php
require_once '../../vendor/autoload.php';
require_once '../../initialize.php';
// TODO: https://stackoverflow.com/questions/8062496/how-to-change-max-allowed-packet-size


try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, $e->getMessage());
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':

            break;
        case 'POST':
            if (key_exists("c_id", $_POST) && key_exists("f_path", $_POST) && key_exists("f_type", $_POST) && key_exists("f_data", $_FILES)) {
                if ($_FILES["f_data"]["error"] > 0) {
                    echo jsonResponse(400, "The uploaded file contains error");
                    die();
                } else if (is_uploaded_file($_FILES["f_data"]["tmp_name"])) {
                    $mime_type = mime_content_type($_FILES['f_data']['tmp_name']);
                    $blob = file_get_contents($_FILES['f_data']['tmp_name']);
                    $data = array(
                        "u_id" => $_SESSION['u_id'],
                        "c_id" => intval($_POST['c_id']),
                        "f_name" => $_FILES['f_data']['name'],
                        "f_path" => $_POST['f_path'],
                        "f_data" => $blob,
                        "f_mime_type" => $mime_type,
                        "f_type" => 'FILE'
                    );
                    $db->insert('files', $data);
                }
                echo jsonResponse(200, "File Uploaded Successfully");
            } else if (key_exists("c_id", $_POST) && key_exists("f_path", $_POST) && key_exists("f_type", $_POST)) {
                echo jsonResponse(200, "Folder Created Successfully");
            } else {
                echo jsonResponse(400, "Invaild Parameters");
            }
            break;
        case 'DELETE':
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
