<?php
/**
 * Migration: Create VTU tables
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Read vtu-schema.sql
    $sql = file_get_contents(__DIR__ . '/vtu-schema.sql');
    
    // Execute SQL
    $db->exec($sql);
    
    echo "✅ VTU tables created successfully.\n";
    
} catch (Exception $e) {
    echo "❌ VTU Migration failed: " . $e->getMessage() . "\n";
}
?>
