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
    <title>Personalize - agentify Verification Services</title>
    
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
        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            padding: 15px;
            border-radius: 10px;
        }
        .chart-container {
            height: 300px;
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
                    <h1 class="page-title">Personalize</h1>
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
                <div class="service-header text-white py-4">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-4 fw-bold">Personalized Dashboard</h1>
                                <p class="lead">View your personalized BVN modification history and statistics</p>
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
                                    <li class="breadcrumb-item active" aria-current="page">Personalize</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Total Requests</h5>
                                            <h2 id="totalRequests" class="mb-0">0</h2>
                                        </div>
                                        <div class="stat-icon bg-primary text-white">
                                            <i class="fas fa-file-upload"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Pending</h5>
                                            <h2 id="pendingRequests" class="mb-0">0</h2>
                                        </div>
                                        <div class="stat-icon bg-warning text-white">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Completed</h5>
                                            <h2 id="completedRequests" class="mb-0">0</h2>
                                        </div>
                                        <div class="stat-icon bg-success text-white">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Rejected</h5>
                                            <h2 id="rejectedRequests" class="mb-0">0</h2>
                                        </div>
                                        <div class="stat-icon bg-danger text-white">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        Request Status Distribution
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Requests Over Time
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="timelineChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload History Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>
                                        BVN Modification History
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="historyTable" class="table table-striped" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Reference</th>
                                                    <th>Documents</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="historyTableBody">
                                                <!-- Data will be loaded here by JavaScript -->
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        // Load upload history and initialize charts
        document.addEventListener("DOMContentLoaded", function() {
            loadUploadHistory();
        });
        
        // Load upload history
        function loadUploadHistory() {
            // Dummy data for demonstration
            const historyData = [
                { date: "2023-10-18", reference: "BVN-2023-0157", documents: 3, status: "completed" },
                { date: "2023-10-15", reference: "UBN-2023-0156", documents: 2, status: "processing" },
                { date: "2023-10-10", reference: "BVN-2023-0155", documents: 4, status: "completed" },
                { date: "2023-10-05", reference: "BVN-2023-0154", documents: 3, status: "rejected" },
                { date: "2023-09-28", reference: "BVN-2023-0153", documents: 2, status: "completed" }
            ];
            
            updateStats(historyData);
            populateTable(historyData);
            initializeCharts(historyData);
        }
        
        // Update stats
        function updateStats(data) {
            const total = data.length;
            const pending = data.filter(item => item.status === "processing").length;
            const completed = data.filter(item => item.status === "completed").length;
            const rejected = data.filter(item => item.status === "rejected").length;
            
            document.getElementById("totalRequests").textContent = total;
            document.getElementById("pendingRequests").textContent = pending;
            document.getElementById("completedRequests").textContent = completed;
            document.getElementById("rejectedRequests").textContent = rejected;
        }
        
        // Populate table
        function populateTable(data) {
            const tableBody = document.getElementById("historyTableBody");
            tableBody.innerHTML = "";
            
            data.forEach(item => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${item.date}</td>
                    <td>${item.reference}</td>
                    <td>${item.documents} files</td>
                    <td><span class="badge bg-${getStatusColor(item.status)}">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewDetails('${item.reference}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
            
            // Initialize DataTable
            if (!$.fn.DataTable.isDataTable('#historyTable')) {
                $('#historyTable').DataTable({
                    "order": [[ 0, "desc" ]],
                    "pageLength": 10,
                    "responsive": true
                });
            }
        }
        
        // Get status color
        function getStatusColor(status) {
            switch(status) {
                case "completed": return "success";
                case "processing": return "warning";
                case "rejected": return "danger";
                default: return "secondary";
            }
        }
        
        // Initialize charts
        function initializeCharts(data) {
            // Status distribution chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Completed', 'Processing', 'Rejected'],
                    datasets: [{
                        data: [
                            data.filter(item => item.status === "completed").length,
                            data.filter(item => item.status === "processing").length,
                            data.filter(item => item.status === "rejected").length
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
            
            // Timeline chart
            const timelineCtx = document.getElementById('timelineChart').getContext('2d');
            new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.date),
                    datasets: [{
                        label: 'Requests',
                        data: data.map((_, index) => index + 1),
                        borderColor: 'rgba(23, 162, 184, 1)',
                        backgroundColor: 'rgba(23, 162, 184, 0.2)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
        
        function viewDetails(reference) {
            alert(`Viewing details for reference: ${reference}`);
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
