<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --cyan: #06b6d4;
        }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-blue), #1e293b);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <h2 class="mb-4"><i class="fas fa-cog me-2"></i>Settings</h2>

                <div class="row">
                    <!-- Profile Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Profile Settings</h5>
                            </div>
                            <div class="card-body">
                                <form id="profileForm">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" id="adminName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="adminEmail" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" id="adminRole" readonly disabled>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form id="passwordForm">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="currentPassword" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="newPassword" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                                    </div>
                                    <button type="submit" class="btn btn-warning">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminToken = null;

        document.addEventListener('DOMContentLoaded', function() {
            adminToken = localStorage.getItem('adminToken');
            if (!adminToken) {
                window.location.href = 'login.html';
                return;
            }

            loadSettings();

            document.getElementById('profileForm').addEventListener('submit', updateProfile);
            document.getElementById('passwordForm').addEventListener('submit', changePassword);
        });

        async function loadSettings() {
            try {
                const response = await fetch('../api/admin/settings.php', {
                    headers: { 'Authorization': 'Bearer ' + adminToken }
                });
                const result = await response.json();

                if (result.success) {
                    const profile = result.data.profile;
                    document.getElementById('adminName').value = profile.name;
                    document.getElementById('adminEmail').value = profile.email;
                    document.getElementById('adminRole').value = profile.role || 'Admin';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error loading settings');
            }
        }

        async function updateProfile(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.textContent;
            btn.textContent = 'Updating...';
            btn.disabled = true;

            try {
                const response = await fetch('../api/admin/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify({
                        action: 'update_profile',
                        name: document.getElementById('adminName').value,
                        email: document.getElementById('adminEmail').value
                    })
                });
                const result = await response.json();

                if (result.success) {
                    alert('Profile updated successfully');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating profile');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }

        async function changePassword(e) {
            e.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;

            if (newPass !== confirmPass) {
                alert('New passwords do not match');
                return;
            }

            const btn = e.target.querySelector('button');
            const originalText = btn.textContent;
            btn.textContent = 'Changing...';
            btn.disabled = true;

            try {
                const response = await fetch('../api/admin/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify({
                        action: 'change_password',
                        current_password: current,
                        new_password: newPass
                    })
                });
                const result = await response.json();

                if (result.success) {
                    alert('Password changed successfully');
                    e.target.reset();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error changing password');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
