<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Data - Lildone VTU Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .network-card, .plan-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .network-card:hover, .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .network-card.selected, .plan-card.selected {
            border-color: #06b6d4;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(30, 58, 138, 0.1));
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="page-title"><i class="fas fa-wifi me-2"></i>Buy Data</h1>
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
                                <h5 class="mb-0">Step 1: Select Network</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3" id="networkGrid">
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="MTN">
                                            <div class="card-body">
                                                <div style="background: #ffcc00; border-radius: 10px; padding: 20px;">
                                                    <h3 class="mb-0" style="color: #000;">MTN</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="GLO">
                                            <div class="card-body">
                                                <div style="background: #00a859; border-radius: 10px; padding: 20px;">
                                                    <h3 class="mb-0" style="color: #fff;">GLO</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="AIRTEL">
                                            <div class="card-body">
                                                <div style="background: #ed1c24; border-radius: 10px; padding: 20px;">
                                                    <h3 class="mb-0" style="color: #fff;">Airtel</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card network-card text-center" data-network="9MOBILE">
                                            <div class="card-body">
                                                <div style="background: #00a65a; border-radius: 10px; padding: 20px;">
                                                    <h3 class="mb-0" style="color: #fff; font-size: 1.2rem;">9mobile</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Plans -->
                        <div class="card mb-4" id="plansCard" style="display: none;">
                            <div class="card-header">
                                <h5 class="mb-0">Step 2: Select Data Plan</h5>
                            </div>
                            <div class="card-body">
                                <div id="plansLoading" class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading plans...</p>
                                </div>
                                <div id="plansGrid" class="row g-3" style="display: none;"></div>
                            </div>
                        </div>

                        <!-- Purchase Form -->
                        <div class="card" id="purchaseCard" style="display: none;">
                            <div class="card-header">
                                <h5 class="mb-0">Step 3: Complete Purchase</h5>
                            </div>
                            <div class="card-body">
                                <form id="dataForm">
                                    <div class="mb-3">
                                        <label class="form-label">Selected Plan</label>
                                        <input type="text" class="form-control" id="selectedPlan" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phoneNumber" placeholder="08012345678" maxlength="11" required>
                                    </div>
                                    <div class="alert alert-info">
                                        <strong>Total Amount:</strong> ₦<span id="totalAmount">0.00</span>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <i class="fas fa-shopping-cart me-2"></i>Purchase Data
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
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Instant activation</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>All networks supported</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Best prices</li>
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
        let selectedPlanId = null;
        let selectedPlanData = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadWalletBalance();
            loadRecentTransactions();

            // Network selection
            document.querySelectorAll('.network-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.network-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedNetwork = this.dataset.network;
                    loadDataPlans(selectedNetwork);
                });
            });

            // Form submission
            document.getElementById('dataForm').addEventListener('submit', handleSubmit);
        });

        async function loadDataPlans(network) {
            document.getElementById('plansCard').style.display = 'block';
            document.getElementById('plansLoading').style.display = 'block';
            document.getElementById('plansGrid').style.display = 'none';
            document.getElementById('purchaseCard').style.display = 'none';

            try {
                const token = localStorage.getItem('authToken');
                const response = await fetch(`api/vtu-get-data-plans.php?network=${network}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();

                if (data.success && data.data.plans) {
                    displayPlans(data.data.plans);
                } else {
                    showAlert('Failed to load data plans', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error loading plans', 'danger');
            }
        }

        function displayPlans(plans) {
            const grid = document.getElementById('plansGrid');
            grid.innerHTML = plans.map(plan => `
                <div class="col-md-6">
                    <div class="card plan-card" data-plan='${JSON.stringify(plan)}'>
                        <div class="card-body">
                            <h6 class="mb-1">${plan.data_amount}</h6>
                            <p class="mb-1 text-muted small">${plan.plan_name}</p>
                            <p class="mb-0"><strong>₦${parseFloat(plan.price).toLocaleString()}</strong></p>
                            <small class="text-muted">${plan.validity}</small>
                        </div>
                    </div>
                </div>
            `).join('');

            document.getElementById('plansLoading').style.display = 'none';
            document.getElementById('plansGrid').style.display = 'flex';

            // Add click handlers
            document.querySelectorAll('.plan-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedPlanData = JSON.parse(this.dataset.plan);
                    selectedPlanId = selectedPlanData.plan_id;
                    document.getElementById('selectedPlan').value = `${selectedPlanData.data_amount} - ₦${selectedPlanData.price}`;
                    document.getElementById('totalAmount').textContent = parseFloat(selectedPlanData.price).toFixed(2);
                    document.getElementById('purchaseCard').style.display = 'block';
                });
            });
        }

        async function handleSubmit(e) {
            e.preventDefault();

            if (!selectedNetwork || !selectedPlanId) {
                showAlert('Please select network and plan', 'danger');
                return;
            }

            const phone = document.getElementById('phoneNumber').value;
            if (!/^0\d{10}$/.test(phone)) {
                showAlert('Please enter a valid 11-digit phone number', 'danger');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            try {
                const token = localStorage.getItem('authToken');
                const response = await fetch('api/vtu-purchase-data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        network: selectedNetwork,
                        phone: phone,
                        plan_id: selectedPlanId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Data purchase successful!', 'success');
                    document.getElementById('dataForm').reset();
                    selectedNetwork = null;
                    selectedPlanId = null;
                    document.querySelectorAll('.network-card, .plan-card').forEach(c => c.classList.remove('selected'));
                    document.getElementById('plansCard').style.display = 'none';
                    document.getElementById('purchaseCard').style.display = 'none';
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
                submitBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Purchase Data';
            }
        }

        async function loadRecentTransactions() {
            const token = localStorage.getItem('authToken');
            if (!token) return;

            try {
                const response = await fetch('api/vtu-get-transactions.php?type=DATA&limit=5', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                
                if (data.success && data.data.transactions.length > 0) {
                    const html = data.data.transactions.map(tx => `
                        <div class="mb-2 pb-2 border-bottom">
                            <small class="text-muted">${tx.network} - ${tx.plan_name}</small><br>
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
                console.error('Error:', error);
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
