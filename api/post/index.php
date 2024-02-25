<?php
require_once '../../vendor/autoload.php';
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET) && key_exists("u_id", $_GET) && key_exists("c_id", $_GET)) {
                $db->where('u_id', $_GET['u_id']);
                $db->where('c_id', $_GET['c_id']);
                $result = $db->getOne("enrollments");
                if ($result) {
                    $output = array();
                    $page = isset($_GET["page"]) ? (intval($_GET["page"]) <= 0 ? 1 : (intval($_GET["page"]))) : 1;
                    $db->pageLimit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
                    $db->where('c_id', $_GET['c_id']);
                    $courses = $db->arraybuilder()->paginate("posts", $page);
                    $output["page"] = $page;
                    $output["limit"] = $db->pageLimit;
                    $output["total_page"] = $db->totalPages;
                    $output["data"] = $courses;
                    echo json_encode($output);
                } else {
                    http_response_code(400);
                    echo json_encode(array(
                        "status" => http_response_code(),
                        "message" => "Course not found"
                    ));
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "status" => http_response_code(),
                    "message" => "Invalid input"
                ));
            }
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
                if (key_exists('c_id', $_PUT) && key_exists('u_id', $_PUT) && key_exists('p_title', $_PUT) && key_exists('p_type', $_PUT)) {
                    //
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
