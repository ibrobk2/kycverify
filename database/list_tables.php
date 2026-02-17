<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
try {
    $s = $db->query('SHOW TABLES');
    foreach ($s as $r) echo array_values($r)[0] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
