<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

// Basic session check
if (!isset($_SESSION['user_id'])) {
    // header("Location: index.html");
    // exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPE Clearance - Lildone Verification Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .service-header {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .form-control.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23dc3545' viewBox='-2 -2 7 7'%3e%3cpath stroke='%23dc3545' d='M0 0l3 3m0-3L0 3'/%3e%3ccircle r='.5'/%3e%3ccircle cx='3' r='.5'/%3e%3ccircle cy='3' r='.5'/%3e%3ccircle cx='3' cy='3' r='.5'/%3e%3c/svg%3E");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
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
                    <h1 class="page-title">IPE Clearance</h1>
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
                <!-- Wallet Balance Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-wallet me-2"></i>
                                    Wallet Balance & Service Cost
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-coins me-2"></i>Current Balance</h6>
                                            <h3 id="walletBalance">₦0.00</h3>
                                            <button class="btn btn-outline-success btn-sm" onclick="refreshWalletBalance()">
                                                <i class="fas fa-refresh me-1"></i>Refresh Balance
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-dollar-sign me-2"></i>Service Cost</h6>
                                            <h3 id="serviceCost">₦0.00</h3>
                                            <small class="text-muted">Cost will be deducted from your wallet upon successful submission</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-header text-white">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-4 fw-bold">IPE Clearance Service</h1>
                                <p class="lead">Clear IPE enrollment issues and retrieve your ID</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-file-alt fa-5x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <!-- Breadcrumb -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item">Services</li>
                                    <li class="breadcrumb-item active" aria-current="page">IPE Clearance</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- IPE Clearance Form -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>IPE Clearance Form</h5>
                                </div>
                                <div class="card-body">
                                    <form id="ipeClearanceForm" novalidate>
                                        <div class="mb-3">
                                            <label for="ipeCategory" class="form-label">Select IPEs Category <span class="text-danger">*</span></label>
                                            <select class="form-select" id="ipeCategory" name="ipeCategory" required>
                                                <option value="" selected disabled>Choose IPEs Category</option>
                                                <option value="new-enrollment">New Enrollment for ID Retrieval</option>
                                                <option value="inprocessing-error">Inprocessing Error</option>
                                                <option value="enrollment-being-processed">Enrollment is Still Being Process</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select an IPEs category.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="trackingId" class="form-label">Tracking Id <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="trackingId" name="trackingId" required placeholder="Enter Tracking Id">
                                            <div class="invalid-feedback">
                                                Please enter a tracking ID.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="remarks" class="form-label">Remarks</label>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter any remarks"></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Submit
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About IPE Clearance</h5>
                                </div>
                                <div class="card-body">
                                    <p>IPE Clearance helps you resolve enrollment issues with:</p>
                                    <ul>
                                        <li>New enrollment ID retrieval</li>
                                        <li>Processing errors</li>
                                        <li>Pending enrollments</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="dashboard.php" class="btn btn-outline-primary">
                                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                        </a>
                                        <a href="bvn-retrieval.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-university me-2"></i>BVN Retrieval
                                        </a>
                                        <a href="bvn-modification.php" class="btn btn-outline-info">
                                            <i class="fas fa-edit me-2"></i>BVN Modification
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-headset me-2"></i>Need Help?</h5>
                                </div>
                                <div class="card-body">
                                    <p>Contact our support team</p>
                                    <a href="https://wa.me/2349056124304" class="btn btn-success w-100" target="_blank">
                                        <i class="fab fa-whatsapp me-2"></i>Chat on WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        // Load wallet balance and service cost on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadWalletBalance();
            loadServiceCost();
        });

        // Load wallet balance
        async function loadWalletBalance() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                document.getElementById('walletBalance').textContent = '₦0.00';
                return;
            }

            try {
                const response = await fetch('api/get-wallet-balance.php', {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById('walletBalance').textContent = '₦' + parseFloat(data.balance).toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById('walletBalance').textContent = '₦0.00';
                }
            } catch (error) {
                console.error('Error loading wallet balance:', error);
                document.getElementById('walletBalance').textContent = '₦0.00';
            }
        }

        // Load service cost
        async function loadServiceCost() {
            try {
                const response = await fetch('api/get-service-price.php?service=IPE Clearance');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('serviceCost').textContent = '₦' + parseFloat(data.price).toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById('serviceCost').textContent = '₦0.00';
                }
            } catch (error) {
                console.error('Error loading service cost:', error);
                document.getElementById('serviceCost').textContent = '₦0.00';
            }
        }

        // Refresh wallet balance
        async function refreshWalletBalance() {
            await loadWalletBalance();
        }

        // Form validation
        (function () {
            'use strict';

            const form = document.getElementById('ipeClearanceForm');

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }

                // Submit form
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';

                setTimeout(() => {
                    alert('IPE Clearance form submitted successfully!');
                    form.reset();
                    form.classList.remove('was-validated');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit';
                }, 1500);
            }, false);
        })();

        function showNotifications() {
            alert('Notifications feature coming soon!');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                localStorage.removeItem('authToken');
                localStorage.removeItem('userData');
                window.location.href = 'index.html';
            }
        }
    </script>
</body>
</html>
