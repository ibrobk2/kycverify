// Complete OTP Fix - Fixes OTP page not showing after registration
(function() {
    'use strict';

    // Global variables
    let currentOTPUserId = null;
    
    // DOM Content Loaded
    document.addEventListener("DOMContentLoaded", () => {
        initializeOTPSystem();
    });

    // Initialize OTP system
    function initializeOTPSystem() {
        setupOTPModal();
        setupEventListeners();
    }

    // Setup OTP modal
    function setupOTPModal() {
        if (!document.getElementById('otpModal')) {
            createOTPModal();
        }
    }

    // Create OTP modal
    function createOTPModal() {
        const modalHTML = `
            <div class="modal fade" id="otpModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Email Verification</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Please check your email for the verification code.</p>
                            <form id="otpForm">
                                <div class="mb-3">
                                    <label for="otpCode" class="form-label">Verification Code</label>
                                    <input type="text" class="form-control" id="otpCode" 
                                           placeholder="Enter 6-digit code" maxlength="6" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">Verify</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="resendOTP()">
                                        Resend Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    // Setup event listeners
    function setupEventListeners() {
        // OTP form submission
        const otpForm = document.getElementById('otpForm');
        if (otpForm) {
            otpForm.addEventListener('submit', handleOTPVerification);
        }
    }

    // Show OTP modal
    function showOTPModal() {
        const modal = new bootstrap.Modal(document.getElementById('otpModal'));
        modal.show();
    }

    // Handle OTP verification
    async function handleOTPVerification(e) {
        e.preventDefault();
        
        const userId = localStorage.getItem("signupUserId");
        const otpCode = document.getElementById("otpCode").value;

        if (!userId) {
            showAlert("Session expired. Please sign up again.", "danger");
            return;
        }

        if (!otpCode || otpCode.length !== 6) {
            showAlert("Please enter a valid 6-digit code", "danger");
            return;
        }

        try {
            const response = await fetch("api/verify-otp.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    user_id: userId,
                    otp_code: otpCode,
                }),
            });

            const data = await response.json();

            if (data.success) {
                showAlert("Email verified successfully!", "success");
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('otpModal'));
                modal.hide();
                
                // Clear session
                localStorage.removeItem("signupUserId");
                
                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = "dashboard.html";
                }, 1000);
            } else {
                showAlert(data.message || "Verification failed", "danger");
            }
        } catch (error) {
            console.error("OTP verification error:", error);
            showAlert("Network error. Please try again.", "danger");
        }
    }

    // Resend OTP
    async function resendOTP() {
        const email = localStorage.getItem("signupEmail");
        
        if (!email) {
            showAlert("Email not found", "danger");
            return;
        }

        try {
            const response = await fetch("api/resend-otp.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    email: email
                }),
            });

            const data = await response.json();

            if (data.success) {
                showAlert("OTP resent successfully", "success");
            } else {
                showAlert(data.message || "Failed to resend OTP", "danger");
            }
        } catch (error) {
            showAlert("Network error", "danger");
        }
    }

    // Utility function to show alerts
    function showAlert(message, type = "info") {
        const alertDiv = document.createElement("div");
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = "top: 100px; right: 20px; z-index: 9999; min-width: 300px;";
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Expose functions globally
    window.showOTPModal = showOTPModal;
    window.resendOTP = resendOTP;

})();

