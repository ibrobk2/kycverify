<?php
require_once 'config/database.php';
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

$tables = ['birth_attestations', 'bvn_modification_uploads', 'verification_logs', 'service_transactions', 'pricing'];

$result = "";
foreach ($tables as $table) {
    $result .= "--- Table: $table ---\n";
    try {
        $stmt = $db->prepare("DESCRIBE $table");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            $result .= "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']}\n";
        }
    } catch (Exception $e) {
        $result .= "Error: " . $e->getMessage() . "\n";
    }
    $result .= "\n";
}

file_put_contents('schemas_dump.txt', $result);
echo "Done\n";
?>
