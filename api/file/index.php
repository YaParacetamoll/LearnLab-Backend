<?php
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
                $db->join('users u', "u.u_id=f.u_id", "LEFT");
                $db->where('c_id', intval($_GET['c_id']));
                $db->where('f_path', $_GET['f_path']);
                if ($isAllow['u_role'] == "STUDENT") {
                    $db->where('f_privacy', 'PUBLIC');
                }
                $db->orderBy('f_type', 'desc');
                $db->orderBy('f_name', 'asc');
                $cols = array("f.f_id", "f.f_name", "f.u_id", "u.u_firstname", "u.u_lastname", "f.f_mime_type", "f.f_privacy", "f.f_type", 'f.created_at', 'f.updated_at');
                $listing = $db->get('files f', null, $cols);
                echo json_encode($listing);
            } else if (key_exists("f_id", $_GET)) {
                $db->where('f_id', intval($_GET["f_id"]));
                $file = $db->getOne('files', 'f_data, f_name, f_mime_type, f_path, c_id, f_ident_key');
                if ($file && !is_null($file['f_data'])) {
                    header('Content-Disposition: filename="' . $file["f_name"] . '"');
                    header('Content-type: ' . $file['f_mime_type'], true);
                    echo $file['f_data'];
                    exit();
                } else if ($file && is_null($file['f_data'])) {
                    try {
                        header('Content-Disposition: filename="' . $file["f_name"] . '"', true, 200);
                        header('Content-type: ' . $file['f_mime_type'], true, 200);
                        // TODO : Use Cloud Front Later
                        $s3Obj = $s3client->getObject([
                            'Bucket' => $s3bucket, // ชื่อBucket
                            'Key' =>  key_exists("f_path", $file) ? $file["c_id"] . $file["f_path"] . $file["f_ident_key"] . $file["f_name"] : $file["c_id"] . '/' . $file["f_ident_key"] . $file["f_name"], // ชื่อไฟล์ ,
                        ]);
                        $res = $s3Obj->get('Body');
                        $res->rewind();
                        echo $res;
                        exit();
                    } catch (Exception $e) {
                        header('Content-Type: application/json; charset=utf-8', true);
                        echo jsonResponse(404, "No file here");
                    }
                } else {
                    echo jsonResponse(404, "No file here");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }

            break;
        case 'POST':
            if (key_exists("c_id", $_POST) && key_exists("f_path", $_POST) && key_exists("f_type", $_POST) && key_exists("f_data", $_FILES)) {
                $db->where('u_id', $_SESSION['u_id']);
                $db->where('c_id', $_POST['c_id']);
                $isAllow = $db->getOne('enrollments');
                if (is_null($isAllow)) {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                }

                if ($_FILES["f_data"]["error"] > 0) {
                    echo jsonResponse(400, "The uploaded file contains error");
                    die();
                } else if (is_uploaded_file($_FILES["f_data"]["tmp_name"])) {
                    $mime_type = mime_content_type($_FILES['f_data']['tmp_name']);
                    $blob = file_get_contents($_FILES['f_data']['tmp_name']);
                    $ident_key = uniqid(date("Y-m-d-H-i-s-"));
                    try {
                        $s3client->putObject(
                            [
                                'Bucket' => $s3bucket,
                                'Key' =>  key_exists("f_path", $_POST) ? $_POST['c_id'] . $_POST['f_path'] . $ident_key . $_FILES['f_data']['name'] : $_POST['c_id'] . $ident_key . $_FILES['f_data']['name'],
                                'Body' => $blob,
                            ]
                        );
                    } catch (Exception $e) {
                        die($e);
                    }
                    $data = array(
                        "u_id" => $_SESSION['u_id'],
                        "c_id" => intval($_POST['c_id']),
                        "f_name" => $_FILES['f_data']['name'],
                        "f_path" => $_POST['f_path'],
                        "f_data" => null,
                        "f_mime_type" => $mime_type,
                        "f_type" => 'FILE',
                        "f_ident_key" => $ident_key
                    );
                    $res = $db->insert('files', $data);
                }
                echo ($res) ? jsonResponse(200, "อัพโหลดไฟล์ " . $_FILES['f_data']['name'] . " สำเร็จ") : jsonResponse(500, "อัพโหลดไฟล์ล้มเหลว");
            } else if (key_exists("c_id", $_POST) && key_exists("f_path", $_POST) && key_exists("f_type", $_POST) && key_exists("f_name", $_POST)) {
                $data = array(
                    "u_id" => $_SESSION['u_id'],
                    "c_id" => intval($_POST['c_id']),
                    "f_name" => $_POST['f_name'],
                    "f_path" => $_POST['f_path'],
                    "f_type" => 'FOLDER'
                );
                $res = $db->insert('files', $data);
                echo ($res) ? jsonResponse(200, "สร้างโฟลเดอร์ " . $_POST['f_name'] . " สำเร็จ") : jsonResponse(500, "สร้างโฟลเดอร์ล้มเหลว");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");;
            }
            break;
        case 'DELETE':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (key_exists("f_id", $JSON_DATA) && key_exists("c_id", $JSON_DATA) && key_exists("f_type", $JSON_DATA)) {

                $db->where('u_id', $_SESSION['u_id']);
                $db->where('c_id', $JSON_DATA['c_id']);
                $isAllow = $db->getOne('enrollments');

                if (is_null($isAllow)) {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                } else if ($isAllow['u_role'] === 'STUDENT') {
                    echo jsonResponse(403, "Unauthorized on this course");
                    die();
                }
                
                $db->where('f_id', $JSON_DATA['f_id']);
                $folder_path = $db->getOne('files', 'f_name ,f_path,f_data,f_ident_key');
                if ($JSON_DATA['f_type'] === 'FILE') $db->where('f_id', $JSON_DATA['f_id']); // Delete Single File
                else if ($JSON_DATA['f_type'] === 'FOLDER') { // Delete Folder and All Files inside it

                    if ($folder_path) {
                        $db->where('f_path', $folder_path['f_path'] . $folder_path['f_name'] . '/%', 'LIKE');
                        $db->orWhere('f_id', $JSON_DATA['f_id']);
                    }
                }
                if ($db->delete('files')) {
                    if (is_null($folder_path["f_data"]))
                        try {
                            $s3client->deleteObject(
                                [
                                    'Bucket' => $s3bucket,
                                    'Key' => $JSON_DATA['c_id'] . $folder_path['f_path'] . $folder_path['f_ident_key'] . $folder_path['f_name'],
                                ]
                            );
                        } catch (Exception $e) {
                            echo jsonResponse(500, "ลบล้มเหลว");
                            die();
                        }
                    echo jsonResponse(200, "ลบสำเร็จ");
                } else {
                    echo jsonResponse(500, "ลบล้มเหลว");
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
