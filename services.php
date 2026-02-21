<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';

// Authentication check can be added here if needed, 
// but we'll follow the dashboard's pattern of letting JS handle it for now
// while providing PHP-side balance data if available.

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$wallet_balance = 0.00;

if ($user_id) {
    $walletHelper = new WalletHelper();
    $wallet_balance = $walletHelper->getBalance($user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Services - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #eef2f7;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #06b6d4;
        }
        .service-icon {
            width: 60px;
            height: 60px;
            background: #f0f9ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: #06b6d4;
            font-size: 24px;
        }
        .service-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .service-card:hover .service-icon {
            background: #06b6d4;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="page-title">All Services</h1>
                    <div class="header-actions">
                        <div class="wallet-info me-3 d-none d-md-flex align-items-center">
                            <span class="text-muted me-2">Balance:</span>
                            <span class="fw-bold text-primary" id="headerWalletBalance">₦<?php echo number_format($wallet_balance, 2); ?></span>
                        </div>
                        <div class="user-dropdown">
                            <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle fa-2x"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="logout()">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-12">
                            <p class="text-muted">Select a service to get started with your verification or purchase.</p>
                        </div>
                    </div>

                    <div class="services-grid">
                        <!-- NIN Services -->
                        <div class="service-card" onclick="window.location.href='nin-verification.php'">
                            <div class="service-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <h6 class="service-title">NIN Verification</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='nin-validation.php'">
                            <div class="service-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h6 class="service-title">NIN Validation</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='nin-modification.php'">
                            <div class="service-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <h6 class="service-title">NIN Modification</h6>
                        </div>

                        <!-- BVN Services -->
                        <div class="service-card" onclick="window.location.href='bvn-retrieval.php'">
                            <div class="service-icon">
                                <i class="fas fa-search-plus"></i>
                            </div>
                            <h6 class="service-title">BVN Retrieval</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='bvn-slip-printing.php'">
                            <div class="service-icon">
                                <i class="fas fa-print"></i>
                            </div>
                            <h6 class="service-title">BVN SLIP</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='bvn-modification.php'">
                            <div class="service-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <h6 class="service-title">BVN Modification</h6>
                        </div>

                        <!-- Other Identity Services -->
                        <div class="service-card" onclick="window.location.href='birth-attestation.php'">
                            <div class="service-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h6 class="service-title">Birth Attestation</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='ipe-clearance.php'">
                            <div class="service-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h6 class="service-title">IPE Clearance</h6>
                        </div>

                        <!-- Utility Services -->
                        <div class="service-card" onclick="window.location.href='airtime-purchase.php'">
                            <div class="service-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h6 class="service-title">Buy Airtime</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='data-purchase.php'">
                            <div class="service-icon">
                                <i class="fas fa-wifi"></i>
                            </div>
                            <h6 class="service-title">Buy Data</h6>
                        </div>

                        <!-- Account Services -->
                        <div class="service-card" onclick="window.location.href='personalize.php'">
                            <div class="service-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h6 class="service-title">Personalize</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='profile.php'">
                            <div class="service-icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <h6 class="service-title">My Profile</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                // window.location.href = 'index.html';
            }
            loadWalletBalance();
        });

        async function loadWalletBalance() {
            const token = localStorage.getItem('authToken');
            if (!token) return;

            try {
                const response = await fetch('api/get-wallet-balance.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                if (data.success) {
                    const balance = parseFloat(data.balance).toLocaleString('en-NG', {minimumFractionDigits: 2});
                    document.getElementById('headerWalletBalance').innerText = '₦' + balance;
                }
            } catch (e) {
                console.error(e);
            }
        }

        function logout() {
            if (confirm('Are you sure?')) {
                localStorage.removeItem('authToken');
                localStorage.removeItem('userData');
                window.location.href = 'index.html';
            }
        }
    </script>
</body>
</html>
