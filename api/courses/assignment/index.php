<?php
require_once '../../../initialize.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        
    }
} catch (Exception $e) {
    echo jsonResponse(500, $e->getMessage());
}
