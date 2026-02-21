<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="p-3">
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary">
            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                <i class="fas fa-user-shield text-white fs-4"></i>
            </div>
            <div class="overflow-hidden">
                <h6 class="text-white mb-0 text-truncate admin-username-display">Admin</h6>
                <a href="#" class="text-info small text-decoration-none logout-trigger" onclick="if(window.logoutAdmin) { window.logoutAdmin(); return false; }">
                    <i class="fas fa-power-off me-1"></i>Logout
                </a>
            </div>
        </div>
        
        <h5 class="text-white mb-3 px-2" style="opacity: 0.5; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Menu</h5>
        <nav class="nav flex-column ps-2">
            <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users me-2"></i>Users
            </a>
            <a class="nav-link <?php echo $currentPage === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                <i class="fas fa-exchange-alt me-2"></i>Transactions
            </a>
            <a class="nav-link <?php echo $currentPage === 'service-management.php' ? 'active' : ''; ?>" href="service-management.php">
                <i class="fas fa-tasks me-2"></i>Service Management
            </a>
            <a class="nav-link <?php echo $currentPage === 'nin-modification.php' ? 'active' : ''; ?>" href="nin-modification.php">
                <i class="fas fa-id-card me-2"></i>NIN Modification
            </a>
            <a class="nav-link <?php echo $currentPage === 'ipe-clearance.php' ? 'active' : ''; ?>" href="ipe-clearance.php">
                <i class="fas fa-gavel me-2"></i>IPE Clearance
            </a>
            <a class="nav-link <?php echo $currentPage === 'vtu-management.php' ? 'active' : ''; ?>" href="vtu-management.php">
                <i class="fas fa-mobile-alt me-2"></i>VTU Management
            </a>
            <a class="nav-link <?php echo $currentPage === 'api-integrations.php' ? 'active' : ''; ?>" href="api-integrations.php">
                <i class="fas fa-plug me-2"></i>API Integrations
            </a>
            <a class="nav-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>" href="analytics.php">
                <i class="fas fa-chart-line me-2"></i>Analytics
            </a>
            <a class="nav-link <?php echo $currentPage === 'pricing.php' ? 'active' : ''; ?>" href="pricing.php">
                <i class="fas fa-dollar-sign me-2"></i>Pricing
            </a>
            <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class="fas fa-cog me-2"></i>Settings
            </a>
            <a class="nav-link text-danger mt-3" href="#" id="logout-btn" onclick="if(window.logoutAdmin) { window.logoutAdmin(); return false; }">
                <i class="fas fa-sign-out-alt me-2"></i>Sign Out
            </a>
        </nav>
    </div>
</div>

<link rel="stylesheet" href="assets/css/admin-custom.css">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script src="assets/js/admin-main.js"></script>
