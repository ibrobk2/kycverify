<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - agentify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            font-size: 1.2rem;
            color: #666;
        }

        /* Slider Styles */
        .dashboard-slider {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            height: 300px;
        }
        .dashboard-slider .carousel-item {
            height: 300px;
        }
        .dashboard-slider img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }
        .carousel-caption {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 10px;
            bottom: 20px;
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
                <div>
                    <!-- Dashboard Section -->
                    <div id="dashboard-section" class="content-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Dashboard Overview</h2>
                            <a href="slider-management.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-images me-2"></i>Manage Slider
                            </a>
                        </div>

                        
                        <!-- Slider -->
                        <div id="dashboardCarousel" class="carousel slide dashboard-slider" data-bs-ride="carousel">
                            <div class="carousel-inner" id="slider-container">
                                <div class="carousel-item active">
                                    <div class="d-flex justify-content-center align-items-center h-100 bg-light">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading slider...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#dashboardCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#dashboardCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number" id="total-users">--</div>
                                    <p class="text-muted mb-0">Total Users</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number" id="total-verifications">--</div>
                                    <p class="text-muted mb-0">Verifications</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number" id="pending-applications">--</div>
                                    <p class="text-muted mb-0">Pending</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number" id="revenue">₦0</div>
                                    <p class="text-muted mb-0">Revenue</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div id="loading-state" class="loading">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Loading dashboard data...
                        </div>
                        
                        <!-- Charts -->
                        <div class="row" id="charts-container" style="display: none;">
                            <div class="col-lg-8 mb-4">
                                <div class="chart-container">
                                    <h5 class="mb-3">Verification Trends</h5>
                                    <canvas id="verificationChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-4">
                                <div class="chart-container">
                                    <h5 class="mb-3">Status Distribution</h5>
                                    <canvas id="statusChart" width="200" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Transactions -->
                        <div class="row" id="recent-transactions-container" style="display: none;">
                            <div class="col-12">
                                <div class="chart-container">
                                    <h5 class="mb-3">Recent Transactions</h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="recent-transactions-table">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Email</th>
                                                    <th>Amount</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="5" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Section -->
                    <div id="users-section" class="content-section" style="display: none;">
                        <h2 class="mb-4">User Management</h2>
                        <div class="chart-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>All Users</h5>
                                <button class="btn btn-primary" onclick="refreshUsers()">
                                    <i class="fas fa-refresh me-2"></i>Refresh
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="users-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center">Loading users...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Admin Dashboard JavaScript
        let adminToken = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        async function checkAdminAuth() {
            // FIX: Assign to global adminToken, not a new local variable
            adminToken = localStorage.getItem('adminToken');
            if (!adminToken) {
                return false;
            }

            try {
                const response = await fetch('../api/admin-auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    }
                });
                const data = await response.json();
                return data.success === true;
            } catch (error) {
                return false;
            }
        }

        async function initializeApp() {
            // Check authentication
            const isAuthenticated = await checkAdminAuth();
            if (!isAuthenticated) {
                showAuthError();
                setTimeout(() => {
                    window.location.href = './login.html';
                }, 2000);
                return;
            }
            
            // Setup navigation
            setupNavigation();
            
            // Load dashboard
            await loadDashboardData();
            await loadSliderImages();

            // Keep dashboard data updated every 10 seconds
            setInterval(loadDashboardData, 10000);
            
            // Setup logout
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                logoutAdmin();
            });
        }

        function showAuthError() {
            let container = document.querySelector('.p-4') || document.body;
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger mt-4';
            alertDiv.textContent = 'Authentication failed. Redirecting to login...';
            container.prepend(alertDiv);
        }

        function setupNavigation() {
            const navLinks = document.querySelectorAll('.nav-link[data-section]');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    showSection(section);
                    
                    // Update active nav
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }

        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => section.style.display = 'none');
            
            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.style.display = 'block';
            }
        }

        async function loadDashboardData() {
            if (!adminToken) return;

            try {
                const response = await fetch('../api/admin/stats.php', {
                    headers: {
                        'Authorization': 'Bearer ' + adminToken
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load stats');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.data;
                    
                    // Update stat cards
                    document.getElementById('total-users').textContent = stats.users.total;
                    document.getElementById('total-verifications').textContent = stats.verifications.total;
                    document.getElementById('pending-applications').textContent = stats.verifications.pending;
                    document.getElementById('revenue').textContent = '₦' + parseFloat(stats.revenue.total).toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    
                    // Hide loading, show content
                    document.getElementById('loading-state').style.display = 'none';
                    document.getElementById('charts-container').style.display = 'flex';
                    document.getElementById('recent-transactions-container').style.display = 'block';
                    
                    // Update charts
                    updateCharts(stats);
                    
                    // Update recent transactions table
                    updateRecentTransactions(stats.recent_transactions);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                document.getElementById('loading-state').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load dashboard data. Please refresh the page.
                    </div>
                `;
            }
        }
        
        function updateCharts(stats) {
            // User growth chart
            const userGrowthLabels = stats.user_growth.map(d => new Date(d.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}));
            const userGrowthData = stats.user_growth.map(d => d.count);
            
            const ctx1 = document.getElementById('verificationChart').getContext('2d');
            if (window.verificationChart) window.verificationChart.destroy();
            window.verificationChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: userGrowthLabels.length > 0 ? userGrowthLabels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'New Users',
                        data: userGrowthData.length > 0 ? userGrowthData : [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
            
            // Status distribution chart
            const ctx2 = document.getElementById('statusChart').getContext('2d');
            if (window.statusChart) window.statusChart.destroy();
            window.statusChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Success', 'Pending', 'Failed'],
                    datasets: [{
                        data: [
                            stats.verifications.success || 0,
                            stats.verifications.pending || 0,
                            stats.verifications.failed || 0
                        ],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        async function loadSliderImages() {
            if (!adminToken) return;

            try {
                const response = await fetch('../api/admin/slider-images.php', {
                    headers: {
                        'Authorization': 'Bearer ' + adminToken
                    }
                });
                
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const sliderContainer = document.getElementById('slider-container');
                    sliderContainer.innerHTML = result.data.map((img, index) => `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                            <img src="${img.image_url}" class="d-block w-100" alt="${img.caption || 'Slider Image'}">
                            ${img.caption ? `
                            <div class="carousel-caption d-none d-md-block">
                                <h5>${img.caption}</h5>
                            </div>` : ''}
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading slider images:', error);
            }
        }

        function updateRecentTransactions(transactions) {
            const tbody = document.querySelector('#recent-transactions-table tbody');
            if (!tbody) return;
            
            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No recent transactions</td></tr>';
                return;
            }
            
            tbody.innerHTML = transactions.map(tx => `
                <tr>
                    <td>${tx.user_name || 'N/A'}</td>
                    <td>${tx.user_email || 'N/A'}</td>
                    <td>₦${parseFloat(tx.amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
                    <td><span class="badge bg-${tx.transaction_type === 'credit' ? 'success' : 'danger'}">${tx.transaction_type}</span></td>
                    <td>${new Date(tx.created_at).toLocaleDateString()}</td>
                </tr>
            `).join('');
        }

        function loadCharts() {
            // Verification trends chart
            const ctx1 = document.getElementById('verificationChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Verifications',
                        data: [12, 19, 3, 5, 2, 3, 9],
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Status distribution chart
            const ctx2 = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Success', 'Pending', 'Failed'],
                    datasets: [{
                        data: [85, 10, 5],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        async function logoutAdmin() {
            // try {
            //     localStorage.removeItem('adminToken');
            //     localStorage.removeItem('adminUser');
            //     // Optionally, call a logout API endpoint if you have one
            // } catch (error) {
            //     console.error('Logout error:', error);
            // } finally {
            //     window.location.href = './login.html';
             const confirmLogout = window.confirm("Are you sure you want to logout?");
            if (confirmLogout) {
                localStorage.removeItem("adminToken");
                localStorage.removeItem("adminData");
                window.location.href = "login.html";
    
            }
        }

        async function refreshUsers() {
            if (!adminToken) return;
            
            try {
                const response = await fetch('../api/admin/users.php', {
                    headers: {
                        'Authorization': 'Bearer ' + adminToken
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load users');
                }
                
                const data = await response.json();
                console.log('Users refreshed:', data);
                
            } catch (error) {
                console.error('Error refreshing users:', error);
            }
        }
    </script>
</body>
</html>
