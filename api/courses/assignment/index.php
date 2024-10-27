<?php
require_once "../../../initialize.php";

try {
    if (!isset($_SESSION["u_id"])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (!isset($_GET["c_id"])) {
                throw new Exception("ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง", 400);
            }
            if (isset($_GET["a_id"])) {
                $db->where("a.c_id", intval($_GET["c_id"]));
                $db->where("a.a_id", $_GET["a_id"]);
                $db->join(
                    "submissions_assignment s",
                    "s.a_id=a.a_id AND s.u_id=" . $_SESSION["u_id"],
                    "LEFT"
                );
                $assignment = $db->getOne("assignments a");
                $assignment["a_files"] = json_decode($assignment["a_files"]);
                $assignment["s_content"] = json_decode(
                    $assignment["s_content"]
                );
                if (count($assignment["a_files"]) > 0) {
                    $db->where("f_id", $assignment["a_files"], "IN");
                    $db->orderBy("f_name", "asc");
                    $cols = ["f_id", "f_name", "f_mime_type"];
                    $assignment["a_files"] = $db->get("files", null, $cols);
                }
                if (
                    isset($assignment["s_content"]) &&
                    count($assignment["s_content"]->files) > 0
                ) {
                    $db->where("f_id", $assignment["s_content"]->files, "IN");
                    $db->orderBy("f_name", "asc");
                    $cols = ["f_id", "f_name", "f_mime_type"];
                    $assignment["s_content"]->files = $db->get(
                        "files",
                        null,
                        $cols
                    );
                } elseif (!isset($assignment["s_content"])) {
                    $assignment["s_content"] = json_decode("{}");
                }
                echo json_encode($assignment);
            } else {
                $db->join(
                    "submissions_assignment s",
                    "s.a_id=a.a_id AND s.u_id=" . $_SESSION["u_id"],
                    "LEFT"
                );
                $db->where("a.c_id", intval($_GET["c_id"]));
                $assignment = $db->get(
                    "assignments a",
                    null,
                    "a.a_id, a.c_id, a.a_name, a.a_due_date, a.a_score, s.s_datetime"
                );
                echo json_encode($assignment);
            }
            break;
        case "PUT": //create assignment
            $_PUT = json_decode(file_get_contents("php://input"), true);
            if (!key_exists("c_id", $_PUT) || !key_exists("a_name", $_PUT)) {
                throw new Exception("ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง", 400);
            }

            $data = [];
            foreach (array_keys($_PUT) as $key) {
                $data[$key] =
                    $key == "a_files" ? json_encode($_PUT[$key]) : $_PUT[$key];
            }
            echo $db->insert("assignments", $data)
                ? jsonResponse(message: "มอบหมายงานภายในคอร์สเรียบร้อย")
                : jsonResponse(400, "ไม่สามารถมอบหมายงานได้");
            break;
        case "PATCH": //edit assignment
            $JSON = json_decode(file_get_contents("php://input"), true);

            if (!isset($_SESSION["u_id"])) {
                throw new Exception("Unauthenticated", 403);
            }
            if (!isset($JSON) && !key_exists("a_id", $JSON)) {
                throw new Exception("ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง", 400);
            }

            $data = [];
            foreach (array_keys($JSON) as $key) {
                $data[$key] =
                    $key == "a_files" ? json_encode($JSON[$key]) : $JSON[$key];
            }
            $db->where("a_id", $JSON["a_id"]);
            echo $db->update("assignments", $data)
                ? jsonResponse(message: "แก้ไขงานที่มอบหมายเรียบร้อย")
                : jsonResponse(400, "ไม่สามารถแก้ไขได้");
            break;
        case "DELETE": //delete assignment
            $_DELETE = json_decode(file_get_contents("php://input"), true);
            if (isset($_DELETE) && key_exists("a_id", $_DELETE)) {
                $db->where("a_id", $_DELETE["a_id"]);
                $p_files = json_decode($db->getValue("assignments", "a_files"));
                if (count($p_files) > 0) {
                    $db->where("f_id", $p_files, "IN");
                    if (!$db->delete("files")) {
                        throw new Exception("ไม่สามารถลบไฟล์ในโพสต์ได้", 400);
                        break;
                    }
                }

                $db->where("a_id", intval($_DELETE["a_id"]));
                $sub_files = $db->get(
                    "submissions_assignment",
                    null,
                    "s_content"
                );
                $sub_file_list = [];
                foreach ($sub_files as $files) {
                    $data = json_decode($files["s_content"]);
                    $sub_file_list = array_merge($sub_file_list, $data->files);
                }
                if (count($sub_file_list) > 0) {
                    $db->where("f_id", $sub_file_list, "IN");
                    if (!$db->delete("files")) {
                        echo jsonResponse(
                            400,
                            "ไม่สามารถลบไฟล์ใน Assignment ได้"
                        );
                        break;
                    }
                }

                $db->where("a_id", intval($_DELETE["a_id"]));
                if (!$db->delete("submissions_assignment")) {
                    echo jsonResponse(400, "ไม่สามารถลบ Assignment");
                    break;
                }

                $db->where("a_id", intval($_DELETE["a_id"]));
                echo $db->delete("assignments")
                    ? jsonResponse(message: "ลบงานที่มอบหมายเรียบร้อบ")
                    : jsonResponse(400, "ไม่สามารถลบงานที่มอบหมายได้");
            } else {
                throw new Exception("ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง", 400);
            }
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
