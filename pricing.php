<?php
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT * FROM pricing WHERE status = 'active' ORDER BY price ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $services = [];
    error_log("Error fetching pricing: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .pricing-header {
            background: linear-gradient(135deg, #1e3a8a, #06b6d4);
            padding: 100px 0 60px;
            color: white;
            text-align: center;
        }
        
        .pricing-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
        }
        
        .price-tag {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e3a8a;
        }
        
        .currency {
            font-size: 1.2rem;
            vertical-align: super;
        }
        
        .service-name {
            text-transform: capitalize;
            color: #4b5563;
        }

        .navbar {
            background-color: #1e3a8a !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-bolt text-info"></i>
                <span class="ms-2 fw-bold">agentify</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.html">Home</a>
                    <a class="nav-link active" href="pricing.php">Pricing</a>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Pricing Header -->
    <header class="pricing-header">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Transparent Pricing</h1>
            <p class="lead">Competitive rates for all our identity and verification services</p>
        </div>
    </header>

    <!-- Pricing Grid -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <?php if (empty($services)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">No services available at the moment.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card pricing-card text-center p-4">
                                <div class="card-body">
                                    <div class="mb-4">
                                        <i class="fas fa-shield-check fa-3x text-info mb-3"></i>
                                        <h4 class="service-name fw-bold"><?php echo str_replace('_', ' ', $service['service_name']); ?></h4>
                                    </div>
                                    <div class="mb-4">
                                        <span class="price-tag"><span class="currency">₦</span><?php echo number_format($service['price'], 2); ?></span>
                                    </div>
                                    <p class="text-muted mb-4"><?php echo $service['description']; ?></p>
                                    <a href="dashboard.php" class="btn btn-primary w-100 py-2">Get Started</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 text-center bg-white">
        <div class="container">
            <h2 class="fw-bold mb-4">Need a Custom Plan?</h2>
            <p class="lead mb-4">Contact our sales team for high-volume enterprise pricing.</p>
            <a href="mailto:support@agentify.com.ng" class="btn btn-outline-primary btn-lg px-5">Contact Sales</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 agentify. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
