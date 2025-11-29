<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
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
        
        .stat-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users me-2"></i>User Management</h2>
                    <div>
                        <button class="btn btn-success me-2" onclick="exportUsers()">
                            <i class="fas fa-file-export me-2"></i>Export CSV
                        </button>
                        <button class="btn btn-primary" onclick="refreshUsers()">
                            <i class="fas fa-refresh me-2"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, or phone...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                            <div class="col-md-3 text-end">
                                <span class="text-muted" id="userCount">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Wallet</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <i class="fas fa-spinner fa-spin me-2"></i>Loading users...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav id="paginationContainer" style="display: none;">
                            <ul class="pagination justify-content-center" id="pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Management Modal -->
    <div class="modal fade" id="walletModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">User: <strong id="walletUserName"></strong></label>
                        <p class="text-muted">Current Balance: <strong id="walletCurrentBalance">₦0.00</strong></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select class="form-select" id="walletAction">
                            <option value="credit">Credit Wallet</option>
                            <option value="debit">Debit Wallet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <input type="number" class="form-control" id="walletAmount" min="1" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details/Reason</label>
                        <textarea class="form-control" id="walletDetails" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processWalletAction()">Process</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminToken = null;
        let currentPage = 1;
        let currentUserId = null;
        let walletModal;

        document.addEventListener('DOMContentLoaded', function() {
            adminToken = localStorage.getItem('adminToken');
            if (!adminToken) {
                window.location.href = 'login.html';
                return;
            }

            walletModal = new bootstrap.Modal(document.getElementById('walletModal'));
            loadUsers();

            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    localStorage.removeItem('adminToken');
                    window.location.href = 'login.html';
                }
            });

            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') applyFilters();
            });
        });

        async function loadUsers(page = 1) {
            currentPage = page;
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;

            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 20,
                    ...(search && { search }),
                    ...(status && { status })
                });

                const response = await fetch(`../api/admin/users.php?${params}`, {
                    headers: { 'Authorization': 'Bearer ' + adminToken }
                });

                const result = await response.json();

                if (result.success) {
                    displayUsers(result.data.users);
                    displayPagination(result.data.pagination);
                    document.getElementById('userCount').textContent = 
                        `Showing ${result.data.users.length} of ${result.data.pagination.total} users`;
                } else {
                    showError('Failed to load users: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error loading users');
            }
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No users found</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>${user.phone || 'N/A'}</td>
                    <td>₦${parseFloat(user.wallet).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
                    <td><span class="badge bg-${getStatusColor(user.status)}">${user.status}</span></td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success" onclick="manageWallet(${user.id}, '${user.name}', ${user.wallet})" title="Manage Wallet">
                                <i class="fas fa-wallet"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="toggleStatus(${user.id}, '${user.status}')" title="Change Status">
                                <i class="fas fa-toggle-on"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function displayPagination(pagination) {
            const container = document.getElementById('paginationContainer');
            const paginationEl = document.getElementById('pagination');

            if (pagination.total_pages <= 1) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            let html = '';

            html += `<li class="page-item ${pagination.page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadUsers(${pagination.page - 1}); return false;">Previous</a>
            </li>`;

            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    html += `<li class="page-item ${i === pagination.page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadUsers(${i}); return false;">${i}</a>
                    </li>`;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            html += `<li class="page-item ${pagination.page === pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadUsers(${pagination.page + 1}); return false;">Next</a>
            </li>`;

            paginationEl.innerHTML = html;
        }

        function getStatusColor(status) {
            const colors = { 'active': 'success', 'inactive': 'secondary', 'suspended': 'danger' };
            return colors[status] || 'secondary';
        }

        function applyFilters() {
            loadUsers(1);
        }

        function refreshUsers() {
            loadUsers(currentPage);
        }

        function manageWallet(userId, userName, currentBalance) {
            currentUserId = userId;
            document.getElementById('walletUserName').textContent = userName;
            document.getElementById('walletCurrentBalance').textContent = '₦' + parseFloat(currentBalance).toLocaleString('en-NG', {minimumFractionDigits: 2});
            document.getElementById('walletAmount').value = '';
            document.getElementById('walletDetails').value = '';
            walletModal.show();
        }

        async function processWalletAction() {
            const action = document.getElementById('walletAction').value;
            const amount = parseFloat(document.getElementById('walletAmount').value);
            const details = document.getElementById('walletDetails').value;

            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            if (!details.trim()) {
                alert('Please provide details/reason');
                return;
            }

            try {
                const response = await fetch('../api/admin/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify({
                        action: action + '_wallet',
                        user_id: currentUserId,
                        amount: amount,
                        details: details
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Wallet ' + action + ' successful!\nNew balance: ₦' + result.new_balance.toLocaleString('en-NG', {minimumFractionDigits: 2}));
                    walletModal.hide();
                    loadUsers(currentPage);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error processing wallet action');
            }
        }

        async function toggleStatus(userId, currentStatus) {
            const statuses = ['active', 'inactive', 'suspended'];
            const newStatus = prompt(`Change user status to:\n1. active\n2. inactive\n3. suspended\n\nCurrent: ${currentStatus}\nEnter new status:`);

            if (!newStatus || !statuses.includes(newStatus.toLowerCase())) {
                return;
            }

            try {
                const response = await fetch('../api/admin/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        user_id: userId,
                        status: newStatus.toLowerCase()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('User status updated successfully!');
                    loadUsers(currentPage);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating status');
            }
        }

        function exportUsers() {
            window.location.href = `../api/admin/export.php?type=users&token=${adminToken}`;
        }

        function showError(message) {
            document.getElementById('usersTableBody').innerHTML = `
                <tr><td colspan="8" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </td></tr>
            `;
        }
    </script>
</body>
</html>
