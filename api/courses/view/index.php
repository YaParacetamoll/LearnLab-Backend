<?php
require_once '../../../initialize.php';

// มี option คือ QUIZ กับ ASSIGNMENT จะเอาข้อมูล quiz หรือ assignment ทุก item มาแสดงของใน course นั้นๆ 
try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (isset($JSON_DATA) && key_exists('option', $JSON_DATA) && key_exists('c_id', $JSON_DATA)) {
                $db->where("c_id", $JSON_DATA['c_id']);
                $result = (!strcmp(strtoupper($JSON_DATA['option']), "QUIZ")) ? $db->get('quizzes') : $db->get('assignments');
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
