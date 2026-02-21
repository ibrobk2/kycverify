<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';

if (!isset($_SESSION['user_id'])) {
    // Allow JS to handle auth
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Airtime - agentify VTU Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .network-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .network-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .network-card.selected {
            border-color: #06b6d4;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(30, 58, 138, 0.1));
        }
        .network-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="page-title"><i class="fas fa-mobile-alt me-2"></i>Buy Airtime</h1>
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

            <div class="content-area">
                <!-- Wallet Balance -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-wallet me-2"></i>Wallet Balance</h6>
                                        <h3 id="walletBalance">₦0.00</h3>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-cyan" onclick="window.location.href='dashboard.php#fund-wallet'">
                                            <i class="fas fa-plus me-2"></i>Fund Wallet
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Network Selection -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Select Network</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3" id="networkGrid">
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="MTN">
                                            <div class="card-body">
                                                <div class="network-logo mb-2" style="background: #ffcc00; border-radius: 10px; padding: 10px;">
                                                    <h2 class="mb-0" style="color: #000;">MTN</h2>
                                                </div>
                                                <p class="mb-0 fw-bold">MTN</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="GLO">
                                            <div class="card-body">
                                                <div class="network-logo mb-2" style="background: #00a859; border-radius: 10px; padding: 10px;">
                                                    <h2 class="mb-0" style="color: #fff;">GLO</h2>
                                                </div>
                                                <p class="mb-0 fw-bold">Glo</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="AIRTEL">
                                            <div class="card-body">
                                                <div class="network-logo mb-2" style="background: #ed1c24; border-radius: 10px; padding: 10px;">
                                                    <h2 class="mb-0" style="color: #fff;">Airtel</h2>
                                                </div>
                                                <p class="mb-0 fw-bold">Airtel</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="9MOBILE">
                                            <div class="card-body">
                                                <div class="network-logo mb-2" style="background: #00a65a; border-radius: 10px; padding: 10px;">
                                                    <h2 class="mb-0" style="color: #fff; font-size: 1.5rem;">9mobile</h2>
                                                </div>
                                                <p class="mb-0 fw-bold">9mobile</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Airtime Purchase Details</h5>
                            </div>
                            <div class="card-body">
                                <form id="airtimeForm">
                                    <div class="mb-3">
                                        <label class="form-label">Selected Network <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="selectedNetwork" readonly placeholder="Select a network above">
                                    </div>
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phoneNumber" placeholder="08012345678" maxlength="11" required>
                                        <small class="text-muted">Enter 11-digit Nigerian phone number</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="amount" placeholder="100" min="50" max="50000" required>
                                        <small class="text-muted">Min: ₦50, Max: ₦50,000</small>
                                    </div>
                                    <div class="alert alert-info">
                                        <strong>Total Amount:</strong> ₦<span id="totalAmount">0.00</span>
                                        <br><small>Includes service charge</small>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <i class="fas fa-shopping-cart me-2"></i>Purchase Airtime
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Info</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Instant delivery</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>All Nigerian networks supported</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Secure transactions</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>24/7 support</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div id="recentTransactions">
                                    <p class="text-muted">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
        let selectedNetwork = null;
        const commissionPercent = 2; // Will be fetched from API

        document.addEventListener('DOMContentLoaded', function() {
            loadWalletBalance();
            loadRecentTransactions();

            // Network selection
            document.querySelectorAll('.network-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.network-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedNetwork = this.dataset.network;
                    document.getElementById('selectedNetwork').value = selectedNetwork;
                });
            });

            // Amount change
            document.getElementById('amount').addEventListener('input', calculateTotal);

            // Form submission
            document.getElementById('airtimeForm').addEventListener('submit', handleSubmit);
        });

        function calculateTotal() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const commission = (amount * commissionPercent) / 100;
            const total = amount + commission;
            document.getElementById('totalAmount').textContent = total.toFixed(2);
        }

        async function handleSubmit(e) {
            e.preventDefault();

            if (!selectedNetwork) {
                showAlert('Please select a network', 'danger');
                return;
            }

            const phone = document.getElementById('phoneNumber').value;
            const amount = parseFloat(document.getElementById('amount').value);

            if (!/^0\d{10}$/.test(phone)) {
                showAlert('Please enter a valid 11-digit phone number', 'danger');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            try {
                const token = localStorage.getItem('authToken');
                const response = await fetch('api/vtu-purchase-airtime.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        network: selectedNetwork,
                        phone: phone,
                        amount: amount
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Airtime purchase successful!', 'success');
                    document.getElementById('airtimeForm').reset();
                    document.getElementById('selectedNetwork').value = '';
                    selectedNetwork = null;
                    document.querySelectorAll('.network-card').forEach(c => c.classList.remove('selected'));
                    loadWalletBalance();
                    loadRecentTransactions();
                } else {
                    showAlert(data.message || 'Purchase failed', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Purchase Airtime';
            }
        }

        async function loadWalletBalance() {
            const token = localStorage.getItem('authToken');
            if (!token) return;

            try {
                const response = await fetch('api/get-wallet-balance.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('walletBalance').textContent = '₦' + parseFloat(data.balance).toLocaleString('en-NG', {minimumFractionDigits: 2});
                }
            } catch (error) {
                console.error('Error loading balance:', error);
            }
        }

        async function loadRecentTransactions() {
            const token = localStorage.getItem('authToken');
            if (!token) return;

            try {
                const response = await fetch('api/vtu-get-transactions.php?type=AIRTIME&limit=5', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                
                if (data.success && data.data.transactions.length > 0) {
                    const html = data.data.transactions.map(tx => `
                        <div class="mb-2 pb-2 border-bottom">
                            <small class="text-muted">${tx.network} - ${tx.phone_number}</small><br>
                            <strong>₦${tx.amount}</strong>
                            <span class="badge bg-${tx.status === 'SUCCESS' ? 'success' : tx.status === 'FAILED' ? 'danger' : 'warning'} float-end">${tx.status}</span>
                        </div>
                    `).join('');
                    document.getElementById('recentTransactions').innerHTML = html;
                } else {
                    document.getElementById('recentTransactions').innerHTML = '<p class="text-muted">No recent transactions</p>';
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.querySelector('.content-area').insertBefore(alertDiv, document.querySelector('.content-area').firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        }

        function logout() {
            if (confirm('Are you sure?')) {
                localStorage.removeItem('authToken');
                window.location.href = 'index.html';
            }
        }
    </script>
</body>
</html>
