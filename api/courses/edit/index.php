<?php
require_once "../../../initialize.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST": //multipart form
            if (!isset($JWT_SESSION_DATA["u_id"])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            if (
                !isset($_POST) ||
                count($_POST) == 0 ||
                !key_exists("c_id", $_POST)
            ) {
                echo jsonResponse(400, "Invalid input");
                die();
            }
            $db->where("c_id", $_POST["c_id"]);
            $db->where("u_id", intval($JWT_SESSION_DATA["u_id"]));
            $role = $db->getValue("enrollments", "u_role");
            if (strcmp($role, "INSTRUCTOR")) {
                echo jsonResponse(400, "คุณไม่มีสิทธิ์ที่จะแก้ไขคอร์สเรียน");
                die();
            }
            $data = [];
            foreach (array_keys($_POST) as $key) {
                if ($key == "c_password") {
                    $data["c_hashed_password"] = password_hash(
                        $_POST[$key],
                        PASSWORD_DEFAULT
                    );
                    continue;
                }
                if ($key == "c_remove_password") {
                    $data["c_hashed_password"] = null;
                    continue;
                }
                $data[$key] = $_POST[$key];
            }
            if (
                isset($_FILES["c_banner"]) &&
                $_FILES["c_banner"]["error"] == 0

            ) {
                $image = $_FILES["c_banner"]["tmp_name"];

                $webpImage = convertToWebP($image, 80, 'Banner');

                // $imgContent = file_get_contents($image);
                $mime_type = mime_content_type($image);
                if (!strcmp(explode("/", $mime_type)[0], "image")) {
                    //$data["c_banner"] = $imgContent;
                    $s3client->putObject([
                        "Bucket" => $s3bucket_banner,
                        "Key" => $s3_banner_folder.intval($_POST["c_id"]),
                        "Body" => $webpImage,
                        "ContentType" => "image/webp",
                    ]);
                    $data["c_banner_mime_type"] = "image/webp";
                }
            }
            $db->where("c_id", $_POST["c_id"]);
            echo $db->update("courses", $data)
                ? jsonResponse(message: "แก้ไขข้อมูลคอร์สเรียนเรียบร้อย")
                : jsonResponse(400, "การแก้ไขคอร์สล้มเหลว");
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
