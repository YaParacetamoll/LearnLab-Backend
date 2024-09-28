<?php
require_once "../../../initialize.php";

try {
    if (!isset($_SESSION["u_id"])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (!isset($_GET) && !key_exists("c_id", $_GET)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->join("users u", "u.u_id=e.u_id", "LEFT");
            if (isset($_GET["u_role"])) {
                $db->where("e.u_role", $_GET["u_role"]);
            }
            $db->where("c_id", $_GET["c_id"]);
            $db->orderBy("e.u_role", "desc");
            $db->orderBy("u.u_firstname", "asc");
            $db->orderBy("u.u_lastname", "asc");
            $enrollment = $db->get(
                "enrollments e",
                null,
                "e.*, u_avatar_mime_type ,u.u_firstname, u.u_lastname, u.u_email"
            );
            foreach (array_values($enrollment) as $i => $obj) {
                $enrollment[$i]["u_avatar"] = !is_null(
                    $enrollment[$i]["u_avatar_mime_type"]
                );
                unset($enrollment[$i]["u_avatar_mime_type"]);
            }
            echo $enrollment
                ? json_encode($enrollment)
                : jsonResponse(400, "ไม่มีสมาชิกในคอร์สนี้");
            break;
        case "PUT":
            $_PUT = json_decode(file_get_contents("php://input"), true);
            if (!isset($_PUT) && !key_exists("c_code", $_PUT)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->where("c_code", $_PUT["c_code"]);
            $course = $db->getOne(
                "courses",
                "c_id, c_name, c_description, c_hashed_password"
            );
            if ($course) {
                $db->where("c_id", $course["c_id"]);
                $db->where("u_id", $_SESSION["u_id"]);
                $check = $db->getOne("enrollments", "c_id");
                if ($check) {
                    echo jsonResponse(400, "คุณได้ลงทะเบียนคอร์สนี้แล้ว");
                    die();
                }
                echo json_encode($course);
            } else {
                echo jsonResponse(400, "ไม่พบคอร์ส");
            }
            break;
        case "POST":
            $JSON_DATA = json_decode(file_get_contents("php://input"), true);
            if (
                isset($_SESSION["u_id"]) &&
                isset($JSON_DATA) &&
                key_exists("c_id", $JSON_DATA)
            ) {
                $db->where("c_id", $JSON_DATA["c_id"]);
                $hashed_password = $db->getValue(
                    "courses",
                    "c_hashed_password"
                );
                if (
                    $hashed_password != null &&
                    !key_exists("c_password", $JSON_DATA)
                ) {
                    echo jsonResponse(400, "กรุณาใส่รหัส"); //Course ตั้งรหัสแต่ User ไม่ได้ใส่มา
                } elseif (
                    $hashed_password != null &&
                    key_exists("c_password", $JSON_DATA) &&
                    !password_verify($JSON_DATA["c_password"], $hashed_password)
                ) {
                    echo jsonResponse(400, "รหัสคอร์สผิด กรุณาลองใหม่"); //Course ตั้งรหัสแต่ User ใส่ผิด
                } else {
                    $db->where("u_id", $_SESSION["u_id"]);
                    $role = $db->getValue("users", "u_role");
                    $data = [
                        "u_id" => $_SESSION["u_id"],
                        "c_id" => $JSON_DATA["c_id"],
                        "u_role" => $role,
                    ];
                    $u_id = $db->insert("enrollments", $data);
                    echo $u_id
                        ? jsonResponse(message: "เข้าร่วมคอร์สสำเร็จ")
                        : jsonResponse(400, "เข้าร่วมคอร์สล้มเหลว");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case "PATCH":
            $JSON_DATA = json_decode(file_get_contents("php://input"), true);
            if (
                isset($JSON_DATA) &&
                key_exists("u_id", $JSON_DATA) &&
                key_exists("c_id", $JSON_DATA) &&
                key_exists("u_role", $JSON_DATA)
            ) {
                $db->where("u_id", $JSON_DATA["u_id"]);
                $db->where("c_id", $JSON_DATA["c_id"]);
                echo $db->update("enrollments", [
                    "u_role" => $JSON_DATA["u_role"],
                ])
                    ? jsonResponse(message: "อัพเดทผู้ใช้สำเร็จ")
                    : jsonResponse(400, "อัพเดทผู้ใช้ล้มเหลว");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case "DELETE":
            $_DELETE = json_decode(file_get_contents("php://input"), true);
            if (
                isset($_DELETE) &&
                key_exists("c_id", $_DELETE) &&
                key_exists("u_id", $_DELETE)
            ) {
                $db->where("u_id", $_DELETE["u_id"]);
                $db->where("c_id", $_DELETE["c_id"]);
                echo $db->delete("enrollments")
                    ? jsonResponse(message: "ลบผู้ใช้ออกจากคอร์สสำเร็จ")
                    : jsonResponse(400, "Fail to unenroll");
            } elseif (
                isset($_SESSION["u_id"]) &&
                isset($_DELETE) &&
                key_exists("c_id", $_DELETE)
            ) {
                $db->where("u_id", $_SESSION["u_id"]);
                $db->where("c_id", $_DELETE["c_id"]);
                echo $db->delete("enrollments")
                    ? jsonResponse(
                        message: "User unenroll the course successfully"
                    )
                    : jsonResponse(400, "Fail to unenroll");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        default:
            echo jsonResponse(405, "ไม่อนุญาตให้ใช้ Method นี้");
    }
} catch (Exception $e) {
    $error = explode(" ", $e->getMessage());
    if ($error[0] === "Duplicate" && $error[1] === "entry") {
        echo jsonResponse(500, "คุณเป็นสมาชิกของคอร๋สนี้อยู่แล้ว");
    } else {
        echo jsonResponse(500, $e->getMessage());
    }
}
