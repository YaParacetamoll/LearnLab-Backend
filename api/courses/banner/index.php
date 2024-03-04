<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET["c_id"])) {
                $db->where('c_id', intval($_GET["c_id"]));
                $banner = $db->getOne('courses', 'c_banner, c_banner_mime_type');
                if (!is_null($banner['c_banner'])) {
                    header('Content-type: ' . $banner['c_banner_mime_type']);
                    $banner['c_banner'] = $banner['c_banner'];
                    echo $banner['c_banner'];
                } else {
                    echo jsonResponse(404, "No image here");
                }
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
