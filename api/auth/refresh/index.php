<?php
require_once "../../../initialize.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (
                isset($_GET) && isset($JWT_SESSION_DATA["u_id"])
            ) {
                $db->where("u_id", $JWT_SESSION_DATA["u_id"]);
                $user = $db->getOne("users");
                echo json_encode([
                    "status" => http_response_code(),
                    "message" => "Token Refresh Successfully",
                    "access_token" => createJWT(["u_id" => $user["u_id"], "u_role" => $user["u_role"]]),
                    "refresh_token" => createJWT(["u_id" => $user["u_id"]], true)
                ]);
            } else {
                jsonResponse(400, "Invalid input");
            }
            break;
        default:
            echo jsonResponse(405, "ไม่อนุญาตให้ใช้ Method นี้");
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
