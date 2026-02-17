<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $services = [
        ['service_name' => 'nin_verification', 'price' => 100.00, 'description' => 'Fast and secure NIN verification'],
        ['service_name' => 'bvn_verification', 'price' => 150.00, 'description' => 'Reliable BVN verification service'],
        ['service_name' => 'birth_attestation', 'price' => 500.00, 'description' => 'Official birth attestation processing'],
        ['service_name' => 'ipe_clearance', 'price' => 1000.00, 'description' => 'IPE clearance and verification'],
        ['service_name' => 'nin_validation', 'price' => 200.00, 'description' => 'In-depth NIN validation against records'],
        ['service_name' => 'bvn_modification', 'price' => 2500.00, 'description' => 'Service for BVN details modification'],
        ['service_name' => 'bvn_retrieval', 'price' => 300.00, 'description' => 'Retrieve lost or forgotten BVN'],
        ['service_name' => 'bvn_slip_printing', 'price' => 400.00, 'description' => 'High-quality BVN slip printing'],
        ['service_name' => 'airtime_purchase', 'price' => 0.00, 'description' => 'Buy airtime for all networks'],
        ['service_name' => 'data_purchase', 'price' => 0.00, 'description' => 'Buy data bundles for all networks']
    ];

    // Clear existing pricing to ensure a fresh start as requested
    $db->exec("DELETE FROM pricing");

    $stmt = $db->prepare("INSERT INTO pricing (service_name, price, description, status) VALUES (?, ?, ?, 'active')");

    foreach ($services as $service) {
        // Randomize price slightly around the base if user wants random, otherwise use these base values
        // Actually, user said "use random values", so let's vary them a bit
        $price = $service['price'] > 0 ? $service['price'] + rand(-20, 50) : rand(50, 500); 
        $stmt->execute([
            $service['service_name'],
            $price,
            $service['description']
        ]);
        echo "Inserted service: " . $service['service_name'] . " with price: ₦" . $price . "\n";
    }

    echo "Pricing table seeded successfully!\n";

} catch (Exception $e) {
    echo "Error seeding pricing table: " . $e->getMessage() . "\n";
}
?>
