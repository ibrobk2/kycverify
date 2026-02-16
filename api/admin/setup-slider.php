<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create slider_images table
    $sql = "CREATE TABLE IF NOT EXISTS slider_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(255) NOT NULL,
        caption VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);

    // Initial images (Unsplash professional photos)
    $initialImages = [
        ['url' => 'https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=2029&auto=format&fit=crop', 'caption' => 'Secure Verification Services'],
        ['url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072&auto=format&fit=crop', 'caption' => 'Fast and Reliable Results'],
        ['url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?q=80&w=1470&auto=format&fit=crop', 'caption' => '24/7 Dedicated Support'],
        ['url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=1415&auto=format&fit=crop', 'caption' => 'Comprehensive Analytics'],
        ['url' => 'https://images.unsplash.com/photo-1551288049-bbbda536339a?q=80&w=1470&auto=format&fit=crop', 'caption' => 'Manage Your Data Seamlessly']
    ];

    // Check if table is empty
    $count = $db->query("SELECT COUNT(*) FROM slider_images")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO slider_images (image_url, caption) VALUES (?, ?)");
        foreach ($initialImages as $img) {
            $stmt->execute([$img['url'], $img['caption']]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Slider database initialized successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Setup error: ' . $e->getMessage()]);
}
