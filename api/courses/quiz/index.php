<?php
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_SESSION['u_id']) && isset($_GET['c_id']) && isset($_GET['q_id'])) {
                $db->where("c_id", $_GET['c_id']);
                $db->where("q_id", $_GET['q_id']);
                $result = $db->getOne('quizzes');
                $result['q_items'] = json_decode($result['q_items']);

                $db->where("u_id", $_SESSION["u_id"]);
                $db->where("c_id", $_GET["c_id"]);
                $role = $db->getValue("enrollments", "u_role");
                if ($role == "STUDENT") {
                    foreach ($result['q_items'] as $question) {
                        unset($question->correct);
                    }
                }
                echo json_encode(
                    $result
                );
            } else if (isset($_SESSION['u_id']) && isset($_GET['c_id'])) {
                $db->where("c_id", $_GET['c_id']);
                $result = $db->get('quizzes', null, 'q_id, q_name, q_begin_date, q_due_date');
                echo json_encode(
                    $result
                );
            } else {
                echo jsonResponse(400, "คุณไม่มีสิทธิ์ในการสร้างแบบทดสอบ");
                die();
            }
            break;
        case 'PUT': //สร้าง quiz
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($_PUT) && key_exists('c_id', $_PUT) && key_exists('q_name', $_PUT) && key_exists('q_items', $_PUT)) {
                $db->where('c_id', $_PUT['c_id']);
                $db->where('u_id', intval($_SESSION['u_id']));
                $role = $db->getValue("enrollments", "u_role");
                if (strcmp($role, "INSTRUCTOR")) {
                    echo jsonResponse(400, "คุณไม่มีสิทธิ์ในการสร้างแบบทดสอบ");
                    die();
                }
                $keys = array("q_begin_date", "q_due_date");
                $data = array(
                    "c_id" => $_PUT["c_id"],
                    "q_name" => $_PUT["q_name"],
                    "q_items" => json_encode($_PUT["q_items"])
                );
                foreach ($keys as $key) {
                    if (key_exists($key, $_PUT)) {
                        $data[$key] = (!strcmp($key, "q_password")) ? password_hash($_PUT[$key], PASSWORD_DEFAULT) : $_PUT[$key];
                    }
                }
                $q_id = $db->insert('quizzes', $data);
                echo ($q_id) ? jsonResponse(message: "สร้าง quiz เรียบร้อย!") : jsonResponse(400, "ไม่สามารถสร้าง quiz ได้");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case "POST": // แก้ไข quiz
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            if (isset($JSON_DATA) && (key_exists("q_id", $JSON_DATA))) {
                $db->where('c_id', $JSON_DATA['c_id']);
                $db->where('u_id', intval($_SESSION['u_id']));
                $role = $db->getValue("enrollments", "u_role");
                if (strcmp($role, "INSTRUCTOR")) {
                    echo jsonResponse(400, "คุณไม่มีสิทธิ์ในการแก้ไขแบบทดสอบ");
                    die();
                }
                $data = array();
                foreach (array_keys($JSON_DATA) as $key) {
                    if ($key == "q_items") {
                        $data[$key] = json_encode($JSON_DATA[$key]);
                        continue;
                    }
                }
                $db->where("q_id", $JSON_DATA["q_id"]);
                echo ($db->update("quizzes", $data)) ? jsonResponse(message: "บันทึกการแก้ไขแบบทดสอบสำเร็จ") : jsonResponse(400, "ไม่สามารถบันทึกการแก้ไขแบบทดสอบได้");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case "DELETE": // ลบ quiz
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (isset($_DELETE) && key_exists("q_id", $_DELETE) && key_exists("c_id", $_DELETE)) {
                $db->where('c_id', $_DELETE['c_id']);
                $db->where('u_id', intval($_SESSION['u_id']));
                $role = $db->getValue("enrollments", "u_role");
                if (strcmp($role, "INSTRUCTOR")) {
                    echo jsonResponse(400, "คุณไม่มีสิทธิ์ในการลบแบบทดสอบ");
                    die();
                }
                $db->where('q_id', $_DELETE["q_id"]);
                echo ($db->delete("quizzes")) ? jsonResponse(message: "ลบแบบทดสอบเรียบร้อบ") : jsonResponse(400, "ไม่สามารถลบแบบทดสอบได้");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
