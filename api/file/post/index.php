<?php
require_once '../../../initialize.php';
// TODO: https://stackoverflow.com/questions/8062496/how-to-change-max-allowed-packet-size


try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, "Unauthorized");
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (key_exists("f_id", $_GET)) {
                $JSON_DATA = json_decode($_GET['f_id']);
                $db->where('u_id', $_SESSION['u_id']);
                $db->where('c_id', intval($_GET['c_id']));
                $isAllow = $db->getOne('enrollments');

                if (is_null($isAllow)) {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                }

                $db->where('f_id', $JSON_DATA, 'IN');
                $db->orderBy('f_name', 'asc');
                $cols = array("f_id", "f_name", "f_mime_type");
                $listing = $db->get('files', null, $cols);
                echo json_encode($listing);
            } else if (key_exists("f_id", $_GET)) {
                $db->where('f_id', intval($_GET["f_id"]));
                $file = $db->getOne('files', 'f_data, f_name, f_mime_type');
                if ($file && !is_null($file['f_data'])) {
                    header('Content-Disposition: filename="' . $file["f_name"] . '"');
                    header('Content-type: ' . $file['f_mime_type']);
                    echo $file['f_data'];
                } else {
                    echo jsonResponse(404, "No file here");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }

            break;
        case 'POST':
            if (key_exists("c_id", $_POST) && key_exists("f_data", $_FILES)) {
                $db->where('u_id', $_SESSION['u_id']);
                $db->where('c_id', $_POST['c_id']);
                $isAllow = $db->getOne('enrollments');
                $upload_folder_hash = substr(str_shuffle(MD5(microtime())), 0, 8);
                if (is_null($isAllow)) {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                }

                if ($isAllow['u_role'] == 'STUDENT') {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                }
                $db_fs_path = '/posts/' . $upload_folder_hash . '/';
                $data = array(
                    "u_id" => $_SESSION['u_id'],
                    "c_id" => intval($_POST['c_id']),
                    "f_name" => $upload_folder_hash,
                    "f_path" => '/posts/',
                    "f_privacy" => "PRIVATE",
                    "f_type" => 'FOLDER'
                );
                $res = $db->insert('files', $data);

                $num_file = count($_FILES["f_data"]['name']);
                $file_id = array();
                $insertError = array();
                for ($j = 0; $j < count($_FILES["f_data"]['name']); $j++) {
                    if ($_FILES["f_data"]['error'][$j] == UPLOAD_ERR_OK) {
                        $name = $_FILES["f_data"]['name'][$j];
                        $temp = $_FILES["f_data"]['tmp_name'][$j];
                        $mime_type = mime_content_type($temp);
                        $blob = file_get_contents($temp);
                        $data = array(
                            "u_id" => $_SESSION['u_id'],
                            "c_id" => intval($_POST['c_id']),
                            "f_name" => $name,
                            "f_path" => $db_fs_path,
                            "f_data" => $blob,
                            "f_privacy" => "PRIVATE",
                            "f_mime_type" => $mime_type,
                            "f_type" => 'FILE'
                        );
                        $f_id = $db->insert('files', $data);
                        if ($f_id) {
                            array_push($file_id, $f_id);
                        } else {
                            array_push($insertError, $db->getLastError());
                        }
                    } else {
                        array_push($insertError, $_FILES["f_data"]['error'][$j]);
                    }
                }
                if (count($insertError) > 0) {
                    http_response_code(500);
                    echo json_encode(array(
                        "error" => $insertError,
                        "f_id" => $file_id,
                        "message" => "อัพโหลดไฟล์ด้วยข้อผิดพลาด"
                    ));
                } else {
                    echo json_encode(array(
                        "f_id" => $file_id,
                        "message" => "อัพโหลดไฟล์สำเร็จ"
                    ));
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");;
            }
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
