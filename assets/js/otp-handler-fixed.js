// Fixed OTP Handler - Complete solution for OTP page not showing after registration
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
        if (!document.getElementById('otpVerificationModal')) {
            createOTPModal();
        }
    }

    // Create OTP modal HTML
    function createOTPModal() {
        const modalHTML = `
            <div class="modal fade" id="otpVerificationModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="otpModalLabel">Email Verification</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Please check your email for a 6-digit verification code.</p>
                            <form id="otpVerificationForm">
                                <div class="mb-3">
                                    <label for="otpCode" class="form-label">Verification Code</label>
                                    <input type="text" class="form-control" id="otpCode" 
                                           placeholder="Enter 6-digit code" maxlength="6" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check me-2"></i>Verify Email
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="resendOTP()">
                                        <i class="fas fa-redo me-2"></i>Resend Code
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

    // Show OTP verification modal
    function showOTPVerificationModal() {
        setupOTPModal();
        
        // Remove any existing modal instances
        const existingModal = bootstrap.Modal.getInstance(document.getElementById('otpVerificationModal'));
        if (existingModal) {
            existingModal.dispose();
        }
        
        // Show new modal
        const modal = new bootstrap.Modal(document.getElementById('otpVerificationModal'));
        modal.show();
        
        // Setup form handler
        setupOTPFormHandlers();
    }

    // Setup OTP form handlers
    function setupOTPFormHandlers() {
        const form = document.getElementById('otpVerificationForm');
        if (form) {
            form.addEventListener('submit', handleOTPVerification);
        }
    }

    // Handle OTP verification
    async function handleOTPVerification(e) {
        e.preventDefault();
        
        const userId = localStorage.getItem("signupUserId");
        const otpCode = document.getElementById("otpCode").value;

        if (!userId) {
            showAlert("Signup session expired. Please sign up again.", "danger");
            return;
        }

        if (!otpCode || otpCode.length !== 6) {
            showAlert("Please enter a valid 6-digit OTP code", "danger");
            return;
        }

        const submitBtn = e.target.querySelector('button[type="submit"]');
        setLoadingState(submitBtn, true);

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
                showAlert("Email verified successfully! You can now login.", "success");
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('otpVerificationModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Clear signup user ID
                localStorage.removeItem("signupUserId");
                
                // Redirect to login
                setTimeout(() => {
                    window.location.href = "dashboard.html";
                }, 1500);
            } else {
                showAlert(data.message || "OTP verification failed", "danger");
            }
        } catch (error) {
            console.error("OTP verification error:", error);
            showAlert("Network error. Please try again.", "danger");
        } finally {
            setLoadingState(submitBtn, false);
        }
    }

    // Resend OTP function
    async function resendOTP() {
        const email = localStorage.getItem("signupEmail");
        
        if (!email) {
            showAlert("Email not found. Please sign up again.", "danger");
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
                showAlert(data.message || "OTP code resent successfully", "success");
            } else {
                showAlert(data.message || "Failed to resend OTP", "danger");
            }
        } catch (error) {
            console.error("Resend OTP error:", error);
            showAlert("Network error. Please try again.", "danger");
        }
    }

    // Utility functions
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

    function setLoadingState(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute("data-original-text") || "Submit";
        }
    }

    // Expose functions globally
    window.showOTPVerificationModal = showOTPVerificationModal;
    window.resendOTP = resendOTP;

})();

