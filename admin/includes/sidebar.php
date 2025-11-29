<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="col-auto sidebar">
    <div class="p-3">
        <h4 class="text-white mb-4">
            <i class="fas fa-bolt text-info"></i>
            Admin Panel
        </h4>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users me-2"></i>Users
            </a>
            <a class="nav-link <?php echo $currentPage === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                <i class="fas fa-exchange-alt me-2"></i>Transactions
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
            <a class="nav-link" href="#" id="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </nav>
    </div>
</div>
<script>
    document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            localStorage.removeItem('adminToken');
            window.location.href = 'login.html';
        }
    });
</script>
