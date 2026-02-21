<?php
// Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-bolt text-cyan"></i>
            <span class="logo-text">agentify</span>
        </div>
    </div>

    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-info">
            <h6 class="user-name" id="sidebarUserName">Loading...</h6>
            <p class="user-email" id="sidebarUserEmail">Please wait...</p>
            <span class="user-badge">User</span>
        </div>
    </div>

    <script>
        // Load user details for sidebar
        (async function loadSidebarUserData() {
            const token = localStorage.getItem('authToken');
            if (!token) return;

            try {
                const response = await fetch('api/verify-token.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                
                if (data.success && data.user) {
                    document.getElementById('sidebarUserName').textContent = data.user.name;
                    document.getElementById('sidebarUserEmail').textContent = data.user.email;
                }
            } catch (error) {
                console.error('Error loading sidebar user data:', error);
            }
        })();
    </script>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">
                <a href="services.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>All Services</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'nin-verification.php' || $current_page == 'nin-validation.php') ? 'active' : ''; ?>">
                <a href="nin-verification.php" class="nav-link">
                    <i class="fas fa-id-card"></i>
                    <span>NIN Services</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'nin-modification.php') ? 'active' : ''; ?>">
                <a href="nin-modification.php" class="nav-link">
                    <i class="fas fa-user-edit"></i>
                    <span>NIN Modification</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'bvn-retrieval.php' || $current_page == 'bvn-modification.php') ? 'active' : ''; ?>">
                <a href="bvn-retrieval.php" class="nav-link">
                    <i class="fas fa-university"></i>
                    <span>BVN Services</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'ipe-clearance.php') ? 'active' : ''; ?>">
                <a href="ipe-clearance.php" class="nav-link">
                    <i class="fas fa-gavel"></i>
                    <span>IPE Clearance</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'bvn-slip-printing.php') ? 'active' : ''; ?>">
                <a href="bvn-slip-printing.php" class="nav-link">
                    <i class="fas fa-print"></i>
                    <span>BVN SLIP</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'airtime-purchase.php' || $current_page == 'data-purchase.php') ? 'active' : ''; ?>">
                <a href="airtime-purchase.php" class="nav-link">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Buy Airtime</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'data-purchase.php') ? 'active' : ''; ?>">
                <a href="data-purchase.php" class="nav-link">
                    <i class="fas fa-wifi"></i>
                    <span>Buy Data</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" onclick="showFundWallet()" class="nav-link">
                    <i class="fas fa-wallet"></i>
                    <span>Fund Wallet</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'transactions.php') ? 'active' : ''; ?>">
                <a href="transactions.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'personalize.php') ? 'active' : ''; ?>">
                <a href="personalize.php" class="nav-link">
                    <i class="fas fa-user-circle"></i>
                    <span>Personalize</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link text-danger" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
