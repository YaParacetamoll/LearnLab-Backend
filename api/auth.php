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

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    if (file_get_contents('php://input') == null) {
        http_response_code(400);
        echo json_encode(array(
            "status" => http_response_code(),
            "message" => "Invalid input"
        ));
    } else {
        parse_str(file_get_contents('php://input'), $_PUT);
        if (key_exists("u_firstname", $_PUT) && key_exists("u_lastname", $_PUT) && key_exists("u_tel", $_PUT) && key_exists("u_email", $_PUT) && key_exists("u_gender", $_PUT) && key_exists("u_role", $_PUT)) {
            $hash_password = key_exists("u_hashed_password", $_PUT) ? password_hash($_PUT["u_hashed_password"], PASSWORD_DEFAULT) : NULL;
            $data = array(
                "u_firstname" => $_PUT["u_firstname"],
                "u_lastname" => $_PUT["u_lastname"],
                "u_tel" => $_PUT["u_tel"],
                "u_email" => $_PUT["u_email"],
                "u_hashed_password" => $hash_password,
                "u_access_token" => bin2hex(random_bytes(8)),
                "u_gender" => $_PUT["u_gender"],
                "u_role" => $_PUT["u_role"]
            );
            $id = $db->insert('users', $data);
            if ($id) {
                echo json_encode(array(
                    "status" => 200,
                    "message" => 'User was created successfully! Id = ' . $id
                ));
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "status" => http_response_code(),
                    "message" => "Fail to create user."
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
