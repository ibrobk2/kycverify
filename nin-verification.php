<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';

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
    <title>NIN Verification - agentify Verification Services</title>

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

        .form-section {
            transition: all 0.3s ease;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
                    <h1 class="page-title">NIN Verification</h1>
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

                <!-- NIN Verification Form -->
                <div class="row">

                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">NIN Verification Form</h5>
                            </div>
                            <div class="card-body">
                                <form id="ninVerificationForm">
                                    <!-- Verification Type Selection -->
                                    <div class="mb-3">
                                        <label for="verificationType" class="form-label">Select Verification Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="verificationType" required>
                                            <option value="">Choose verification type</option>
                                            <option value="nin">Verify by NIN</option>
                                            <option value="phone">Verify by Phone Number</option>
                                            <option value="demographic">Demographic Search</option>
                                        </select>
                                    </div>

                                    <!-- Slip Type Selection (shown when verification type is selected) -->
                                    <div id="slipTypeSection" class="mb-3" style="display: none;">
                                        <label for="slipType" class="form-label">Select Slip Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="slipType" required>
                                            <option value="">Choose slip type</option>
                                            <option value="premium">Premium</option>
                                            <option value="regular">Regular</option>
                                            <option value="standard">Standard</option>
                                            <option value="vnin">VNIN</option>
                                        </select>
                                    </div>

                                    <!-- NIN Input Section -->
                                    <div id="ninInputSection" class="form-section" style="display: none;">
                                        <div class="mb-3">
                                            <label for="ninNumber" class="form-label">NIN Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="ninNumber" placeholder="Enter 11-digit NIN" maxlength="11" pattern="[0-9]{11}">
                                        </div>
                                    </div>

                                    <!-- Phone Input Section -->
                                    <div id="phoneInputSection" class="form-section" style="display: none;">
                                        <div class="mb-3">
                                            <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phoneNumber" placeholder="Enter phone number (11 digits)" maxlength="11" pattern="[0-9]{11}">
                                        </div>
                                    </div>

                                    <!-- Demographic Search Section -->
                                    <div id="demographicInputSection" class="form-section" style="display: none;">
                                        <div class="mb-3">
                                            <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="firstName" placeholder="Enter first name">
                                        </div>
                                        <div class="mb-3">
                                            <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="lastName" placeholder="Enter last name">
                                        </div>
                                        <div class="mb-3">
                                            <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="dob">
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="fas fa-search me-2"></i>Get NIN Slip
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                            <i class="fas fa-refresh me-2"></i>Reset Form
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">How to Use</h5>
                            </div>
                            <div class="card-body">
                                <ol class="list">
                                    <li class="mb-2"><strong>Select Verification Type</strong> - Choose from NIN, Phone Number, or Demographic Search</li>
                                    <li class="mb-2"><strong>Select Slip Type</strong> - Choose Premium, Regular, Standard, or VNIN</li>
                                    <li class="mb-2"><strong>Enter Required Information</strong> - Provide NIN, Phone Number, or demographic details</li>
                                    <li class="mb-2"><strong>Submit Form</strong> - Click "Get NIN Slip" to retrieve your document</li>
                                    <li class="mb-2"><strong>Download PDF</strong> - Your NIN slip will be automatically downloaded</li>
                                </ol>
                                <hr>
                                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Slip Types:</h6>
                                <ul class="list-unstyled small">
                                    <li><strong>Premium:</strong> Full detailed slip</li>
                                    <li><strong>Regular:</strong> Standard slip format</li>
                                    <li><strong>Standard:</strong> Basic slip format</li>
                                    <li><strong>VNIN:</strong> Virtual NIN slip</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Support</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">Need help with verification?</p>
                                <a href="https://wa.me/2349012345678" class="btn btn-success w-100">
                                    <i class="fab fa-whatsapp me-2"></i>Chat with Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/dashboard.js"></script>
    <script>
        // API Configuration
        // const API_KEY = "your_dataverify_api_key"; // Removed: Processed on backend
        // const ENDPOINTS = { ... }; // Removed: Handled by backend

        // Load wallet balance and service cost on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadWalletBalance();
            loadServiceCost();
            
            const verificationType = document.getElementById('verificationType');
            const slipTypeSection = document.getElementById('slipTypeSection');
            const slipType = document.getElementById('slipType');
            const ninInputSection = document.getElementById('ninInputSection');
            const phoneInputSection = document.getElementById('phoneInputSection');
            const demographicInputSection = document.getElementById('demographicInputSection');
            const form = document.getElementById('ninVerificationForm');

            // Handle verification type change
            verificationType.addEventListener('change', function() {
                const value = this.value;
                
                // Hide all input sections
                ninInputSection.style.display = 'none';
                phoneInputSection.style.display = 'none';
                demographicInputSection.style.display = 'none';
                
                // Reset slip type
                slipType.value = '';
                
                if (value === '') {
                    slipTypeSection.style.display = 'none';
                } else {
                    slipTypeSection.style.display = 'block';
                    
                    // Show appropriate input section
                    if (value === 'nin') {
                        ninInputSection.style.display = 'block';
                    } else if (value === 'phone') {
                        phoneInputSection.style.display = 'block';
                    } else if (value === 'demographic') {
                        demographicInputSection.style.display = 'block';
                    }
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Check balance before submitting
                const balanceText = document.getElementById('walletBalance').textContent;
                const costText = document.getElementById('serviceCost').textContent;
                const balance = parseFloat(balanceText.replace(/[^\d.-]/g, ''));
                const cost = parseFloat(costText.replace(/[^\d.-]/g, ''));

                if (balance < cost) {
                    showAlert('Insufficient wallet balance. Please fund your wallet first.', 'danger');
                    return;
                }

                if (validateForm()) {
                    submitVerification();
                }
            });

            // Form validation
            window.validateForm = function() {
                const verificationType = document.getElementById('verificationType').value;
                const slipType = document.getElementById('slipType').value;

                if (!verificationType) {
                    showAlert('Please select a verification type', 'danger');
                    return false;
                }

                if (!slipType) {
                    showAlert('Please select a slip type', 'danger');
                    return false;
                }

                // Validate specific fields based on verification type
                if (verificationType === 'nin') {
                    const ninNumber = document.getElementById('ninNumber').value;
                    
                    if (!ninNumber || ninNumber.length !== 11 || !/^\d{11}$/.test(ninNumber)) {
                        showAlert('Please enter a valid 11-digit NIN number', 'danger');
                        return false;
                    }
                }

                if (verificationType === 'phone') {
                    const phoneNumber = document.getElementById('phoneNumber').value;
                    
                    if (!phoneNumber || !/^\d{11}$/.test(phoneNumber)) {
                        showAlert('Please enter a valid 11-digit phone number', 'danger');
                        return false;
                    }
                }

                if (verificationType === 'demographic') {
                    const firstName = document.getElementById('firstName').value;
                    const lastName = document.getElementById('lastName').value;
                    const dob = document.getElementById('dob').value;
                    
                    if (!firstName.trim()) {
                        showAlert('Please enter your first name', 'danger');
                        return false;
                    }
                    
                    if (!lastName.trim()) {
                        showAlert('Please enter your last name', 'danger');
                        return false;
                    }
                    
                    if (!dob) {
                        showAlert('Please select your date of birth', 'danger');
                        return false;
                    }
                }

                return true;
            };
        });

        // Load wallet balance (moved outside DOMContentLoaded for global access)
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
                const response = await fetch('api/get-service-price.php?service=nin_verification');
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

        // Submit verification and call DataVerify API
        async function submitVerification() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            const verificationType = document.getElementById('verificationType').value;
            const slipType = document.getElementById('slipType').value;
            const token = localStorage.getItem('authToken');

            try {
                let payload = {
                    verification_type: verificationType,
                    slip_type: slipType
                };

                // Add type-specific data
                if (verificationType === 'nin') {
                    payload.nin = document.getElementById('ninNumber').value;
                } else if (verificationType === 'phone') {
                    payload.phone = document.getElementById('phoneNumber').value;
                } else if (verificationType === 'demographic') {
                    payload.first_name = document.getElementById('firstName').value;
                    payload.last_name = document.getElementById('lastName').value;
                    payload.dob = document.getElementById('dob').value;
                }

                // Call Backend API
                const response = await fetch('api/verify-nin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.status === 'success' && data.pdf_base64) {
                    // Decode and download PDF
                    const pdfData = base64ToBlob(data.pdf_base64);
                    const downloadLink = document.createElement('a');
                    downloadLink.href = URL.createObjectURL(pdfData);
                    
                    let filename = 'nin_slip.pdf';
                    if (verificationType === 'nin') {
                        filename = `nin_slip_${document.getElementById('ninNumber').value}.pdf`;
                    } else if (verificationType === 'phone') {
                        filename = `nin_slip_${document.getElementById('phoneNumber').value}.pdf`;
                    }
                    
                    downloadLink.download = filename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);

                    showAlert('NIN Slip retrieved successfully! File is downloading.', 'success');
                    
                    // Log the transaction
                    await logTransaction(verificationType, slipType, token);
                    
                    resetForm();
                    loadWalletBalance();
                } else {
                    showAlert(data.message || 'Failed to retrieve NIN Slip', 'danger');
                }
            } catch (error) {
                console.error('Error submitting verification:', error);
                showAlert('An error occurred while processing your request: ' + error.message, 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-search me-2"></i>Get NIN Slip';
            }
        }

        // Convert base64 to Blob
        function base64ToBlob(base64) {
            const binaryString = atob(base64);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            return new Blob([bytes], { type: 'application/pdf' });
        }

        // Log transaction to server
        async function logTransaction(verificationType, slipType, token) {
            try {
                await fetch('api/log-nin-verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        verification_type: verificationType,
                        slip_type: slipType,
                        timestamp: new Date().toISOString()
                    })
                });
            } catch (error) {
                console.error('Error logging transaction:', error);
            }
        }

        // Show alert messages
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const form = document.getElementById('ninVerificationForm');
            form.parentNode.insertBefore(alertDiv, form);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        // Reset form
        function resetForm() {
            const form = document.getElementById('ninVerificationForm');
            const slipTypeSection = document.getElementById('slipTypeSection');
            const ninInputSection = document.getElementById('ninInputSection');
            const phoneInputSection = document.getElementById('phoneInputSection');
            const demographicInputSection = document.getElementById('demographicInputSection');
            
            form.reset();
            slipTypeSection.style.display = 'none';
            ninInputSection.style.display = 'none';
            phoneInputSection.style.display = 'none';
            demographicInputSection.style.display = 'none';
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'index.html';
            }
        }
    </script>
</body>
</html>
