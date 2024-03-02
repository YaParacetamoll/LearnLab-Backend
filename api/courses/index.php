<?php
require_once '../../vendor/autoload.php';
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $output = array();
            $page = isset($_GET["page"]) ? (intval($_GET["page"]) <= 0 ? 1 : (intval($_GET["page"]))) : 1;
            $locked = isset($_GET["locked"]) ? (($_GET["locked"] == 'true') ? 'true' : ($_GET["locked"])) : 'false'; // true - false - free
            $db->pageLimit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
            if (isset($_GET["search"]) && strlen($_GET["search"]) > 0) $db->where('c_name', '%' . $_GET["search"] . '%', 'LIKE');
            if ($locked == 'true') $db->where('c_hashed_password', NULL, 'IS NOT');
            if ($locked == 'free') $db->where('c_hashed_password', NULL, 'IS');
            $courses = $db->where('c_privacy', 'PUBLIC');
            $courses = $db->arraybuilder()->paginate("courses", $page);
            $output["page"] = $page;
            $output["limit"] = $db->pageLimit;
            $output["total_page"] = $db->totalPages;
            foreach (array_values($courses) as $i => $obj) {
                $courses[$i]['c_hashed_password'] = !is_null($courses[$i]['c_hashed_password']);
            }
            $output["data"] = $courses;
            echo json_encode($output);
            break;

        case 'POST':
            $output = array();
            if (!isset($_POST["c_id"]) /*|| !isset($_POST["u_id"]) */) {
                echo jsonResponse(400, "No Course ID given");
                break;
            }
            $db->where('c_id', intval($_POST["c_id"]));
            $course = $db->getOne("courses");
            $course['c_hashed_password'] = !is_null($course['c_hashed_password']);
            echo json_encode($course);
            break;

        case 'PUT':
            if (file_get_contents('php://input') == null) {
                echo jsonResponse(400, "Invalid input");
            } else {
                parse_str(file_get_contents('php://input'), $_PUT);
                if (key_exists("c_name", $_PUT) && key_exists("c_description", $_PUT) && key_exists("c_privacy", $_PUT) && key_exists("c_id", $_PUT) && key_exists("u_id", $_PUT)) {
                    $c_code = bin2hex(random_bytes(4));
                    $hash_password = key_exists("c_hashed_password", $_PUT) ? password_hash($_PUT["c_hashed_password"], PASSWORD_DEFAULT) : NULL;
                    $data = array(
                        "c_name" => $_PUT["c_name"],
                        "c_code" => $c_code,
                        "c_hashed_password" => $hash_password,
                        "c_description" => $_PUT["c_description"]
                    );
                    if ($db->insert('courses', $data)) {
                        $enroll_data = array(
                            "c_id" => $_PUT['c_id'],
                            "u_id" => $_PUT['u_id'],
                            "u_role" => "INSTRUCTOR"
                        );
                        $db->insert('enrollments', $enroll_data);
                        echo jsonResponse(message: 'Course was created successfully! Id = ' . $id);
                    } else {
                        echo jsonResponse(400, "Fail to create course.");
                    }
                } else {
                    echo jsonResponse(400, "Invalid input");
                }
            }
            break;
        case 'DELETE':
            if (file_get_contents('php://input') == null) {
                echo jsonResponse(400, "Invalid input");
            } else {
                parse_str(file_get_contents('php://input'), $_DELETE);
                if (key_exists("c_id", $_DELETE) && key_exists("u_id", $_DELETE)) {
                    $db->where("u_id", $_DELETE['u_id']);
                    $role = $db->getValue("enrollments", 'u_role');
                    if ($role && $role == "INSTRUCTOR") {
                        $db->where('c_id', $_DELETE["c_id"]);
                        echo ($db->delete('courses')) ? jsonResponse(message: 'successfully deleted') : jsonResponse(400, "fail to delete course");
                    } else {
                        echo jsonResponse(400, "Permission denied");
                    }
                } else {
                    echo jsonResponse(400, "Invalid input");
                }
            }
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
