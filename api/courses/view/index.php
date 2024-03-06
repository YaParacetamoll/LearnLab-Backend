<?php
require_once '../../../initialize.php';

// มี option คือ QUIZ กับ ASSIGNMENT จะเอาข้อมูล quiz หรือ assignment ทุก item มาแสดงของใน course นั้นๆ 
try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET) && key_exists('option', $_GET) && key_exists('c_id', $_GET)) {
                $db->where("c_id", $_GET['c_id']);
                if (strtoupper($_GET['option']) == "QUIZ") {
                    $result = $db->get('quizzes');
                } else {
                    $result = $db->get('assignments');
                }
                foreach (array_values($result) as $i => $obj) {
                    if (key_exists("q_items", $result[$i])) {
                        $result[$i]["q_items"] = json_decode($result[$i]["q_items"]);
                    } else if (key_exists("a_files", $result[$i])){
                        $result[$i]["a_files"] = json_decode($result[$i]["a_files"]);
                    }
                }
                echo json_encode(
                    $result
                );
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
