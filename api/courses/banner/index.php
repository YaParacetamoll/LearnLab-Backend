<?php
require_once "../../../vendor/autoload.php";
require_once "../../../initialize.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["c_id"])) {
                $db->where("c_id", intval($_GET["c_id"]));
                $banner = $db->getOne("courses", "c_banner_mime_type");

                if ($banner) { // Check if a banner record exists
                    $cmd = $s3client->getCommand('GetObject', [
                        'Bucket' => $s3bucket_banner,
                        'Key' => $s3_banner_folder . intval($_GET["c_id"])
                    ]);

                    $request = $s3client->createPresignedRequest($cmd, '+10 minute');
                    $presignedUrl = (string)$request->getUri();

                    header("Location: " . $presignedUrl);
                    exit();
                } else {
                    echo jsonResponse(404, "No banner found for this course.");
                }

            } else {
                echo jsonResponse(400, "Missing or invalid parameters.");
            }
            break;
        default:
            echo jsonResponse(405, "Method Not Allowed");
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage()); // Log this error for debugging!
}