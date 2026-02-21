<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';

// Authentication check handled by JS (consistent with other pages)
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
    <title>NIN Modification - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
        .modification-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 120px);
            padding: 20px;
        }
        
        .mod-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px;
            padding: 40px;
        }
        
        .mod-title {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 28px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-select, .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 16px;
            margin-bottom: 20px;
            color: #475569;
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .btn-verify {
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 600;
            font-size: 18px;
            width: 100%;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .btn-verify:hover {
            background: #152b6d;
            color: white;
        }
        
        .btn-verify:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .alert-floating {
            margin-top: 20px;
        }
        
        .cost-display {
            background: #f8fafc;
            border: 1px dashed #1e3a8a;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
            color: #1e3a8a;
            font-size: 18px;
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
                    <h1 class="page-title">NIN Modification</h1>
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
                <div class="modification-container">
                    <div class="mod-card">
                        <h2 class="mod-title">NIN Modifications</h2>
                        
                        <form id="ninModForm">
                            <label class="form-label" for="modType">Select Modification Type:</label>
                            <select class="form-select" id="modType" required>
                                <option value="change-name">Change Name</option>
                                <option value="change-dob">Change Date of Birth</option>
                                <option value="change-address">Change Address</option>
                                <option value="change-phone">Change Phone Number</option>
                            </select>
                            
                            <div class="cost-display" id="costDisplay">
                                Service Cost: ₦6,000.00
                            </div>
                            
                            <input type="text" class="form-control" id="ninNumber" placeholder="11-digit NIN" maxlength="11" pattern="\d{11}" required>
                            
                            <!-- Dynamic Details Fields -->
                            <div id="detailsFields">
                                <!-- Change Name Fields -->
                                <div id="nameFields" class="d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Old Full Name</label>
                                            <input type="text" class="form-control" id="oldName" placeholder="As it is on NIN">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Full Name</label>
                                            <input type="text" class="form-control" id="newName" placeholder="Correct Name">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Change DOB Fields -->
                                <div id="dobFields" class="d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Old Date of Birth</label>
                                            <input type="date" class="form-control" id="oldDob">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Date of Birth</label>
                                            <input type="date" class="form-control" id="newDob">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Change Address Fields -->
                                <div id="addressFields" class="d-none">
                                    <label class="form-label">New Address Details</label>
                                    <textarea class="form-control" id="newAddress" rows="2" placeholder="Enter new address details"></textarea>
                                </div>
                                
                                <!-- Change Phone Fields -->
                                <div id="phoneFields" class="d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Old Phone Number</label>
                                            <input type="tel" class="form-control" id="oldPhone" placeholder="Previous Number">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Phone Number</label>
                                            <input type="tel" class="form-control" id="newPhone" placeholder="New Number">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-verify" id="verifyBtn">Submit Modification</button>
                        </form>
                        
                        <div id="feedback" class="alert-floating"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                // window.location.href = 'index.html';
            }
            loadWalletBalance();
            
            const modTypeSelect = document.getElementById('modType');
            const costDisplay = document.getElementById('costDisplay');
            const nameFields = document.getElementById('nameFields');
            const dobFields = document.getElementById('dobFields');
            const addressFields = document.getElementById('addressFields');
            const phoneFields = document.getElementById('phoneFields');
            
            const costs = {
                'change-name': 6000,
                'change-dob': 40000,
                'change-address': 6000,
                'change-phone': 6000
            };
            
            function updateFieldsAndCost() {
                const type = modTypeSelect.value;
                const cost = costs[type] || 0;
                costDisplay.innerText = `Service Cost: ₦${cost.toLocaleString('en-NG', {minimumFractionDigits: 2})}`;
                
                // Hide all detail fields
                nameFields.classList.add('d-none');
                dobFields.classList.add('d-none');
                addressFields.classList.add('d-none');
                phoneFields.classList.add('d-none');
                
                // Show relevant detail fields
                if (type === 'change-name') nameFields.classList.remove('d-none');
                else if (type === 'change-dob') dobFields.classList.remove('d-none');
                else if (type === 'change-address') addressFields.classList.remove('d-none');
                else if (type === 'change-phone') phoneFields.classList.remove('d-none');
            }
            
            modTypeSelect.addEventListener('change', updateFieldsAndCost);
            updateFieldsAndCost();
            
            const form = document.getElementById('ninModForm');
            const verifyBtn = document.getElementById('verifyBtn');
            const feedback = document.getElementById('feedback');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const selectedType = modTypeSelect.value;
                const modTypeText = modTypeSelect.options[modTypeSelect.selectedIndex].text;
                const nin = document.getElementById('ninNumber').value;
                
                // Collect specific details
                let extraDetails = {};
                if (selectedType === 'change-name') {
                    extraDetails = { old_name: document.getElementById('oldName').value, new_name: document.getElementById('newName').value };
                } else if (selectedType === 'change-dob') {
                    extraDetails = { old_dob: document.getElementById('oldDob').value, new_dob: document.getElementById('newDob').value };
                } else if (selectedType === 'change-address') {
                    extraDetails = { new_address: document.getElementById('newAddress').value };
                } else if (selectedType === 'change-phone') {
                    extraDetails = { old_phone: document.getElementById('oldPhone').value, new_phone: document.getElementById('newPhone').value };
                }
                
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                feedback.innerHTML = '';
                
                try {
                    const response = await fetch('api/verify-nin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify({
                            verification_type: 'modification',
                            nin: nin,
                            tracking_id: 'MOD-' + Date.now(),
                            modification_type: modTypeText,
                            details: extraDetails
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        feedback.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                        form.reset();
                        loadWalletBalance();
                    } else {
                        feedback.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                    }
                } catch (e) {
                    console.error(e);
                    feedback.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                } finally {
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = 'Verify NIN';
                }
            });
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
