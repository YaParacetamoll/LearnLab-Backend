<?php
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_SESSION['u_id']) && isset($_GET) && key_exists("c_id", $_GET)) {
                $db->where('u_id', intval($_SESSION['u_id']));
                $db->where('c_id', $_GET['c_id']);
                $result = $db->getOne("enrollments");
                if ($result) {
                    $output = array();
                    $db->where('p.c_id', $_GET['c_id']);
                    $db->join("users u", "u.u_id=p.u_id", "LEFT");
                    $db->join("enrollments e", "e.u_id=p.u_id AND e.c_id=p.c_id", "LEFT");
                    $cols = array("p_id, p_created_at, p_updated_at, p_title, p_content, p_item_list, p_type, p.u_id, e.u_role, u_firstname, u_lastname, u_avatar_mime_type");
                    $db->orderBy('p.p_created_at', 'desc');
                    $posts = $db->get("posts p", null, $cols);
                    foreach (array_values($posts) as $i => $obj) {
                        $posts[$i]['u_avatar'] = !is_null($posts[$i]['u_avatar_mime_type']);
                        $posts[$i]['p_item_list'] = json_decode($posts[$i]['p_item_list']);
                        unset($posts[$i]['u_avatar_mime_type']);

                        if (count($posts[$i]['p_item_list']->assignments) > 0) {
                            $db->where('a_id', $posts[$i]['p_item_list']->assignments, 'IN');
                            $cols = array("a_id", "a_name", "a_due_date", "a_score");
                            $posts[$i]['p_item_list']->assignments = $db->get('assignments', null, $cols);
                        }

                        if (count($posts[$i]['p_item_list']->files) > 0) {
                            $db->where('f_id', $posts[$i]['p_item_list']->files, 'IN');
                            $db->orderBy('f_name', 'asc');
                            $cols = array("f_id", "f_name", "f_mime_type");
                            $posts[$i]['p_item_list']->files = $db->get('files', null, $cols);
                        }

                        if (count($posts[$i]['p_item_list']->quizzes) > 0) {
                            $db->where('q_id', $posts[$i]['p_item_list']->quizzes, 'IN');
                            $cols = array("q_id", "q_name", "q_due_date", "q_time_limit");
                            $posts[$i]['p_item_list']->quizzes = $db->get('quizzes', null, $cols);
                        }
                    }
                    $output["data"] = $posts;
                    // $output["statement"] = $db->getLastQuery();

                    echo json_encode($output);
                } else {
                    echo jsonResponse(400, "You are not enrolled on that course.");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case 'PUT':
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($_PUT) && key_exists('c_id', $_PUT) && key_exists('p_title', $_PUT) && key_exists('p_type', $_PUT)) {
                $db->where('c_id', $_PUT['c_id']);
                $db->where('u_id', intval($_SESSION['u_id']));
                $result = $db->getOne("enrollments");
                if ($result && in_array($result['u_role'], ['TA', 'INSTRUCTOR'])) {
                    $p_content = key_exists('p_content', $_PUT) ? $_PUT['p_content'] : NULL;
                    $ct_id = key_exists('ct_id', $_PUT) ? $_PUT['ct_id'] : NULL;
                    $p_item_list = key_exists('p_item_list', $_PUT) ? json_encode($_PUT['p_item_list']) : NULL;
                    $p_show_time = key_exists('p_show_time', $_PUT) ? $_PUT['p_show_time'] : NULl;
                    $data = array(
                        "c_id" => $_PUT['c_id'],
                        "u_id" => intval($_SESSION['u_id']),
                        "ct_id" => $ct_id,
                        "p_title" => $_PUT['p_title'],
                        "p_type" => $_PUT['p_type'],
                        "p_content" => $p_content,
                        "p_item_list" => $p_item_list,
                        "p_show_time" => $p_show_time
                    );
                    echo ($db->insert('posts', $data)) ? jsonResponse(message: "Post created successfully") : jsonResponse(400, "Failed to create post");
                } else {
                    echo jsonResponse(400, "Permission denied"); //ไม่ได้เป็น TA หรือ INSTRUCTOR ใน course นั้นๆ
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case 'POST':
            $JSON_DATA = json_decode(file_get_contents('php://input'), true);
            if (isset($_SESSION['u_id']) && isset($JSON_DATA) && key_exists("p_id", $JSON_DATA)) { //ถ้าเราจะมีการแก้ไข Post ก็น่าจะประมาณนี้นะ, ไม่รู้ว่า Post item จะมีการแก้ไขยังไงได้บ้างเลย commit แบบนี้ไปก่อนละกัน 
                $db->where("p_id", $JSON_DATA["p_id"]);
                $post_u_id = $db->getValue('posts', 'u_id');
                if (intval($_SESSION['u_id']) == $post_u_id) {
                    $data = array();
                    foreach (array_keys($JSON_DATA) as $key) {
                        $data[$key] = ($key == "p_item_list") ? json_encode($JSON_DATA[$key]) : $JSON_DATA[$key];
                    }
                    $db->where("p_id", $JSON_DATA['p_id']);
                    echo ($db->update('posts', $data)) ? jsonResponse(message: "Post edited successfully") : jsonResponse(400, "Fail to edit post.");
                } else {
                    echo jsonResponse(400, "Permission denied");
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case 'DELETE':
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            // รับทั้ง u_id(id ของ user ที่เข้าใช้งานอยู่), c_id(่ของ course ที่ต้องการลบ post) และ p_id(post ที่ต้องการลบ) มา
            if (isset($_SESSION['u_id']) && isset($_DELETE) && key_exists("p_id", $_DELETE) && key_exists("c_id", $_DELETE)) {
                $db->join("posts p", "p.u_id=e.u_id", "LEFT");
                $db->where("p.p_id", $_DELETE['p_id']);
                $db->where("e.c_id", $_DELETE['c_id']);
                $post_info = $db->getOne("enrollments e", null, "e.u_id, e.u_role");
                $db->where("u_id", intval($_SESSION['u_id']));
                $db->where("c_id", $_DELETE['c_id']);
                $user_role = $db->getValue('enrollments', 'u_role');
                if ($_SESSION['u_id'] == $post_info['u_id'] || ($user_role == 'INSTRUCTOR' && $post_info['u_role'] == 'TA')) {
                    $db->where("p_id", $_DELETE["p_id"]);
                    $p_item_list = json_decode($db->getValue("posts", "p_item_list"));
                    if (count($p_item_list->files) > 0) {
                        $db->where('f_id', $p_item_list->files, 'IN');
                        if (!$db->delete("files")) {
                            echo jsonResponse(400, "ไม่สามารถลบไฟล์ในโพสต์ได้");
                            break;
                        }
                    }

                    $db->where('p_id', $_DELETE['p_id']);
                    echo ($db->delete('posts')) ? jsonResponse(message: "ลบโพสต์เรียบร้อยแล้ว") : jsonResponse(400, "ไม่สามารถลบโพสต์ได้");
                } else {
                    echo jsonResponse(403, "ไม่มีสิทธิ์ในการลบโพสต์");
                    break;
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
