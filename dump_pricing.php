<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SELECT * FROM pricing");
    $pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/pricing_dump.json', json_encode($pricing, JSON_PRETTY_PRINT));
    echo "Done! Pricing data saved to pricing_dump.json";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
