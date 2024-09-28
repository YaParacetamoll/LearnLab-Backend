<?php
require_once "../../initialize_head.php";
// session_status explained:
// _DISABLED = 0
// _NONE = 1
// _ACTIVE = 2

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            $session = [
                "state" => session_status(),
                "PHPSESSID" => session_id(),
                "session_name" => session_name(),
            ];
            echo json_encode($session);
            break;
        case "DELETE":
            session_unset();
            session_destroy();
            unset($_COOKIE[session_name()]);
            setcookie(session_name(), "", time() - 3600, "/");
            $session = [
                "state" => session_status(),
                "PHPSESSID" => session_id(),
                "session_name" => session_name(),
            ];
            echo json_encode($session);
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
