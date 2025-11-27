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
    <title>Birth Attestation - Lildone Verification Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .service-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
        }
        .requirement-badge {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin: 5px;
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
                    <h1 class="page-title">Birth Attestation</h1>
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
                                <h1 class="display-4 fw-bold">Birth Attestation Service</h1>
                                <p class="lead">Get your birth certificate attested for official use</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-baby fa-5x opacity-50"></i>
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
                                    <li class="breadcrumb-item active" aria-current="page">Birth Attestation</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Service Information -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About This Service</h5>
                                </div>
                                <div class="card-body">
                                    <p>Birth attestation is the process of verifying and certifying your birth certificate for official purposes. This service is essential for:</p>
                                    <ul>
                                        <li>International travel and visa applications</li>
                                        <li>Educational admissions abroad</li>
                                        <li>Employment verification</li>
                                        <li>Legal proceedings</li>
                                        <li>Government documentation</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Application Form -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Birth Attestation Application</h5>
                                </div>
                                <div class="card-body">
                                    <form id="birthAttestationForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="fullName" class="form-label">Full Name (as on Birth Certificate) *</label>
                                                <input type="text" class="form-control" id="fullName" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="dateOfBirth" class="form-label">Date of Birth *</label>
                                                <input type="date" class="form-control" id="dateOfBirth" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="placeOfBirth" class="form-label">Place of Birth *</label>
                                                <input type="text" class="form-control" id="placeOfBirth" placeholder="City, State" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="certificateNumber" class="form-label">Birth Certificate Number *</label>
                                                <input type="text" class="form-control" id="certificateNumber" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="fatherName" class="form-label">Father's Full Name *</label>
                                                <input type="text" class="form-control" id="fatherName" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="motherName" class="form-label">Mother's Full Name *</label>
                                                <input type="text" class="form-control" id="motherName" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email Address *</label>
                                                <input type="email" class="form-control" id="email" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">Phone Number *</label>
                                                <input type="tel" class="form-control" id="phone" pattern="[0-9]{11}" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="purposeOfAttestation" class="form-label">Purpose of Attestation *</label>
                                            <select class="form-select" id="purposeOfAttestation" required>
                                                <option value="">Select purpose</option>
                                                <option value="visa">Visa Application</option>
                                                <option value="education">Educational Purpose</option>
                                                <option value="employment">Employment</option>
                                                <option value="legal">Legal Proceedings</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="birthCertificate" class="form-label">Upload Birth Certificate *</label>
                                            <input type="file" class="form-control" id="birthCertificate" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max 5MB)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="additionalDocuments" class="form-label">Additional Documents (Optional)</label>
                                            <input type="file" class="form-control" id="additionalDocuments" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                            <small class="text-muted">Upload any supporting documents if required</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="specialInstructions" class="form-label">Special Instructions (Optional)</label>
                                            <textarea class="form-control" id="specialInstructions" rows="3" placeholder="Any special requirements or instructions..."></textarea>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-paper-plane me-2"></i>Submit Application
                                            </button>
                                            <button type="reset" class="btn btn-outline-secondary">
                                                <i class="fas fa-redo me-2"></i>Reset Form
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Information -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Requirements</h5>
                                </div>
                                <div class="card-body">
                                    <div class="requirement-badge">Original Birth Certificate</div>
                                    <div class="requirement-badge">Valid ID</div>
                                    <div class="requirement-badge">Passport Photograph</div>
                                    <div class="requirement-badge">Application Form</div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Processing Time</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-card">
                                        <h6><i class="fas fa-bolt text-warning me-2"></i>Express Service</h6>
                                        <p class="mb-0">2-3 business days</p>
                                    </div>
                                    <div class="info-card">
                                        <h6><i class="fas fa-calendar text-primary me-2"></i>Standard Service</h6>
                                        <p class="mb-0">5-7 business days</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-headset me-2"></i>Need Help?</h5>
                                </div>
                                <div class="card-body">
                                    <p>Our support team is here to assist you</p>
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
                const response = await fetch('api/get-service-price.php?service=Birth Attestation');
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

        // Form submission
        document.getElementById('birthAttestationForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            // TODO: Implement actual API call
            setTimeout(() => {
                alert('Birth attestation application submitted successfully! You will receive a confirmation email shortly.');
                this.reset();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Application';
            }, 1500);
        });

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
