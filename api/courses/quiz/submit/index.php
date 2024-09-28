<?php
require_once "../../../../initialize.php";

try {
    if (!isset($_SESSION["u_id"])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (
                !isset($_GET) &&
                !key_exists("c_id", $_GET) &&
                !key_exists("q_id", $_GET)
            ) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            if (key_exists("u_id", $_GET)) {
                $db->where("sa.q_id", $_GET["q_id"]);
                $db->where("sa.u_id", $_GET["u_id"]);
                $db->join("users u", "u.u_id=sa.u_id", "LEFT");
                $db->join("quizzes a", "a.q_id=sa.q_id", "LEFT");
                $submissions = $db->getOne(
                    "submissions_quiz sa",
                    "a.q_due_date, a.q_begin_date, a.q_score, a.q_name ,sa.* ,u_firstname, u_lastname, u_avatar_mime_type"
                );
                $submissions["s_content"] = json_decode(
                    $submissions["s_content"]
                );
                $submissions["u_avatar"] = !is_null(
                    $submissions["u_avatar_mime_type"]
                );
                unset($submissions["u_avatar_mime_type"]);
                echo json_encode(["data" => $submissions]);
            }
            $db->where("q_id", $_GET["q_id"]);
            $quiz_data = $db->getOne(
                "quizzes",
                "q_due_date, q_name, q_begin_date, q_items"
            );
            $quiz_data["q_items"] = json_decode($quiz_data["q_items"]);
            $quiz_data["full_score"] = count($quiz_data["q_items"]);
            $submissions = $db->rawQuery(
                "SELECT e.u_id, s.q_id, u.u_firstname, u.u_lastname, u.u_avatar_mime_type, s.score, s_datetime FROM enrollments e LEFT JOIN
            (SELECT e.u_id, q_id, s_datetime, s.score FROM enrollments e LEFT OUTER JOIN submissions_quiz s on e.u_id=s.u_id WHERE  e.c_id=?  AND e.u_role = 'STUDENT' AND q_id=?) AS s
            ON e.u_id=s.u_id LEFT JOIN users u ON u.u_id=e.u_id WHERE e.c_id=? AND e.u_role = 'STUDENT'",
                [$_GET["c_id"], $_GET["q_id"], $_GET["c_id"]]
            );
            foreach (array_values($submissions) as $i => $obj) {
                $submissions[$i]["u_avatar"] = !is_null(
                    $submissions[$i]["u_avatar_mime_type"]
                );
                unset($submissions[$i]["u_avatar_mime_type"]);
            }
            $quiz_data["data"] = $submissions;
            echo json_encode($quiz_data);
            break;
        case "PUT": //submit
            $_PUT = json_decode(file_get_contents("php://input"), true);
            if (
                !isset($_PUT) &&
                !key_exists("c_id", $_PUT) &&
                !key_exists("q_id", $_PUT) &&
                !key_exists("s_content", $_PUT)
            ) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $score = 0;
            $db->where("q_id", $_PUT["q_id"]);
            $q_items = json_decode($db->getValue("quizzes", "q_items"));
            for ($i = 0; $i < count($q_items); $i++) {
                $ans =
                    $q_items[$i]->type == "CHOICE"
                        ? $q_items[$i]->correct
                        : $q_items[$i]->correct;
                if (
                    isset($_PUT["s_content"][$i]) &&
                    $ans == $_PUT["s_content"][$i]
                ) {
                    $score++;
                }
            }
            $data = [
                "q_id" => $_PUT["q_id"],
                "u_id" => $_SESSION["u_id"],
                "c_id" => $_PUT["c_id"],
                "s_content" => json_encode($_PUT["s_content"]),
                "score" => $score,
            ];
            echo $db->insert("submissions_quiz", $data)
                ? json_encode([
                    "message" => "บันทีกการส่งแบบทดสอบเรียบร้อย",
                    "std_score" => $score,
                    "full_score" => count($q_items),
                ])
                : jsonResponse(400, "ไม่สามารถบันทึกการส่งแบบทดสอบได้");
            // echo json_encode(array("score" => $score));
            break;
        case "DELETE": // ลบ submits
            $_DELETE = json_decode(file_get_contents("php://input"), true);
            if (!isset($_DELETE) && !key_exists("q_id", $_DELETE)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            break;
        default:
            echo jsonResponse(405, "ไม่อนุญาตให้ใช้ Method นี้");
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
