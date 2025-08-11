document.addEventListener("DOMContentLoaded", () => {
    const otpVerificationForm = document.getElementById("otpVerificationForm");
    const resendOtpBtn = document.getElementById("resendOtpBtn");

    const urlParams = new URLSearchParams(window.location.search);
    const email = urlParams.get('email');

    if (!email) {
        window.location.href = "index.html";
    }

    if (otpVerificationForm) {
        otpVerificationForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const otpCode = document.getElementById("otpCode").value;

            if (!otpCode || otpCode.length !== 6) {
                showAlert("Please enter a valid 6-digit OTP code", "danger");
                return;
            }

            try {
                const response = await fetch("./api/verify-otp.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        email: email,
                        otp: otpCode,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showAlert("Email verified successfully! You can now log in.", "success");
                    setTimeout(() => {
                        window.location.href = "index.html";
                    }, 2000);
                } else {
                    showAlert(data.message || "OTP verification failed", "danger");
                }
            } catch (error) {
                console.error("OTP verification error:", error);
                showAlert("Network error. Please try again.", "danger");
            }
        });
    }

    if (resendOtpBtn) {
        resendOtpBtn.addEventListener("click", async () => {
            try {
                const response = await fetch("api/resend-otp.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ email: email }),
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
        });
    }
});

function showAlert(message, type = "info") {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = "top: 20px; right: 20px; z-index: 9999;";
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
