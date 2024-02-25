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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $courses = $db->get('courses');
        echo json_encode($courses);
        break;
    case 'PUT':
        if (file_get_contents('php://input') == null) {
            http_response_code(400);
            echo json_encode(array(
                "status" => http_response_code(),
                "message" => "Invalid input"
            ));
        } else {
            parse_str(file_get_contents('php://input'), $_PUT);
            if (key_exists("c_name", $_PUT) && key_exists("c_description", $_PUT) && key_exists("c_privacy", $_PUT)) {
                $c_code = bin2hex(random_bytes(4));
                $hash_password = key_exists("c_hashed_password", $_PUT) ? password_hash($_PUT["c_hashed_password"], PASSWORD_DEFAULT) : NULL;
                $data = array(
                    "c_name" => $_PUT["c_name"],
                    "c_code" => $c_code,
                    "c_hashed_password" => $hash_password,
                    "c_description" => $_PUT["c_description"]
                );
                $id = $db->insert('courses', $data);
                if ($id) {
                    echo json_encode(array(
                        "status" => 200,
                        "message" => 'Course was created successfully! Id = ' . $id
                    ));
                } else {
                    http_response_code(400);
                    echo json_encode(array(
                        "status" => http_response_code(),
                        "message" => "Fail to create course."
                    ));
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "status" => http_response_code(),
                    "message" => "Invalid input"
                ));
            }
        }
        break;
    case 'DELETE':
        if (file_get_contents('php://input') == null) {
            http_response_code(400);
            echo json_encode(array(
                "status" => http_response_code(),
                "message" => "Invalid input"
            ));
        } else {
            parse_str(file_get_contents('php://input'), $_DELETE);
            if (key_exists("c_id", $_DELETE)) {
                $db->where('c_id', $_DELETE["c_id"]);
                if ($db->delete('courses')) echo json_encode(array(
                    "status" => 200,
                    "message" => 'successfully deleted'
                ));
                else {
                    http_response_code(400);
                    echo json_encode(array(
                        "status" => http_response_code(),
                        "message" => "fail to delete course"
                    ));
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "status" => http_response_code(),
                    "message" => "Invalid input"
                ));
            }
        }
        break;
}
