<?php
require_once "../../initialize.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["u_id"])) {
                // if requested with 'image' query it will return image base64 encoded blob with mime type
                $db->where("u_id", intval($_GET["u_id"]));
                $user = $db->getOne("users", "u_avatar, u_avatar_mime_type");
                if ($user) {
                    //header("Content-type: " . $user["u_avatar_mime_type"]);
                    try {
                        $presignedUrl = getS3PreSignedUrl(
                            $s3client,
                            $s3bucket_avatar,
                            $s3_avatar_folder.intval($_GET["u_id"])
                        );
                        header("Location: " . $presignedUrl, true);
                        exit();
                    } catch (Exception $exception) {
                        echo jsonResponse(500, "can not create presigned url");
                        die();
                    }
                    exit();
                } else {
                    echo jsonResponse(404, "No image here");
                }
            } else {
                echo jsonResponse(403, "กรุณาระบุ User ID ด้วย");
            }
            break;
        default:
            echo jsonResponse(405, "ไม่อนุญาตให้ใช้ Method นี้");
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
