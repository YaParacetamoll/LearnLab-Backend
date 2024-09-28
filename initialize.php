<?php
require_once "initialize_head.php";
require_once "initialize_db.php";
require_once "initialize_aws.php";

function jsonResponse($response_code = 200, $message = "")
{
    http_response_code($response_code);
    return json_encode([
        "status" => http_response_code(),
        "message" => $message,
    ]);
}
