<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeload();
$db = new MysqliDb(array(
    'host' => $_SERVER['DB_HOSTNAME'],
    'username' => $_SERVER['DB_USERNAME'],
    'password' => $_SERVER['DB_PASSWORD'],
    'db' => $_SERVER['DB_DATABASE'],
    'port' => $_SERVER['DB_PORT']
));
?>