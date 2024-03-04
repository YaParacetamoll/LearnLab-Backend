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
            if (!isset($_SESSION['u_id'])) {
                jsonResponse(403, "Unauthenticated");
                die();
            }

            $en_crs = array(); // Course that user enrolled
            $cols = array("c_id");
            $db->where('u_id', $_SESSION['u_id']);
            $enrolled_course = $db->get('enrollments', null, $cols);
            if ($db->count > 0)
                foreach ($enrolled_course as $ec) {
                    array_push($en_crs, $ec["c_id"]);
                }
            if (!in_array(intval($_POST["c_id"]), $en_crs)) {
                jsonResponse(403, "You're not a member of this course");
                die();
            }
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
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($_PUT) && key_exists("c_name", $_PUT) && key_exists("c_description", $_PUT) && key_exists("c_privacy", $_PUT)) {
                $c_code = bin2hex(random_bytes(4));
                $hash_password = key_exists("c_hashed_password", $_PUT) ? password_hash($_PUT["c_hashed_password"], PASSWORD_DEFAULT) : NULL;
                $data = array(
                    "c_name" => $_PUT["c_name"],
                    "c_code" => $c_code,
                    "c_hashed_password" => $hash_password,
                    "c_description" => $_PUT["c_description"]
                );
                if ($db->insert('courses', $data)) {
                    $db->where("c_name", $_PUT["c_name"]);
                    $c_id = $db->getValue("courses", "c_id");
                    $enroll_data = array(
                        "c_id" => $c_id,
                        "u_id" => intval($_SESSION['u_id']),
                        "u_role" => "INSTRUCTOR"
                    );
                    $db->insert('enrollments', $enroll_data);
                    echo jsonResponse(message: 'Course was created successfully!');
                } else {
                    echo jsonResponse(400, "Fail to create course.");
                }
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
        case 'DELETE':
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($_DELETE) && key_exists("c_id", $_DELETE)) {
                $db->where("u_id", $_SESSION['u_id']);
                $role = $db->getValue("enrollments", 'u_role');
                if ($role && $role == "INSTRUCTOR") {
                    $db->where('c_id', $_DELETE['c_id']);
                    $clear_enrollment = ($db->delete('enrollments')) ? true : false;
                    $db->where('c_id', $_DELETE["c_id"]);
                    echo ($clear_enrollment && $db->delete('courses')) ? jsonResponse(message: 'successfully deleted') : jsonResponse(400, "fail to delete course");
                } else {
                    echo jsonResponse(400, "Permission denied");
                }
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
