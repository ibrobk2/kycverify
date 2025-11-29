<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Admin Panel</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-exchange-alt me-2"></i>Transaction Monitoring</h2>
                    <div>
                        <button class="btn btn-success me-2" onclick="exportTransactions()">
                            <i class="fas fa-file-export me-2"></i>Export CSV
                        </button>
                        <button class="btn btn-primary" onclick="loadTransactions()">
                            <i class="fas fa-refresh me-2"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select" id="typeFilter">
                                    <option value="">All Types</option>
                                    <option value="wallet">Wallet</option>
                                    <option value="vtu">VTU</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="startDate">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="endDate">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Details</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <i class="fas fa-spinner fa-spin me-2"></i>Loading transactions...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <nav id="paginationContainer" style="display: none;">
                            <ul class="pagination justify-content-center" id="pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminToken = null;
        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', function() {
            adminToken = localStorage.getItem('adminToken');
            if (!adminToken) {
                window.location.href = 'login.html';
                return;
            }

            loadTransactions();

            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    localStorage.removeItem('adminToken');
                    window.location.href = 'login.html';
                }
            });
        });

        async function loadTransactions(page = 1) {
            currentPage = page;
            const type = document.getElementById('typeFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 50,
                    ...(type && { type }),
                    ...(startDate && { start_date: startDate }),
                    ...(endDate && { end_date: endDate })
                });

                const response = await fetch(`../api/admin/transactions.php?${params}`, {
                    headers: { 'Authorization': 'Bearer ' + adminToken }
                });

                const result = await response.json();

                if (result.success) {
                    displayTransactions(result.data.transactions);
                    displayPagination(result.data.pagination);
                } else {
                    showError('Failed to load transactions: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error loading transactions');
            }
        }

        function displayTransactions(transactions) {
            const tbody = document.getElementById('transactionsTableBody');
            
            if (transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No transactions found</td></tr>';
                return;
            }

            tbody.innerHTML = transactions.map(tx => {
                if (tx.type === 'wallet') {
                    return `
                        <tr>
                            <td>${tx.id}</td>
                            <td>${tx.user_name || 'N/A'}</td>
                            <td><span class="badge bg-primary">Wallet</span></td>
                            <td>₦${parseFloat(tx.amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
                            <td>
                                <span class="badge bg-${tx.transaction_type === 'credit' ? 'success' : 'danger'}">${tx.transaction_type}</span>
                                <br><small>${tx.details || 'N/A'}</small>
                            </td>
                            <td>${new Date(tx.created_at).toLocaleString()}</td>
                        </tr>
                    `;
                } else {
                    return `
                        <tr>
                            <td>${tx.id}</td>
                            <td>${tx.user_name || 'N/A'}</td>
                            <td><span class="badge bg-info">VTU - ${tx.transaction_type}</span></td>
                            <td>₦${parseFloat(tx.amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
                            <td>
                                <span class="badge bg-${getStatusColor(tx.status)}">${tx.status}</span>
                                <br><small>${tx.network} - ${tx.phone_number}</small>
                            </td>
                            <td>${new Date(tx.created_at).toLocaleString()}</td>
                        </tr>
                    `;
                }
            }).join('');
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
                <a class="page-link" href="#" onclick="loadTransactions(${pagination.page - 1}); return false;">Previous</a>
            </li>`;

            for (let i = 1; i <= Math.min(pagination.total_pages, 10); i++) {
                html += `<li class="page-item ${i === pagination.page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadTransactions(${i}); return false;">${i}</a>
                </li>`;
            }

            html += `<li class="page-item ${pagination.page === pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadTransactions(${pagination.page + 1}); return false;">Next</a>
            </li>`;

            paginationEl.innerHTML = html;
        }

        function getStatusColor(status) {
            const colors = { 'SUCCESS': 'success', 'PENDING': 'warning', 'FAILED': 'danger', 'PROCESSING': 'info' };
            return colors[status] || 'secondary';
        }

        function applyFilters() {
            loadTransactions(1);
        }

        function exportTransactions() {
            window.location.href = `../api/admin/export.php?type=transactions&token=${adminToken}`;
        }

        function showError(message) {
            document.getElementById('transactionsTableBody').innerHTML = `
                <tr><td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </td></tr>
            `;
        }
    </script>
</body>
</html>
