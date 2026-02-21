<?php
require_once 'config/database.php';
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

$service_name = 'ipe_clearance';
$stmt = $db->prepare("SELECT price FROM pricing WHERE service_name = ? AND status = 'active'");
$stmt->execute([$service_name]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "DB Price for 'ipe_clearance': " . ($result ? $result['price'] : "Not found") . "\n";

if (defined('IPE_CLEARANCE_COST')) {
    echo "Constant IPE_CLEARANCE_COST: " . IPE_CLEARANCE_COST . "\n";
} else {
    echo "Constant IPE_CLEARANCE_COST: Not defined\n";
}
?>
