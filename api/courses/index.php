<?php
require_once '../../vendor/autoload.php';
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $output = array();
            $page = isset($_GET["page"]) ? (intval($_GET["page"]) <= 0 ? 1 : (intval($_GET["page"]))) : 1;
            $db->pageLimit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
            if (isset($_GET["search"])) $db->where('c_name', '%' . $_GET["search"] . '%', 'LIKE');
            $courses = $db->where('c_privacy', 'PUBLIC');
            $courses = $db->arraybuilder()->paginate("courses", $page);
            $output["page"] = $page;
            $output["limit"] = $db->pageLimit;
            $output["total_page"] = $db->totalPages;
            $output["data"] = $courses;
            echo json_encode($output);
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
            default:
            echo json_encode(array(
                "status" => http_response_code(),
                "message" => ""
            ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => http_response_code(),
        "message" => $e->getMessage()
    ));
}
