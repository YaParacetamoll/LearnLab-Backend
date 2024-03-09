<?php
require_once '../../../initialize.php';

try {
    if (!isset($_SESSION['u_id'])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER['REQUEST_METHOD']) {
        case "GET":
            if (!isset($_GET['c_id'])) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            if (isset($_GET['a_id'])) {
                $db->where('c_id', intval($_GET['c_id']));
                $db->where('a_id', $_GET['a_id']);
                $assignment = $db->getOne('assignments');
                $assignment['a_files'] = json_decode($assignment['a_files']);
                if (count($assignment['a_files']) > 0) {
                    $db->where('f_id', $assignment['a_files'], 'IN');
                    $db->orderBy('f_name', 'asc');
                    $cols = array("f_id", "f_name", "f_mime_type");                           
                    $assignment['a_files'] = $db->get('files', null, $cols);
                }
                echo ($assignment) ? json_encode($assignment) : jsonResponse(500, $db->getLastError());
            } else {
                $db->where('c_id', intval($_GET['c_id']));
                $assignment = $db->get('assignments');
                echo ($assignment) ? json_encode($assignment) : jsonResponse(500, $db->getLastError());
            }
            
            break;
        case "PUT": //create assignment
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (!isset($_PUT) && !key_exists("c_id", $_PUT) && !key_exists("a_name", $_PUT)); {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $data = array();
            foreach (array_keys($_PUT) as $key) {
                $data[$key] = ($key == "a_files") ? json_encode($_PUT[$key]) : $_PUT[$key];
            }
            echo ($db->insert("assignments", $data)) ? jsonResponse(message: "มอบหมายงานภายในคอร์สเรียบร้อย") : jsonResponse(400, "ไม่สามารถมอบหมายงานได้");
            break;
        case "POST": //edit assignment
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $JSON = json_decode(file_get_contents('php://input'), true);
            if (!isset($JSON) && !key_exists("a_id", $JSON)); {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $data = array();
            foreach (array_keys($_PUT) as $key) {
                $data[$key] = ($key == "a_files") ? json_encode($_PUT[$key]) : $_PUT[$key];
            }
            $db->where("a_id", $JSON['a_id']);
            echo ($db->update("assignments", $data)) ? jsonResponse(message: "แก้ไขงานที่มอบหมายเรียบร้อย") : jsonResponse(400, "ไม่สามารถแก้ไขได้");
            break;
        case "DELETE": //delete assignment
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (isset($_DELETE) && key_exists("a_id", $_DELETE)) {
                $db->where('a_id', $_DELETE("a_id"));
                echo ($db->delete("assignments")) ? jsonResponse(message: "ลบงานที่มอบหมายเรียบร้อบ") : jsonResponse(400, "ไม่สามารถลบงานที่มอบหมายได้");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
