<?php
require_once '../../../../initialize.php';

try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (!isset($_GET) && !key_exists("c_id", $_GET) && key_exists("a_id", $_GET)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            if (key_exists("u_id", $_GET)) {
                $db->where('a_id', $_GET['a_id']);
                $db->where('u_id', $_GET['u_id']);
                $submissions = $db->getOne("submissions_assignment");
                $submissions["s_content"] = json_decode($submissions["s_content"]);
                echo json_encode(array("data" => $submissions));
            } else {
                $col = array(
                    "a_id", "u.u_firstname", "u.u_lastname", "s_datetime"
                );
                $submissions = $db->rawQuery("SELECT e.u_id, s.a_id, u.u_firstname, u.u_lastname, u.u_avatar_mime_type, s_datetime FROM enrollments e LEFT JOIN
            (SELECT e.u_id, a_id, s_datetime FROM enrollments e LEFT OUTER JOIN submissions_assignment s on e.u_id=s.u_id WHERE  e.c_id=?  AND e.u_role = 'STUDENT' AND a_id=?) AS s
            ON e.u_id=s.u_id LEFT JOIN users u ON u.u_id=e.u_id WHERE e.c_id=? AND e.u_role = 'STUDENT'", array($_GET['c_id'], $_GET['a_id'], $_GET['c_id']));

                foreach (array_values($submissions) as $i => $obj) {
                    $submissions[$i]['u_avatar'] = !is_null($submissions[$i]['u_avatar_mime_type']);
                    unset($submissions[$i]['u_avatar_mime_type']);
                }

                echo json_encode(array("data" => $submissions));
            }
            break;
        case 'PUT': //submit
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (!isset($_PUT) && !key_exists("a_id", $_PUT) && !key_exists("c_id", $_PUT)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $data = array(
                "u_id" => $_SESSION["u_id"]
            );
            foreach (array_keys($_PUT) as $key) {
                $data[$key] = ($key == "s_content") ? json_encode($_PUT[$key]) : $_PUT[$key];
            }
            echo ($db->insert("submissions_assignment", $data)) ? jsonResponse(message: "บันทีกการส่งเรียบร้อย") : jsonResponse(400, "ไม่สามารถบันทีกการส่งได้");
            break;
        case "DELETE": // ลบ submits
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (!isset($_DELETE) && !key_exists("a_id", $_DELETE)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->where("a_id", $_DELETE['a_id']);
            $db->where("u_id", $_DELETE['u_id']);
            echo ($db->delete("submissions_assignment")) ? jsonResponse(message: "ยกเลิกการส่งเรียบร้อย") : jsonResponse(400, "ไม่สามารถยกเลิกการส่งได้");
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
