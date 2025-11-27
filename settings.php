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
    <title>Settings - Lildone Verification Services</title>
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
                    <h1 class="page-title">Settings</h1>
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
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>Account Settings</h5>
                            </div>
                            <div class="card-body">
                                <form id="accountSettingsForm">
                                    <div class="mb-3">
                                        <label for="currentPassword" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="newPassword" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="newPassword" placeholder="Enter new password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">
                                        Email Notifications
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="smsNotifications">
                                    <label class="form-check-label" for="smsNotifications">
                                        SMS Notifications
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="transactionAlerts" checked>
                                    <label class="form-check-label" for="transactionAlerts">
                                        Transaction Alerts
                                    </label>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Two-Factor Authentication</h6>
                                    <p class="text-muted">Add an extra layer of security to your account</p>
                                    <button type="button" class="btn btn-outline-primary" onclick="alert('Two-factor authentication setup coming soon!')">
                                        <i class="fas fa-lock me-2"></i>Enable 2FA
                                    </button>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h6>Active Sessions</h6>
                                    <p class="text-muted">Manage your active sessions across devices</p>
                                    <button type="button" class="btn btn-outline-danger" onclick="alert('Session management coming soon!')">
                                        <i class="fas fa-sign-out-alt me-2"></i>Sign Out All Devices
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Appearance</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="themeSelect" class="form-label">Theme</label>
                                    <select class="form-select" id="themeSelect">
                                        <option value="light" selected>Light</option>
                                        <option value="dark">Dark</option>
                                        <option value="auto">Auto (System)</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="saveTheme()">
                                    <i class="fas fa-save me-2"></i>Save Theme
                                </button>
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
        document.getElementById('accountSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields');
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New passwords do not match');
                return;
            }

            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters');
                return;
            }

            // TODO: Implement password change API call
            alert('Password change functionality coming soon!');
        });

        function saveNotificationSettings() {
            const emailNotifications = document.getElementById('emailNotifications').checked;
            const smsNotifications = document.getElementById('smsNotifications').checked;
            const transactionAlerts = document.getElementById('transactionAlerts').checked;

            // TODO: Save to backend
            alert('Notification preferences saved!');
        }

        function saveTheme() {
            const theme = document.getElementById('themeSelect').value;
            localStorage.setItem('theme', theme);
            alert('Theme preference saved!');
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
    </script>
</body>
</html>
