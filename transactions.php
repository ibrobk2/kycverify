<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/wallet-helper.php';

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
    <title>Transaction History - Lildone Verification Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .transaction-table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-failed { background: #fee2e2; color: #b91c1c; }
        .type-credit { color: #15803d; font-weight: 600; }
        .type-debit { color: #b91c1c; font-weight: 600; }
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
                    <h1 class="page-title">Transaction History</h1>
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
                <div class="container-fluid">
                    <!-- Filters -->
                    <div class="filter-section">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <label class="form-label small text-muted">Transaction Type</label>
                                <select class="form-select" id="filterType" onchange="loadTransactions(1)">
                                    <option value="all">All Transactions</option>
                                    <option value="wallet">Wallet Funding</option>
                                    <option value="service" selected>Service Payments</option>
                                    <option value="vtu">VTU / Airtime</option>
                                </select>
                            </div>
                            <div class="col-md-9 text-md-end">
                                <button class="btn btn-outline-primary" onclick="loadTransactions(1)">
                                    <i class="fas fa-sync-alt me-2"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="transaction-table-container">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                            Loading transactions...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav id="paginationContainer" class="mt-4">
                            <ul class="pagination justify-content-center" id="pagination">
                                <!-- Pagination links will be injected here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                // window.location.href = 'index.html';
                return;
            }
            loadTransactions(1);
        });

        async function loadTransactions(page = 1) {
            const token = localStorage.getItem('authToken');
            const type = document.getElementById('filterType').value;
            const tbody = document.getElementById('transactionBody');
            
            currentPage = page;

            try {
                const response = await fetch(`api/get-transactions.php?page=${page}&limit=10&type=${type}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();

                if (data.success) {
                    renderTransactions(data.data);
                    renderPagination(data.pagination);
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${data.message || 'Error loading transactions'}</td></tr>`;
                }
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">An error occurred. Please try again later.</td></tr>';
            }
        }

        function renderTransactions(transactions) {
            const tbody = document.getElementById('transactionBody');
            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No transactions found.</td></tr>';
                return;
            }

            tbody.innerHTML = transactions.map(tx => {
                const date = new Date(tx.created_at).toLocaleDateString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
                
                const typeClass = tx.type === 'credit' ? 'type-credit' : 'type-debit';
                const typeLabel = tx.type.charAt(0).toUpperCase() + tx.type.slice(1);
                const statusClass = `status-${tx.status.toLowerCase()}`;
                const statusLabel = tx.status.charAt(0).toUpperCase() + tx.status.slice(1);

                return `
                    <tr>
                        <td class="small">${date}</td>
                        <td class="small text-muted">${tx.reference || 'N/A'}</td>
                        <td><span class="${typeClass}">${typeLabel}</span></td>
                        <td>
                            <div class="fw-medium">${tx.details || 'N/A'}</div>
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem">${tx.source}</small>
                        </td>
                        <td class="fw-bold">₦${parseFloat(tx.amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
                        <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                    </tr>
                `;
            }).join('');
        }

        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            if (!pagination || pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';
            
            // Previous
            html += `<li class="page-item ${pagination.page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadTransactions(${pagination.page - 1})">Previous</a>
            </li>`;

            // Pages
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
                    html += `<li class="page-item ${i === pagination.page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadTransactions(${i})">${i}</a>
                    </li>`;
                } else if (i === pagination.page - 2 || i === pagination.page + 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            html += `<li class="page-item ${pagination.page >= pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadTransactions(${pagination.page + 1})">Next</a>
            </li>`;

            container.innerHTML = html;
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
