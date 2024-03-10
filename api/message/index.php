<?php
require_once '../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case "GET":
            // if (!isset($_SESSION['u_id'])) {
            //     echo jsonResponse(403, "Unauthenticated");
            //     die();
            // }
            if (isset($_GET) && key_exists("c_id", $_GET) && key_exists("p_receiver", $_GET)) {
                $db->join("messages c1", "c1.m_thread=c2.m_id", "RIGHT");
                $db->join("posts p", "c1.p_receiver=p.p_id", "LEFT");
                $db->join("users u", "c1.m_sender=u.u_id", "LEFT");
                $db->where("c_id", $_GET['c_id']);
                $db->where("c1.p_receiver", $_GET['p_receiver']);
                $db->orderBy("c1.m_thread, c1.m_id", "asc");
                $messages = $db->get("messages c2", null, "c1.*, p.c_id, c2.m_content AS parent_content, u.u_firstname, u.u_lastname");
                // echo json_encode($messages);
                if ($messages) {
                    $comments = array();
                    $currentThread = null;
                    foreach ($messages as $row) {
                        if ($row['m_thread'] == null) {
                            $currentThread = $row['m_id'];
                            $comments[$currentThread] = array(
                                'm_id' => $row['m_id'],
                                'c_id' => $row['c_id'],
                                'm_sender' => $row['m_sender'],
                                'u_firstname' => $row['u_firstname'],
                                'u_lastname' => $row['u_lastname'],
                                'p_receiver' => $row['p_receiver'],
                                'm_content' => $row['m_content'],
                                'created_at' => $row['created_at'],
                                'replies' => array()
                            );
                        } else {
                            $comments[$row['m_thread']]['replies'][] = array(
                                'm_id' => $row['m_id'],
                                'c_id' => $row['c_id'],
                                'u_firstname' => $row['u_firstname'],
                                'u_lastname' => $row['u_lastname'],
                                'm_sender' => $row['m_sender'],
                                'm_content' => $row['m_content'],
                                'created_at' => $row['created_at'], 
                                'm_thread' => $row['m_thread'],
                            );
                        }
                    }

                    $data = array();
                    foreach ($comments as $index => $val) {
                        array_push($data, $comments[$index]);
                    }

                    echo json_encode($data);
                } else {
                    echo json_encode(array());
                }
            } else {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
            }
            break;
        case "PUT": //send message
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $_PUT = json_decode(file_get_contents('php://input'), true);
            if (!isset($_PUT) && !key_exists("p_receiver", $_PUT) && !key_exists("c_id", $_PUT) && !key_exists("m_content", $_PUT)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $data = array("m_sender" => $_SESSION['u_id']);
            foreach (array_keys($_PUT) as $key) {
                if ($key == "c_id") {
                    continue;
                }
                $data[$key] = $_PUT[$key];
            }
            echo ($db->insert("messages", $data)) ? jsonResponse(message: "ส่งข้อความเรียบร้อย") : jsonResponse(400, "ไม่สามารถส่งข้อความได้");
            break;
        case "DELETE": //delete?
            if (!isset($_SESSION['u_id'])) {
                echo jsonResponse(403, "Unauthenticated");
                die();
            }
            $_DELETE = json_decode(file_get_contents('php://input'), true);
            if (!isset($_DELETE) && key_exists("m_id", $_DELETE)) {
                echo jsonResponse(400, "ค่าที่ให้มาไม่ครบหรือไม่ถูกต้อง");
                die();
            }
            $db->where("m_id", $_DELETE["m_id"]);
            $m_sender = $db->getValue("messages", "m_sender");
            $db->where("m_id", $_DELETE["m_id"]);
            echo (!strcmp($m_sender, $_SESSION['u_id']) && $db->update("messages", array('m_content' => "ข้อความนี้ถูกลบแล้ว"))) ? jsonResponse(message: "ลบข้อความเรียบร้อย") : jsonResponse(400, "ไม่สามารถลบข้อความได้");
            break;
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
