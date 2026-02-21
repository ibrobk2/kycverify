<?php
require_once 'config/database.php';
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

$service_name = 'ipe_clearance';
$stmt = $db->prepare("SELECT * FROM pricing WHERE service_name = ? AND status = 'active'");
$stmt->execute([$service_name]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$output = "DB Price for 'ipe_clearance': " . ($result ? $result['price'] : "Not found") . "\n";

if (defined('IPE_CLEARANCE_COST')) {
    $output .= "Constant IPE_CLEARANCE_COST: " . IPE_CLEARANCE_COST . "\n";
} else {
    $output .= "Constant IPE_CLEARANCE_COST: Not defined\n";
}

file_put_contents('price_result.txt', $output);
echo "Done\n";
?>
