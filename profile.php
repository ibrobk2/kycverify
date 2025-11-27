<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

// Basic session check
if (!isset($_SESSION['user_id'])) {
    // header("Location: index.html");
    // exit;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_data = null;

if ($user_id) {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Lildone Verification Services</title>
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
                    <h1 class="page-title">My Profile</h1>
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
                                <li><a class="dropdown-item" href="#" onclick="showSettings()">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="logout()">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                                </div>
                                <h4 id="profileName"><?php echo $user_data ? htmlspecialchars($user_data['name']) : 'Loading...'; ?></h4>
                                <p class="text-muted" id="profileEmail"><?php echo $user_data ? htmlspecialchars($user_data['email']) : ''; ?></p>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <div id="alertContainer"></div>
                                <form id="profileForm">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" value="<?php echo $user_data ? htmlspecialchars($user_data['name']) : ''; ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo $user_data ? htmlspecialchars($user_data['email']) : ''; ?>" readonly>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" value="<?php echo $user_data ? htmlspecialchars($user_data['phone']) : ''; ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="wallet" class="form-label">Wallet Balance</label>
                                        <input type="text" class="form-control" id="wallet" value="₦<?php echo $user_data ? number_format($user_data['wallet'], 2) : '0.00'; ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="created" class="form-label">Member Since</label>
                                        <input type="text" class="form-control" id="created" value="<?php echo $user_data ? date('F j, Y', strtotime($user_data['created_at'])) : ''; ?>" readonly>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="editBtn" onclick="toggleEditMode()">
                                            <i class="fas fa-edit me-2"></i>Edit Profile
                                        </button>
                                        <button type="submit" class="btn btn-success d-none" id="saveBtn">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                        <button type="button" class="btn btn-secondary d-none" id="cancelBtn" onclick="cancelEdit()">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </button>
                                    </div>
                                </form>
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
        let isEditMode = false;
        let originalData = {};

        // Load user profile from API
        async function loadUserProfile() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                window.location.href = 'index.html';
                return;
            }

            try {
                const response = await fetch('api/verify-token.php', {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success && data.user) {
                    const user = data.user;
                    
                    // Update all profile fields
                    document.getElementById('profileName').textContent = user.name;
                    document.getElementById('profileEmail').textContent = user.email;
                    document.getElementById('name').value = user.name;
                    document.getElementById('email').value = user.email;
                    document.getElementById('phone').value = user.phone;
                    document.getElementById('wallet').value = '₦' + parseFloat(user.wallet).toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    
                    // Format and display member since date
                    if (user.created_at) {
                        const date = new Date(user.created_at);
                        const options = { year: 'numeric', month: 'long', day: 'numeric' };
                        document.getElementById('created').value = date.toLocaleDateString('en-US', options);
                    }
                    
                    // Store original data
                    originalData = {
                        name: user.name,
                        phone: user.phone
                    };
                } else {
                    // Token invalid, redirect to login
                    localStorage.removeItem('authToken');
                    localStorage.removeItem('userData');
                    window.location.href = 'index.html';
                }
            } catch (error) {
                console.error('Error loading profile:', error);
                showAlert('Failed to load profile data', 'danger');
            }
        }

        // Store original values when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadUserProfile();
        });

        function toggleEditMode() {
            isEditMode = true;
            document.getElementById('name').removeAttribute('readonly');
            document.getElementById('phone').removeAttribute('readonly');
            
            document.getElementById('editBtn').classList.add('d-none');
            document.getElementById('saveBtn').classList.remove('d-none');
            document.getElementById('cancelBtn').classList.remove('d-none');
            
            document.getElementById('name').focus();
        }

        function cancelEdit() {
            isEditMode = false;
            document.getElementById('name').value = originalData.name;
            document.getElementById('phone').value = originalData.phone;
            
            document.getElementById('name').setAttribute('readonly', true);
            document.getElementById('phone').setAttribute('readonly', true);
            
            document.getElementById('editBtn').classList.remove('d-none');
            document.getElementById('saveBtn').classList.add('d-none');
            document.getElementById('cancelBtn').classList.add('d-none');
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
            if (!name) {
                showAlert('Name is required', 'danger');
                return;
            }
            
            if (!phone) {
                showAlert('Phone number is required', 'danger');
                return;
            }
            
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            try {
                const token = localStorage.getItem('authToken');
                const response = await fetch('api/update-profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ name, phone })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Profile updated successfully!', 'success');
                    
                    // Update original data
                    originalData = { name, phone };
                    
                    // Update display
                    document.getElementById('profileName').textContent = name;
                    document.getElementById('profileEmail').textContent = data.user.email;
                    
                    // Exit edit mode
                    cancelEdit();
                } else {
                    showAlert(data.message || 'Failed to update profile', 'danger');
                }
            } catch (error) {
                console.error('Profile update error:', error);
                showAlert('Network error. Please try again.', 'danger');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes';
            }
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
