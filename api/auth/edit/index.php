<?php
require_once "../../../initialize.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            if (isset($_SESSION["u_id"]) && count($_POST) > 0) {
                $keys = ["u_firstname", "u_lastname", "u_tel", "u_password"];
                $data = [];
                $db->where("u_id", $_SESSION["u_id"]);
                $user = $db->getOne("users", "u_hashed_password");
                foreach ($keys as $key) {
                    if (key_exists($key, $_POST)) {
                        $data[$key] = $_POST[$key];
                    }
                }
                if (
                    !password_verify(
                        $data["u_password"],
                        $user["u_hashed_password"]
                    )
                ) {
                    echo jsonResponse(403, "รหัสผ่านไม่ถูกต้อง");
                    die();
                } else {
                    unset($data["u_password"]);
                }
                if (
                    isset($_FILES["u_avatar"]) &&
                    $_FILES["u_avatar"]["error"] == 0
                ) {
                    $image = $_FILES["u_avatar"]["tmp_name"];
                    $imgContent = file_get_contents($image);
                    $mime_type = mime_content_type($image);
                    if (!strcmp(explode("/", $mime_type)[0], "image")) {
                        $data["u_avatar"] = $imgContent;
                        $data["u_avatar_mime_type"] = $mime_type;
                    }
                }
                $db->where("u_id", $_SESSION["u_id"]);
                echo $db->update("users", $data)
                    ? jsonResponse(message: "แก้ไขโปรไฟล์สำเร็จ")
                    : jsonResponse(400, "แก้ไขโปรไฟล์ล้มเหลว");
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
