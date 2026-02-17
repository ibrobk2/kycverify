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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>NIN Validation - Lildone Verification Services</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <style>
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

        .alert {
            margin-bottom: 1rem;
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
                    <h1 class="page-title">NIN Validation</h1>
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
                                <li><hr class="dropdown-divider" /></li>
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
                                <div id="balanceAlert" class="alert alert-danger" style="display: none;">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Insufficient Balance!</strong> Your wallet balance is not enough to process this request.
                                    Please <a href="dashboard.php#fund-wallet" class="alert-link">fund your wallet</a> first.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NIN Validation Form -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">NIN Validation Form</h5>
                            </div>
                            <div class="card-body">
                                <form id="ninValidationForm">
                                    <div class="mb-3">
                                        <label for="validationType" class="form-label">Select Validation Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="validationType" required aria-required="true">
                                            <option value="" selected disabled>Choose validation type</option>
                                            <option value="no-record">No Record Found</option>
                                            <option value="modification">Modification Validation</option>
                                        </select>
                                    </div>

                                    <div id="formNoRecord" class="d-none">
                                        <div class="mb-3">
                                            <label for="ninNumberNoRecord" class="form-label">NIN Number <span class="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="ninNumberNoRecord"
                                                name="ninNumberNoRecord"
                                                required
                                                pattern="\\d{11}"
                                                maxlength="11"
                                                minlength="11"
                                                aria-required="true"
                                                aria-describedby="ninHelpNoRecord"
                                                placeholder="Enter 11-digit NIN number"
                                            />
                                            <div id="ninHelpNoRecord" class="form-text">Please enter exactly 11 digits</div>
                                        </div>
                                    </div>

                                    <div id="formModification" class="d-none">
                                        <div class="mb-3">
                                            <label for="ninNumberModification" class="form-label">NIN Number <span class="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="ninNumberModification"
                                                name="ninNumberModification"
                                                required
                                                pattern="\\d{11}"
                                                maxlength="11"
                                                minlength="11"
                                                aria-required="true"
                                                aria-describedby="ninHelpModification"
                                                placeholder="Enter 11-digit NIN number"
                                            />
                                            <div id="ninHelpModification" class="form-text">Please enter exactly 11 digits</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="trackingId" class="form-label">Tracking Id <span class="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="trackingId"
                                                name="trackingId"
                                                required
                                                aria-required="true"
                                                placeholder="Enter your tracking ID"
                                            />
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Validate</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Support Button -->
    <div class="whatsapp-support">
        <a href="https://wa.me/2349056124304" target="_blank" class="whatsapp-btn">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
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
                    checkBalance();
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
                const response = await fetch('api/get-service-price.php?service=nin_validation');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('serviceCost').textContent = '₦' + parseFloat(data.price).toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    checkBalance();
                } else {
                    document.getElementById('serviceCost').textContent = '₦0.00';
                }
            } catch (error) {
                console.error('Error loading service cost:', error);
                document.getElementById('serviceCost').textContent = '₦0.00';
            }
        }

        // Check if balance is sufficient
        function checkBalance() {
            const balanceText = document.getElementById('walletBalance').textContent;
            const costText = document.getElementById('serviceCost').textContent;

            const balance = parseFloat(balanceText.replace(/[^\d.-]/g, ''));
            const cost = parseFloat(costText.replace(/[^\d.-]/g, ''));

            const alertDiv = document.getElementById('balanceAlert');

            if (balance < cost && cost > 0) {
                alertDiv.style.display = 'block';
            } else {
                alertDiv.style.display = 'none';
            }
        }

        // Refresh wallet balance
        async function refreshWalletBalance() {
            await loadWalletBalance();
        }

        // NIN Validation Form Logic
        const validationTypeSelect = document.getElementById('validationType');
        const formNoRecord = document.getElementById('formNoRecord');
        const formModification = document.getElementById('formModification');
        const submitBtn = document.querySelector('#ninValidationForm button[type="submit"]');
        const form = document.getElementById('ninValidationForm');

        // Validation type change handler
        validationTypeSelect.addEventListener('change', () => {
            const selected = validationTypeSelect.value;

            // Reset all forms
            formNoRecord.classList.add('d-none');
            formModification.classList.add('d-none');
            formNoRecord.querySelectorAll('input').forEach((input) => {
                input.value = '';
                input.classList.remove('is-invalid', 'is-valid');
            });
            formModification.querySelectorAll('input').forEach((input) => {
                input.value = '';
                input.classList.remove('is-invalid', 'is-valid');
            });

            // Show appropriate form
            if (selected === 'no-record') {
                formNoRecord.classList.remove('d-none');
            } else if (selected === 'modification') {
                formModification.classList.remove('d-none');
            }
        });

        // Real-time validation
        form.addEventListener('input', (e) => {
            if (e.target.matches('input[required]')) {
                validateField(e.target);
            }
        });

        // Field validation function
        function validateField(field) {
            const isValid = field.checkValidity();
            field.classList.toggle('is-valid', isValid);
            field.classList.toggle('is-invalid', !isValid);
            return isValid;
        }

        // Form submission handler
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const selected = validationTypeSelect.value;
            if (!selected) {
                showFeedback('Please select a validation type.', 'warning');
                validationTypeSelect.focus();
                return;
            }

            let isValid = true;
            let formData = {};

            if (selected === 'no-record') {
                const ninField = document.getElementById('ninNumberNoRecord');
                if (!validateField(ninField)) {
                    isValid = false;
                    ninField.focus();
                } else {
                    formData.ninNumber = ninField.value.trim();
                    formData.validationType = 'no-record';
                }
            } else if (selected === 'modification') {
                const ninField = document.getElementById('ninNumberModification');
                const trackingField = document.getElementById('trackingId');

                if (!validateField(ninField)) {
                    isValid = false;
                    ninField.focus();
                } else if (!validateField(trackingField)) {
                    isValid = false;
                    trackingField.focus();
                } else {
                    formData.ninNumber = ninField.value.trim();
                    formData.trackingId = trackingField.value.trim();
                    formData.validationType = 'modification';
                }
            }

            if (!isValid) {
                showFeedback('Please correct the highlighted fields.', 'danger');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Validating...';

            try {
                // Simulate API call - replace with actual API endpoint
                await new Promise((resolve) => setTimeout(resolve, 1500));

                showFeedback(`Validation submitted successfully! Type: ${selected}`, 'success');

                // Reset form after successful submission
                setTimeout(() => {
                    form.reset();
                    formNoRecord.classList.add('d-none');
                    formModification.classList.add('d-none');
                    form.querySelectorAll('.is-valid, .is-invalid').forEach((el) => {
                        el.classList.remove('is-valid', 'is-invalid');
                    });
                }, 2000);
            } catch (error) {
                showFeedback('An error occurred. Please try again.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Validate';
            }
        });

        // Feedback system
        function showFeedback(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const formCard = document.querySelector('.card-body');
            formCard.insertBefore(alertDiv, formCard.firstChild);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Utility functions with better UX
        function showNotifications() {
            showFeedback('Notifications feature coming soon!', 'info');
        }



        function showSettings() {
            showFeedback('Settings feature coming soon!', 'info');
        }

        // Enhanced logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                localStorage.removeItem('authToken');
                localStorage.removeItem('userData');
                window.location.href = 'index.html'; 
            }
            return false;
        }

        // Initialize form
        document.addEventListener('DOMContentLoaded', () => {
            // Set focus to validation type select
            validationTypeSelect.focus();
        });
    </script>
</body>
</html>
