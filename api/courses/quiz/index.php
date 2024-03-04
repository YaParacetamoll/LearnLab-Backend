<?php
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'PUT':
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (isset($_PUT) && key_exists('q_id', $_PUT) && key_exists('c_id', $_PUT) && key_exists('q_name', $_PUT) && key_exists('q_items', $_PUT)) {
                $keys = array("q_begin_date", "q_due_date", "q_time_limit", "q_password");
                $data = array(
                    "q_id" => $_PUT["q_id"],
                    "c_id" => $_PUT["c_id"],
                    "q_name" => $_PUT["q_name"],
                    "q_items" => $_PUT["q_items"]
                );
                foreach ($keys as $key) {
                    if (key_exists($key, $_PUT)) {
                        $data[$key] = (!strcmp($key, "q_password")) ? password_hash($_PUT[$key], PASSWORD_DEFAULT) : $_PUT[$key];
                    }
                }
                $q_id = $db->insert('quizzes', $data);
                echo ($q_id) ? jsonResponse(message: "สร้าง quiz เรียบร้อย!") : jsonResponse(400, "ไม่สามารถสร้าง quiz ได้");
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
