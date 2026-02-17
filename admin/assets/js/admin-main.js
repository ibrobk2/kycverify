// Admin Main Functionality
window.logoutAdmin = function () {
    if (confirm('Are you sure you want to logout?')) {
        // Remove all admin-related storage
        const keysToRemove = ['adminToken', 'adminUser', 'adminData'];
        keysToRemove.forEach(key => localStorage.removeItem(key));

        // Clear any other potential admin items
        Object.keys(localStorage)
            .filter(key => key.toLowerCase().includes('admin'))
            .forEach(key => localStorage.removeItem(key));

        window.location.href = 'login.html';
    }
};

function initAdminMain() {
    // Sidebar toggle for mobile
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && !document.querySelector('.sidebar-toggler')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'sidebar-toggler';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleBtn);

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 991) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // Set admin username if available
    const adminData = localStorage.getItem('adminUser');
    if (adminData) {
        try {
            const admin = JSON.parse(adminData);
            const displayElements = document.querySelectorAll('.admin-username-display');
            displayElements.forEach(el => el.textContent = admin.name || 'Admin');
        } catch (e) {
            console.error('Error parsing admin data:', e);
        }
    }

    // Handle logout triggers
    const logoutTriggers = document.querySelectorAll('#logout-btn, .logout-trigger');
    logoutTriggers.forEach(btn => {
        // Remove existing listeners to avoid duplicates if script runs twice
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.logoutAdmin();
        });
    });
}

// Initialize when DOM is ready or immediately if already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminMain);
} else {
    initAdminMain();
}
// Also run immediately to catch elements already in DOM (like sidebar)
initAdminMain();
