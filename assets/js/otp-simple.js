// Simple OTP Fix - Fixes OTP page not showing after registration
(function () {
    'use strict';

    // Show OTP modal after registration
    function showOTPModal() {
        // Create modal if it doesn't exist
        if (!document.getElementById('otpModal')) {
            const modalHTML = `
                <div class="modal fade" id="otpModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Email Verification</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Please check your email for the verification code.</p>
                                <input type="text" id="otpCode" class="form-control" placeholder="Enter 6-digit code" maxlength="6">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success" onclick="verifyOTP()">Verify</button>
                                <button type="button" class="btn btn-outline-primary" onclick="resendOTP()">Resend</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        const modal = new bootstrap.Modal(document.getElementById('otpModal'));
        modal.show();
    }

    // Verify OTP
    window.verifyOTP = async function () {
        const otpCode = document.getElementById('otpCode').value;
        const userId = localStorage.getItem('signupUserId');

        if (!otpCode || otpCode.length !== 6) {
            alert('Please enter a valid 6-digit code');
            return;
        }

        try {
            const response = await fetch('api/verify-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, otp_code: otpCode })
            });

            const data = await response.json();

            if (data.success) {
                localStorage.removeItem('signupUserId');
                window.location.href = 'dashboard.html';
            } else {
                alert(data.message || 'Verification failed');
            }
        } catch (error) {
            alert('Network error. Please try again.');
        }
    };

    // Resend OTP
    window.resendOTP = async function () {
        const email = localStorage.getItem('signupEmail');
        if (!email) return;

        try {
            const response = await fetch('api/resend-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            });

            const data = await response.json();
            alert(data.message || 'OTP resent successfully');
        } catch (error) {
            alert('Failed to resend OTP');
        }
    };

    // Expose function globally
    window.showOTPModal = showOTPModal;
})();

