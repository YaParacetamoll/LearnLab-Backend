<?php
require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->safeload();
$db = new MysqliDb(array(
    'host' => $_SERVER['DB_HOSTNAME'],
    'username' => $_SERVER['DB_USERNAME'],
    'password' => $_SERVER['DB_PASSWORD'],
    'db' => $_SERVER['DB_DATABASE'],
    'port' => $_SERVER['DB_PORT']
));

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $courses = $db->get('courses');
    echo json_encode($courses);
}
