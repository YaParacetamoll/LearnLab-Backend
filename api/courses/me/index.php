<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_SESSION['u_id'])) {

            $output = array();
            $page = isset($_GET["page"]) ? (intval($_GET["page"]) <= 0 ? 1 : (intval($_GET["page"]))) : 1;
            $locked = isset($_GET["locked"]) ? (($_GET["locked"] == 'true') ? 'true' : ($_GET["locked"])) : 'false'; // true - false - free
            $db->pageLimit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
            $db->join("courses c", "e.c_id=c.c_id", "LEFT");
            $db->where("e.u_id", $_SESSION['u_id']);
            if (isset($_GET["search"]) && strlen($_GET["search"]) > 0) $db->where('c_name', '%' . $_GET["search"] . '%', 'LIKE');
            if ($locked == 'true') $db->where('c_hashed_password', NULL, 'IS NOT');
            if ($locked == 'free') $db->where('c_hashed_password', NULL, 'IS');
            $courses = $db->arraybuilder()->paginate("enrollments e", $page);
            $output["page"] = $page;
            $output["limit"] = $db->pageLimit;
            $output["total_page"] = $db->totalPages;
            foreach (array_values($courses) as $i => $obj) {
                $courses[$i]['c_hashed_password'] = !is_null($courses[$i]['c_hashed_password']);
            }
            $output["data"] = $courses;
            echo json_encode($output);
        } else {
            echo jsonResponse(403, 'Unauthenticated');
        }
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
