<?php
require_once "../../../vendor/autoload.php";
require_once "../../../initialize.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["c_id"])) {
                $db->where("c_id", intval($_GET["c_id"]));
                $banner = $db->getOne(
                    "courses",
                    "c_banner_mime_type"
                );
                $s3Obj = $s3client->getObject([
                    "Bucket" => $s3bucket_banner, // ชื่อBucket
                    "Key" => $s3_banner_folder.intval($_GET["c_id"])
                ]);
                $res = $s3Obj->get("Body");
                $res->rewind();
                $res;
                if (!is_null($res)) {
                    header("Content-type: " . $banner["c_banner_mime_type"]);
                    echo $res;//$banner["c_banner"];
                    exit();
                } else {
                    echo jsonResponse(404, "No image here");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        default:
            echo jsonResponse(405, "ไม่อนุญาตให้ใช้ Method นี้");
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
