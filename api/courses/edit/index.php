<?php
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST': //multipart form
            if (!isset($_SESSION['u_id'])) {
                jsonResponse(403, "Unauthenticated");
                die();
            }
            if (!isset($_POST) && count($_POST) == 0 && key_exists("c_id", $_POST)) {
                jsonResponse(400, "Invalid input");
                die();
            }
            $db->where("c_id", $_POST['c_id']);
            $db->where("u_id", intval($_SESSION['u_id']));
            $role = $db->getValue("enrollments", "u_role");
            if (strcmp($role, "INSTRUCTOR")) {
                echo jsonResponse(400, "คุณไม่มีสิทธิ์ที่จะแก้ไขคอร์สเรียน");
                die();
            }
            $data = array();
            foreach (array_keys($_POST) as $key) {
                $data[$key] = $_POST[$key];
            }
            if (isset($_FILES["c_banner"]) && $_FILES["c_banner"]["error"] == 0) {
                $image = $_FILES["c_banner"]["tmp_name"];
                $imgContent = file_get_contents($image);
                $mime_type = mime_content_type($image);
                if (!strcmp(explode("/", $mime_type)[0], "image")) {
                    $data["c_banner"] = $imgContent;
                    $data["c_banner_mime_type"] = $mime_type;
                }
            }
            $db->where("c_id", $_POST['c_id']);
            echo ($db->update("courses", $data)) ? jsonResponse(message: "แก้ไขข้อมูลคอร์สเรียนเรียบร้อย") : jsonResponse(400, "การแก้ไขคอร์สล้มเหลว");
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
