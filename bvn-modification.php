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
    <title>BVN Modification - Lildone Verification Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .service-header {
            background: linear-gradient(135deg, #1a6aa5, #2b81b7);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dynamic-form {
            display: none;
        }
        .dynamic-form.active {
            display: block;
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
                    <h1 class="page-title">BVN Modification</h1>
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
                                            <small class="text-muted">Cost varies by modification type</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-header text-white py-4">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-4 fw-bold">BVN Modification</h1>
                                <p class="lead">Update and manage your Bank Verification Number (BVN) information and related services.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item">Services</li>
                                    <li class="breadcrumb-item active" aria-current="page">BVN Modification</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- BVN Modification Form -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-section">
                                <h4 class="mb-4">
                                    <i class="fas fa-edit me-2"></i>
                                    BVN Modification Form
                                </h4>
                                
                                <form id="bvnModificationForm">
                                    <!-- BVN Type Selection -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="bvnType" class="form-label">
                                                <strong>BVN Type *</strong>
                                            </label>
                                            <select class="form-select" id="bvnType" required>
                                                <option value="">Select BVN Type</option>
                                                <option value="agency">Agency - N6,000</option>
                                                <option value="access">Access Bank</option>
                                                <option value="gtb">Guaranty Trust Bank</option>
                                                <option value="firstbank">First Bank of Nigeria</option>
                                                <option value="zenith">Zenith Bank</option>
                                                <option value="uba">United Bank for Africa</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="modificationType" class="form-label">
                                                <strong>Select Modification Type *</strong>
                                            </label>
                                            <select class="form-select" id="modificationType" required>
                                                <option value="">Select Modification Type</option>
                                                <option value="change-name">Change of Name</option>
                                                <option value="change-dob">Change of Date of Birth</option>
                                                <option value="change-phone">Change of Phone Number</option>
                                                <option value="name-dob">Name and Date of Birth</option>
                                                <option value="phone-name">Phone and Name</option>
                                                <option value="dob-phone">Date of Birth and Phone Number</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Dynamic Forms Container -->
                                    <div id="dynamicFormsContainer">
                                        <!-- Change of Name Form -->
                                        <div class="dynamic-form" id="change-name-form">
                                            <h5 class="mb-3">Change of Name Details</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="bvnNumber-name" class="form-label">BVN Number *</label>
                                                    <input type="text" class="form-control" id="bvnNumber-name" maxlength="11" pattern="\d{11}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="ninNumber-name" class="form-label">NIN Number *</label>
                                                    <input type="text" class="form-control" id="ninNumber-name" maxlength="11" pattern="\d{11}" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="newFirstName" class="form-label">New First Name *</label>
                                                    <input type="text" class="form-control" id="newFirstName" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="newMiddleName" class="form-label">New Middle Name (Optional)</label>
                                                    <input type="text" class="form-control" id="newMiddleName">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="newLastName" class="form-label">New Last Name *</label>
                                                    <input type="text" class="form-control" id="newLastName" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Change of Date of Birth Form -->
                                        <div class="dynamic-form" id="change-dob-form">
                                            <h5 class="mb-3">Change of Date of Birth Details</h5>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="bvnNumber-dob" class="form-label">BVN Number *</label>
                                                    <input type="text" class="form-control" id="bvnNumber-dob" maxlength="11" pattern="\d{11}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="ninNumber-dob" class="form-label">NIN Number *</label>
                                                    <input type="text" class="form-control" id="ninNumber-dob" maxlength="11" pattern="\d{11}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="newDateOfBirth" class="form-label">New Date of Birth *</label>
                                                    <input type="date" class="form-control" id="newDateOfBirth" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Change of Phone Number Form -->
                                        <div class="dynamic-form" id="change-phone-form">
                                            <h5 class="mb-3">Change of Phone Number Details</h5>
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label for="bvnNumber-phone" class="form-label">BVN Number *</label>
                                                    <input type="text" class="form-control" id="bvnNumber-phone" maxlength="11" pattern="\d{11}" required>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label for="ninNumber-phone" class="form-label">NIN Number *</label>
                                                    <input type="text" class="form-control" id="ninNumber-phone" maxlength="11" pattern="\d{11}" required>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label for="fullName-phone" class="form-label">Full Name *</label>
                                                    <input type="text" class="form-control" id="fullName-phone" required>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label for="newPhoneNumber" class="form-label">New Phone Number *</label>
                                                    <input type="tel" class="form-control" id="newPhoneNumber" pattern="[0-9]{11}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success w-100 py-3">
                                                <i class="fas fa-paper-plane me-2"></i>
                                                Submit Modification Request
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modification History -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>
                                        Modification History
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="historyTable" class="table table-striped" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Reference</th>
                                                    <th>Modification Type</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- DataTables will populate this or show "No data available" -->
                                            </tbody>
                                        </table>
                                    </div>
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
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        // Load wallet balance and service cost on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadWalletBalance();
            loadServiceCost();
            
            const modificationType = document.getElementById('modificationType');
            const dynamicFormsContainer = document.getElementById('dynamicFormsContainer');
            
            // Hide all forms initially
            function hideAllForms() {
                const forms = dynamicFormsContainer.querySelectorAll('.dynamic-form');
                forms.forEach(form => form.classList.remove('active'));
            }
            
            // Show specific form based on selection
            function showForm(formId) {
                hideAllForms();
                const targetForm = document.getElementById(formId);
                if (targetForm) {
                    targetForm.classList.add('active');
                }
            }
            
            // Handle modification type change
            modificationType.addEventListener('change', function() {
                const selectedType = this.value;
                if (selectedType) {
                    showForm(selectedType + '-form');
                } else {
                    hideAllForms();
                }
            });
            
            // Form submission
            document.getElementById('bvnModificationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const selectedType = modificationType.value;
                if (!selectedType) {
                    alert('Please select a modification type');
                    return;
                }
                
                // Collect form data based on selected type
                const formData = new FormData();
                formData.append('modificationType', selectedType);
                formData.append('bvnType', document.getElementById('bvnType').value);
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                
                // TODO: Implement actual API call
                setTimeout(() => {
                    alert('BVN modification feature coming soon!');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Modification Request';
                }, 1500);
            });
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
                const response = await fetch('api/get-service-price.php?service=bvn_modification');
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
        
        // Initialize DataTable
        $(document).ready(function() {
            $('#historyTable').DataTable({
                "order": [[ 0, "desc" ]],
                "pageLength": 10,
                "responsive": true
            });
        });
    </script>
</body> 
</html>
