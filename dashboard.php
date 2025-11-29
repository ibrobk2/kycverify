<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';

// Check if user is logged in (Basic check, can be improved with JWT/Session validation)
// For now, we rely on frontend token, but for PHP pages, we should use session.
// If session not set, redirect to login (or handle via JS if using token only).
// Let's assume we want to transition to PHP sessions for security.

if (!isset($_SESSION['user_id'])) {
    // If no session, maybe redirect to login.html
    // header("Location: index.html");
    // exit;
    // For now, let's allow it to load and JS will handle auth check if session is missing
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$wallet_balance = 0.00;
$virtual_account = null;

if ($user_id) {
    $walletHelper = new WalletHelper();
    $wallet_balance = $walletHelper->getBalance($user_id);

    // Fetch Virtual Account Details
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT virtual_account_number, bank_name, account_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data && $user_data['virtual_account_number']) {
        $virtual_account = $user_data;
    } else {
        // Generate Virtual Account if not exists (Lazy generation)
        // In a real app, this might be done async or on signup.
        // For now, we just show a "Generate" button or placeholder.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lildone Verification Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="page-title" id="pageTitle">Dashboard</h1>
                    <div class="header-actions">
                        <button class="btn btn-outline-primary me-2" onclick="showNotifications()">
                            <i class="fas fa-bell"></i>
                        </button>
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

            <div class="content-area" id="contentArea">
                <!-- Dashboard Content -->
                <div id="dashboardContent">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="summary-card balance-card">
                                <div class="card-content">
                                    <div class="card-info">
                                        <p class="card-label">WALLET BALANCE</p>
                                        <h2 class="card-value" id="walletBalance">₦<?php echo number_format($wallet_balance, 2); ?></h2>
                                        <p class="card-description">Current available funds</p>
                                        <button class="btn btn-cyan" onclick="showFundWallet()">Fund Wallet</button>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Virtual Account Card -->
                        <div class="col-md-6">
                            <div class="summary-card bg-white text-dark border">
                                <div class="card-content">
                                    <div class="card-info">
                                        <p class="card-label text-muted">VIRTUAL ACCOUNT</p>
                                        <?php if ($virtual_account): ?>
                                            <h3 class="mb-1 text-primary"><?php echo htmlspecialchars($virtual_account['virtual_account_number']); ?></h3>
                                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($virtual_account['bank_name']); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars($virtual_account['account_name']); ?></small>
                                            <div class="mt-2">
                                                <small class="text-success"><i class="fas fa-check-circle"></i> Active</small>
                                            </div>
                                        <?php else: ?>
                                            <h4 class="text-muted">No Account Yet</h4>
                                            <p class="small">Generate a dedicated account for instant funding.</p>
                                            <button class="btn btn-outline-primary btn-sm" onclick="generateVirtualAccount()">Generate Account</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-icon text-primary">
                                        <i class="fas fa-university"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services Grid -->
                    <div class="services-grid">
                        <div class="service-card" onclick="window.location.href='nin-verification.php'">
                            <div class="service-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h6 class="service-title">NIN Verification</h6>
                        </div>
                        
                        <div class="service-card" onclick="window.location.href='bvn-retrieval.php'">
                            <div class="service-icon">
                                <i class="fas fa-search-plus"></i>
                            </div>
                            <h6 class="service-title">BVN Retrieval</h6>
                        </div>
                        
                        <div class="service-card" onclick="window.location.href='nin-validation.php'">
                            <div class="service-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h6 class="service-title">NIN Validation</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='bvn-modification.php'">
                            <div class="service-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <h6 class="service-title">BVN Modification</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='birth-attestation.php'">
                            <div class="service-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h6 class="service-title">Birth Attestation</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='personalize.php'">
                            <div class="service-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h6 class="service-title">Personalize</h6>
                        </div>

                        <div class="service-card" onclick="window.location.href='ipe-clearance.php'">
                            <div class="service-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h6 class="service-title">IPE Clearance</h6>
                        </div>

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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Check auth on load
        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                // window.location.href = 'index.html';
            }
            
            // Always load wallet balance from API to ensure it's current
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
                    document.getElementById('walletBalance').innerText = '₦' + parseFloat(data.balance).toLocaleString('en-NG', {minimumFractionDigits: 2});
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function generateVirtualAccount() {
            const token = localStorage.getItem('authToken');
            if (!token) return;
            
            if (!confirm('Generate a new virtual account?')) return;

            try {
                // Call API to generate account (Need to implement this endpoint)
                alert('Feature coming soon: API endpoint for account generation.');
            } catch (e) {
                console.error(e);
            }
        }

        function logout() {
            if (confirm('Are you sure?')) {
                localStorage.removeItem('authToken');
                localStorage.removeItem('userData');
                // Also destroy PHP session via an endpoint if needed
                window.location.href = 'index.html';
            }
        }
    </script>
</body>
</html>
