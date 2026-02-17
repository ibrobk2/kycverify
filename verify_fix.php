<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['service'] = 'bvn_slip_printing';
define('IN_VERIFICATION', true); 
// We include the file. If it hits exit, it's fine, we see the output.
require_once __DIR__ . '/api/get-service-price.php';
?>
