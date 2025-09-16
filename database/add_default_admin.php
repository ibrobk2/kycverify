<?php
require_once '../config/database.php';

$email = 'admin@example.com';
$password = 'password123'; // Change this to a strong password in production
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if admin already exists
    $query = "SELECT id FROM admins WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Admin user '{$email}' already exists.\n";
        exit;
    }

    $query = "INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute(['Default Admin', $email, $hashed_password, 'superadmin']);

    echo "Default admin user '{$email}' added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
