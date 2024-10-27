<?php
require_once "../../../vendor/autoload.php";
require_once "../../../initialize.php";

try {
    if (!isset($JWT_SESSION_DATA["u_id"])) {
        echo jsonResponse(403, "Unauthenticated");
        die();
    }

    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["mycourse"])) {
                $en_crs = [];
                $cols = ["c_id"];
                $db->where("u_id", $JWT_SESSION_DATA["u_id"]);
                $enrolled_course = $db->get("enrollments", null, $cols);
                if ($db->count > 0) {
                    foreach ($enrolled_course as $ec) {
                        array_push($en_crs, $ec["c_id"]);
                    };
                }
                echo json_encode($en_crs);
            } elseif (isset($_GET["my_course_role"])) {
                $en_crs = [];
                $cols = ["c_id", "u_role"];
                $db->where("u_id", $JWT_SESSION_DATA["u_id"]);
                $enrolled_course = $db->get("enrollments", null, $cols);
                if ($db->count > 0) {
                    foreach ($enrolled_course as $ec) {
                        $en_crs[$ec["c_id"]] = $ec["u_role"];
                    };
                }
                echo json_encode($en_crs);
            } else {
                $output = [];
                $page = isset($_GET["page"])
                    ? (intval($_GET["page"]) <= 0
                        ? 1
                        : intval($_GET["page"]))
                    : 1;
                $locked = isset($_GET["locked"])
                    ? ($_GET["locked"] == "true"
                        ? "true"
                        : $_GET["locked"])
                    : "false"; // true - false - free
                $db->pageLimit = isset($_GET["limit"])
                    ? intval($_GET["limit"])
                    : 10;
                $db->join("courses c", "e.c_id=c.c_id", "LEFT");
                $db->where("e.u_id", $JWT_SESSION_DATA["u_id"]);
                if (isset($_GET["search"]) && strlen($_GET["search"]) > 0) {
                    $db->where("c_name", "%" . $_GET["search"] . "%", "LIKE");
                }
                if ($locked == "true") {
                    $db->where("c_hashed_password", null, "IS NOT");
                }
                if ($locked == "free") {
                    $db->where("c_hashed_password", null, "IS");
                }
                $courses = $db
                    ->arraybuilder()
                    ->paginate(
                        "enrollments e",
                        $page,
                        "c.c_id, c_name, c_hashed_password, c_description, c_banner_mime_type, c_updated_at"
                    );
                $output["page"] = $page;
                $output["limit"] = $db->pageLimit;
                $output["total_page"] = $db->totalPages;
                foreach (array_values($courses) as $i => $obj) {
                    $courses[$i]["c_hashed_password"] = !is_null(
                        $courses[$i]["c_hashed_password"]
                    );
                    $courses[$i]["c_banner"] = !is_null(
                        $courses[$i]["c_banner_mime_type"]
                    );
                    unset($courses[$i]["c_banner_mime_type"]);
                }
                $output["data"] = $courses;
                echo json_encode($output);
            }
            break;
        default:
            echo jsonResponse(405, "ไม่อนุญาตให้ใช้ Method นี้");
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
