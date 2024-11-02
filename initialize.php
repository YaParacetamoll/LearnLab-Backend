<?php
require_once "initialize_head.php";
require_once "initialize_db.php";
require_once "initialize_aws.php";

ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );

function jsonResponse($response_code = 200, $message = "")
{
    http_response_code($response_code);
    return json_encode([
        "status" => http_response_code(),
        "message" => $message,
    ]);
}

require_once "initialize_jwt.php";
