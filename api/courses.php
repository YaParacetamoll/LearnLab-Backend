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

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
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
            $data = array(
                "c_name" => $_PUT["c_name"],
                "c_code" => $c_code,
                "c_description" => $_PUT["c_description"]
            );
            $id = $db->insert('courses', $data);
            if ($id) {
                echo json_encode(array(
                    "status" => 200,
                    "message" => 'Courses was created successfully! Id = ' . $id
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
}

if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
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
}
