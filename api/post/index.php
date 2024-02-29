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
                    $posts = $db->arraybuilder()->paginate("posts", $page);
                    $output["page"] = $page;
                    $output["limit"] = $db->pageLimit;
                    $output["total_page"] = $db->totalPages;
                    $output["data"] = $posts;
                    echo json_encode($output);
                } else {
                    echo jsonResponse(400, "User didn't have enrollment on that course");
                }
            } else {
                echo jsonResponse(400, "Invalid input");
            }
            break;
        case 'PUT':
            if (file_get_contents('php://input') == null) {
                echo jsonResponse(400, "Invalid input");
            } else {
                parse_str(file_get_contents('php://input'), $_PUT);
                if (key_exists('c_id', $_PUT) && key_exists('u_id', $_PUT) && key_exists('p_title', $_PUT) && key_exists('p_type', $_PUT)) {
                    $db->where('c_id', $_PUT['u_id']);
                    $db->where('u_id', $_PUT['c_id']);
                    $result = $db->getOne("enrollments");
                    if ($result && in_array($result['u_role'], ['TA', 'INSTRUCTOR'])) {
                        $p_content = key_exists('p_content', $_PUT) ? $_PUT['p_content'] : NULL;
                        $p_item_list = key_exists('p_item_list', $_PUT) ? $_PUT['p_item_list'] : NULL;
                        $p_show_time = key_exists('p_show_time', $_PUT) ? $_PUT['p_show_time'] : NULl;
                        $data = array(
                            "c_id" => $_PUT['c_id'],
                            "u_id" => $_PUT['u_id'],
                            "p_title" => $_PUT['p_title'],
                            "p_type" => $_PUT['p_type'],
                            "p_content" => $p_content,
                            "p_item_list" => $p_item_list,
                            "p_show_time" => $P_show_time
                        );
                        echo ($db->insert('posts', $data)) ? jsonResponse(message: "Post created successfully") : jsonResponse(400, "Failed to create post");
                    } else {
                        echo jsonResponse(400, "Permission denied");//ไม่ได้เป็น TA หรือ INSTRUCTOR ใน course นั้นๆ
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
                //รับทั้ง u_id(id ของ user ที่เข้าใช้งานอยู่), c_id(่ของ course ที่ต้องการลบ post) และ p_id(post ที่ต้องการลบ) มา
                if (key_exists("u_id", $_DELETE) && key_exists("p_id", $_DELETE) && key_exists("c_id", $_DELETE)) {
                    $db->join("posts p", "p.u_id=e.u_id", "LEFT");
                    $db->where("p.p_id", $_DELETE['p_id']);
                    $db->where("e.c_id", $_DELETE['c_id']);
                    $post_info = $db->getOne("enrollments e", null, "e.u_id, e.u_role");

                    $db->where("u_id", $_DELETE['u_id']);
                    $db->where("c_id", $_DELETE['c_id']);
                    $user_role = $db->getValue('enrollments', 'u_role');
                    $db->where('p_id', $_DELETE['p_id']);
                    echo (($_DELETE['u_id'] == $post_info[0]['u_id'] || ($user_role == 'INSTRUCTOR' && $post_info[0]['u_role'] == 'TA')) && $db->delete('posts')) ? jsonResponse(message: "Post deleted successfully") : jsonResponse(400, "Permission denied");
                } else {
                    echo jsonResponse(400, "Invalid input");
                }
            }
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
