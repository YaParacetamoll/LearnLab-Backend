<?php
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['u_id'])) {
                // if requested with 'image' query it will return image base64 encoded blob with mime type
                $db->where("u_id", intval($_GET['u_id']));
                $user = $db->getOne("users", 'u_avatar, u_avatar_mime_type');
                if ($user && !is_null($user['u_avatar'])) {
                    header('Content-type: ' . $user['u_avatar_mime_type']);
                    echo $user['u_avatar'];
                } else {
                    echo jsonResponse(404, "No image here");
                }
            } else {
                echo jsonResponse(403, "กรุณาระบุ User ID ด้วย");
            }
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
