<?php
require_once '../../../vendor/autoload.php';
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            
            break;
        case 'PUT':
            break;
        case 'DELETE':
            break;
        default:
            echo jsonResponse();
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
