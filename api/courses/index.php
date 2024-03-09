<?php
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
            $courses = $db->arraybuilder()->paginate("courses", $page, "c_id, c_name, c_hashed_password, c_description, c_banner_mime_type, c_updated_at");
            $output["page"] = $page;
            $output["limit"] = $db->pageLimit;
            $output["total_page"] = $db->totalPages;
            foreach (array_values($courses) as $i => $obj) {
                $courses[$i]['c_hashed_password'] = !is_null($courses[$i]['c_hashed_password']);
                $courses[$i]['c_banner'] = !is_null($courses[$i]['c_banner_mime_type']);
                unset($courses[$i]['c_banner_mime_type']);
            }
            $output["data"] = $courses;
            echo json_encode($output);
            break;

        case 'PUT':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            $output = array();
            if (!isset($_SESSION['u_id']) && file_get_contents('php://input') == null) {
                echo jsonResponse(403, "Unauthenticated");
                break;
            }
            if (!isset($JSON_DATA["c_id"])) {
                echo jsonResponse(400, "No Course ID given");
                break;
            }
            $en_crs = array(); // Course that user enrolled
            $cols = array("c_id");
            $db->where('u_id', $_SESSION['u_id']);
            $enrolled_course = $db->get('enrollments', null, $cols);
            if ($db->count > 0)
                foreach ($enrolled_course as $ec) {
                    array_push($en_crs, $ec["c_id"]);
                }
            if (!in_array(intval($JSON_DATA["c_id"]), $en_crs)) {
                echo jsonResponse(403, "You're not a member of this course");
                die();
            }
            $db->where('c.c_id', intval($JSON_DATA["c_id"]));
            $db->join('enrollments e',  "e.c_id=c.c_id", "RIGHT");
            $course = $db->getOne("courses c", "c.c_id, c_name, c_description, c_code, c_hashed_password, c_updated_at, c_created_at, u_role, c_privacy, c_banner_mime_type");
            $course['c_hashed_password'] = !is_null($course['c_hashed_password']);
            $course['c_banner'] = !is_null($course['c_banner_mime_type']);
            unset($course['c_banner_mime_type']);
            echo json_encode($course);
            break;

        case 'POST':
            if (isset($_SESSION['u_role'])) {
                if ($_SESSION['u_role'] !== 'INSTRUCTOR') {
                    echo jsonResponse(403, "You can't perform this action!");
                }
            }
            if (isset($_SESSION['u_id']) && key_exists("c_name", $_POST) && key_exists("c_description", $_POST) && key_exists("c_privacy", $_POST)) {
                $c_code = bin2hex(random_bytes(4));
                $hash_password = key_exists("c_password", $_POST) ? password_hash($_POST["c_password"], PASSWORD_DEFAULT) : NULL;
                $data = array(
                    "c_name" => $_POST["c_name"],
                    "c_code" => $c_code,
                    "c_hashed_password" => $hash_password,
                    "c_description" => $_POST["c_description"],
                    "c_privacy" => $_POST['c_privacy']
                );
                if (isset($_FILES["c_banner"]) && $_FILES["c_banner"]["error"] == 0) {
                    $image = $_FILES["c_banner"]["tmp_name"];
                    $imgContent = file_get_contents($image);
                    $mime_type = mime_content_type($image);
                    if (!strcmp(explode("/", $mime_type)[0], "image")) {
                        $data["c_banner"] = $imgContent;
                        $data["c_banner_mime_type"] = $mime_type;
                    }
                }
                if ($db->insert('courses', $data)) {
                    $db->where("c_name", $_POST["c_name"]);
                    $c_id = $db->getValue("courses", "c_id");
                    $enroll_data = array(
                        "c_id" => $c_id,
                        "u_id" => intval($_SESSION['u_id']),
                        "u_role" => "INSTRUCTOR"
                    );
                    $db->insert('enrollments', $enroll_data);
                    echo jsonResponse(200, 'Course was created successfully!');
                } else {
                    echo jsonResponse(400, "Fail to create course.");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
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
                    echo ($clear_enrollment && $db->delete('courses')) ? jsonResponse('successfully deleted') : jsonResponse(400, "fail to delete course");
                } else {
                    echo jsonResponse(400, "Permission denied");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        default:
            echo jsonResponse(405, 'ไม่อนุญาตให้ใช้ Method นี้');
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
