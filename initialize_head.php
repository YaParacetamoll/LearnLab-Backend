<?php
require_once "vendor/autoload.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    header("Access-Control-Allow-Origin: http://localhost:3000");
    header(
        "Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS"
    );
    header("Access-Control-Allow-Headers: token, Content-Type");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 1728000");
    header("Content-Length: 0");
    header("Content-Type: text/plain");
    die();
}

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeload();
session_start();
?>
