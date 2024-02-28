<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: token, Content-Type');
    header('Access-Control-Max-Age: 1728000');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    die();
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->safeload();
$db = new MysqliDb(array(
    'host' => $_SERVER['DB_HOSTNAME'],
    'username' => $_SERVER['DB_USERNAME'],
    'password' => $_SERVER['DB_PASSWORD'],
    'db' => $_SERVER['DB_DATABASE'],
    'port' => $_SERVER['DB_PORT']
));

function jsonResponse($response_code = 200, $message = "") {
    http_response_code($response_code);
    return json_encode(array(
        "status" => http_response_code(),
        "message" => $message
    ));
}
