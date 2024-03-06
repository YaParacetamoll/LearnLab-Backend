<?php
require_once '../../vendor/autoload.php';
require_once '../../initialize.php';
// TODO: https://stackoverflow.com/questions/8062496/how-to-change-max-allowed-packet-size


try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, "Unauthorized");
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (key_exists("c_id", $_GET) && key_exists("f_path", $_GET)) {

                $db->where('u_id', $_SESSION['u_id']);
                $db->where('c_id', intval($_GET['c_id']));
                $isAllow = $db->getOne('enrollments');

                if (is_null($isAllow)) {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                }

                $db->where('c_id', intval($_GET['c_id']));
                $db->where('f_path', $_GET['f_path']);
                $db->where('f_privacy', 'PUBLIC');
                $cols = array("f_id", "f_name", "u_id", "f_mime_type", "f_type", 'created_at', 'updated_at');
                $listing = $db->get('files', null, $cols);
                // echo json_encode(array("data" => $listing, "statement" => $db->getLastQuery()));
                echo json_encode($listing);
            } else {
                echo jsonResponse(400, "Invalid Input");
            }

            break;
        case 'POST':
            if (key_exists("c_id", $_POST) && key_exists("f_path", $_POST) && key_exists("f_type", $_POST) && key_exists("f_data", $_FILES)) {
                $db->where('u_id', $_SESSION['u_id']);
                $db->where('c_id', $_POST['c_id']);
                $isAllow = $db->getOne('enrollments');

                if (is_null($isAllow)) {
                    echo jsonResponse(403, "Unauthrized on this course");
                    die();
                }

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
                    $res = $db->insert('files', $data);
                }
                echo ($res) ? jsonResponse(200, "File Uploaded Successfully") : jsonResponse(500, "File Upload Error");
            } else if (key_exists("c_id", $_POST) && key_exists("f_path", $_POST) && key_exists("f_type", $_POST)) {
                echo jsonResponse(200, "Folder Created Successfully");
            } else {
                echo jsonResponse(400, "Invaild Parameters");
            }
            break;
        case 'DELETE':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (key_exists("f_id", $JSON_DATA)) {
                $db->where('f_id', $JSON_DATA['f_id']);
                if ($db->delete('files')) {
                    echo jsonResponse(200, "Deleted Successfully");
                } else {
                    echo jsonResponse(500, "Deletion Failure");
                }
            } else {
                echo jsonResponse(400, "Invaild Parameters");
            }
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
