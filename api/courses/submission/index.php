<?php
require_once '../../../initialize.php';

try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (!isset($_GET) && !key_exists("c_id", $_GET)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->join("submissions s", "s.p_id=p.p_id", "RIGHT");
            $db->where("c_id", $_GET["c_id"]);
            $submit = $db->get("posts p", null, "s.*, p.c_id, p.p_type");
            echo json_encode($submit);
            break;
        case 'PUT': //submit
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (!isset($_PUT) && !key_exists("p_id", $_PUT)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $data = array(
                "u_id" => $_SESSION["u_id"]
            );
            foreach(array_keys($_PUT) as $key) {
                $data[$key] = ($key == "s_content") ? json_encode($_PUT[$key]) : $_PUT[$key];
            }
            echo ($db->insert("submissions", $data)) ? jsonResponse(message: "บันทีกการส่งเรียบร้อย") : jsonResponse(400, "ไม่สามารถบันทีกการส่งได้") ;
            break;
        case "DELETE": // ลบ submits
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (!isset($_DELETE) && !key_exists("s_id", $_DELETE)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->where("s_id", $_DELETE['s_id']);
            $u_id = $db->getValue("submissions", "u_id");
            $db->where("s_id", $_DELETE['s_id']);
            echo ($u_id == $_SESSION["u_id"] && $db->delete("submissions")) ? jsonResponse(message: "ยกเลิกการส่งเรียบร้อย") : jsonResponse(400, "ไม่สามารถยกเลิกการส่งได้");
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
