<?php
require_once 'initialize_head.php';
require_once 'initialize_db.php';

function jsonResponse($response_code = 200, $message = "") {
    http_response_code($response_code);
    return json_encode(array(
        "status" => http_response_code(),
        "message" => $message
    ));
}
