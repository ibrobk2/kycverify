<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Database connected.\n";

    // 1. Check tables and columns
    $tables = ['wallet_transactions', 'service_transactions', 'vtu_transactions'];
    foreach ($tables as $table) {
        echo "Checking table: $table\n";
        try {
            $stmt = $db->query("SHOW COLUMNS FROM $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "Columns: " . implode(', ', $columns) . "\n\n";
        } catch (Exception $e) {
            echo "Error checking table $table: " . $e->getMessage() . "\n\n";
        }
    }

    // 2. Try the Union Query parts individually
    
    // Service Transactions
    echo "Testing Service Transactions Query...\n";
    try {
        $sql = "SELECT st.id, 'service' as source_type, st.service_type, 
                         u.name as user_name, u.email as user_email,
                         st.reference_number, st.status, st.amount, st.provider,
                         st.admin_notes, st.created_at
                         FROM service_transactions st
                         LEFT JOIN users u ON st.user_id = u.id
                         LIMIT 1";
        $db->query($sql);
        echo "Service Transactions Query: OK\n";
    } catch (Exception $e) {
        echo "Service Transactions Query Failed: " . $e->getMessage() . "\n";
    }

    // Wallet Transactions
    echo "Testing Wallet Transactions Query...\n";
    try {
        $sql = "SELECT wt.id, 'wallet' as source_type, 
                         CONCAT('wallet_', wt.transaction_type) as service_type,
                         u.name as user_name, u.email as user_email,
                         wt.reference as reference_number, 
                         'completed' as status,
                         wt.amount, 'wallet' as provider,
                         wt.description as admin_notes, wt.created_at
                         FROM wallet_transactions wt
                         LEFT JOIN users u ON wt.user_id = u.id
                         LIMIT 1";
        $db->query($sql);
        echo "Wallet Transactions Query: OK\n";
    } catch (Exception $e) {
        echo "Wallet Transactions Query Failed: " . $e->getMessage() . "\n";
    }

    // VTU Transactions
    echo "Testing VTU Transactions Query...\n";
    try {
        $sql = "SELECT vt.id, 'vtu' as source_type,
                         CONCAT('vtu_', LOWER(vt.transaction_type)) as service_type,
                         u.name as user_name, u.email as user_email,
                         vt.transaction_ref as reference_number,
                         LOWER(vt.status) as status,
                         vt.amount, 'vtu' as provider,
                         vt.status_message as admin_notes, vt.created_at
                         FROM vtu_transactions vt
                         LEFT JOIN users u ON vt.user_id = u.id
                         LIMIT 1";
        $db->query($sql);
        echo "VTU Transactions Query: OK\n";
    } catch (Exception $e) {
        echo "VTU Transactions Query Failed: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>
