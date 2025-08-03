// Global variables
let currentUser = {
    name: "",
    email: "",
    balance: 0,
    totalVerifications: 0,
}

// Initialize dashboard
document.addEventListener("DOMContentLoaded", () => {
    // Check if user is authenticated
    const token = localStorage.getItem("authToken")
    if (!token) {
        window.location.href = "index.html"
        return
    }

    // Verify token and fetch fresh user data
    fetch("api/verify-token.php", {
        method: "GET",
        headers: {
            Authorization: `Bearer ${token}`,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                currentUser = {
                    ...currentUser,
                    name: data.user.name,
                    email: data.user.email,
                    // Assuming balance and totalVerifications are fetched from user or API
                    balance: 0,
                    totalVerifications: 0,
                }
                initializeDashboard()
                setupEventListeners()
                showDashboard()
            } else {
                // Token invalid or expired, redirect to login
                localStorage.removeItem("authToken")
                localStorage.removeItem("userData")
                window.location.href = "index.html"
            }
        })
        .catch((error) => {
            console.error("Token verification error:", error)
            localStorage.removeItem("authToken")
            localStorage.removeItem("userData")
            window.location.href = "index.html"
        })
})

function initializeDashboard() {
    console.log("Dashboard initialized")
    updateUserInfo()
}

// Setup event listeners
function setupEventListeners() {
    // Profile form
    const profileForm = document.getElementById("profileForm")
    if (profileForm) {
        profileForm.addEventListener("submit", handleProfileUpdate)
    }

    // Password form
    const passwordForm = document.getElementById("passwordForm")
    if (passwordForm) {
        passwordForm.addEventListener("submit", handlePasswordChange)
    }

    // Mobile sidebar toggle (for responsive design)
    if (window.innerWidth <= 768) {
        addMobileSidebarToggle()
    }
}

// Logout function
function logout() {
    const confirmLogout = window.confirm("Are you sure you want to logout?");
    if (confirmLogout) {
        localStorage.removeItem("authToken");
        localStorage.removeItem("userData");
        window.location.href = "index.html";
    }
}

// Navigation functions
function showDashboard() {
    hideAllContent()
    document.getElementById("dashboardContent").style.display = "block"
    document.getElementById("pageTitle").textContent = "Dashboard"
    setActiveNavItem("Dashboard")
}

function showNINServices() {
    hideAllContent()
    document.getElementById("pageTitle").textContent = "NIN Services"
    setActiveNavItem("NIN Services")
    showServiceSection("NIN Services")
}

function showBVNServices() {
    hideAllContent()
    document.getElementById("pageTitle").textContent = "BVN Services"
    setActiveNavItem("BVN Services")
    showServiceSection("BVN Services")
}

function showFundWallet() {
    hideAllContent()
    document.getElementById("pageTitle").textContent = "Fund Wallet"
    setActiveNavItem("Fund Wallet")
    showFundWalletModal()
}

function showFundingHistory() {
    hideAllContent()
    document.getElementById("pageTitle").textContent = "Funding History"
    setActiveNavItem("Funding History")
    showHistorySection("funding")
}

function showActivity() {
    hideAllContent()
    document.getElementById("pageTitle").textContent = "Activity"
    setActiveNavItem("Activity")
    showActivitySection()
}

function showDeveloper() {
    hideAllContent()
    document.getElementById("developerContent").style.display = "block"
    document.getElementById("pageTitle").textContent = "Developer Portal"
    setActiveNavItem("Developer")
}

function showSettings() {
    hideAllContent()
    document.getElementById("settingsContent").style.display = "block"
    document.getElementById("pageTitle").textContent = "Settings"
    setActiveNavItem("Settings")
}

function hideAllContent() {
    const contents = ["dashboardContent", "developerContent", "settingsContent"]
    contents.forEach((id) => {
        const element = document.getElementById(id)
        if (element) {
            element.style.display = "none"
        }
    })
}

function setActiveNavItem(itemName) {
    // Remove active class from all nav items
    document.querySelectorAll(".nav-link").forEach((link) => {
        link.classList.remove("active")
    })

    // Add active class to current item
    document.querySelectorAll(".nav-link").forEach((link) => {
        const span = link.querySelector("span")
        if (span && span.textContent === itemName) {
            link.classList.add("active")
        }
    })
}

// Service functions
function openService(serviceId) {
    const serviceName = serviceId.replace(/-/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())
    showAlert(`Opening ${serviceName} service...`, "info")

    // Simulate service opening
    setTimeout(() => {
        if (serviceId === "nin-verification") {
            openNINVerificationModal()
        } else if (serviceId === "bvn-verification") {
            openBVNVerificationModal()
        } else if (serviceId === "birth-attestation") {
            window.location.href = "birth-attestation.html"
        } else if (serviceId === "bvn-modification") {
            window.location.href = "bvn-modification.html"
        } else if (serviceId === "personalize") {
            window.location.href = "personalize.html"
        } else {
            showAlert(`${serviceName} service is coming soon!`, "warning")
        }
    }, 1000)
}



function openNINVerificationModal() {
    const modalHtml = `
        <div class="modal fade" id="ninVerificationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">NIN Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="ninVerificationForm">
                            <div class="mb-3">
                                <label for="ninInput" class="form-label">NIN Number</label>
                                <input type="text" class="form-control" id="ninInput" placeholder="Enter 11-digit NIN" maxlength="11" required>
                            </div>
                            <div class="mb-3">
                                <label for="phoneInput" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phoneInput" placeholder="Enter phone number" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i>Verify NIN
                            </button>
                        </form>
                        <div id="verificationResult" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    `

    document.body.insertAdjacentHTML("beforeend", modalHtml)
    const modal = new window.bootstrap.Modal(document.getElementById("ninVerificationModal"))
    modal.show()

    // Setup form handler
    document.getElementById("ninVerificationForm").addEventListener("submit", handleNINVerification)
}

function openBVNVerificationModal() {
    const modalHtml = `
        <div class="modal fade" id="bvnVerificationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">BVN Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="bvnVerificationForm">
                            <div class="mb-3">
                                <label for="bvnInput" class="form-label">BVN Number</label>
                                <input type="text" class="form-control" id="bvnInput" placeholder="Enter 11-digit BVN" maxlength="11" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-university me-2"></i>Verify BVN
                            </button>
                        </form>
                        <div id="bvnVerificationResult" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    `

    document.body.insertAdjacentHTML("beforeend", modalHtml)
    const modal = new window.bootstrap.Modal(document.getElementById("bvnVerificationModal"))
    modal.show()

    // Setup form handler
    document.getElementById("bvnVerificationForm").addEventListener("submit", handleBVNVerification)
}

function showFundWalletModal() {
    const modalHtml = `
        <div class="modal fade" id="fundWalletModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Fund Wallet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="fundWalletForm">
                            <div class="mb-3">
                                <label for="fundAmount" class="form-label">Amount (₦)</label>
                                <input type="number" class="form-control" id="fundAmount" placeholder="Enter amount" min="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                <select class="form-control" id="paymentMethod" required>
                                    <option value="">Select payment method</option>
                                    <option value="card">Debit/Credit Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="ussd">USSD</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-credit-card me-2"></i>Fund Wallet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `

    document.body.insertAdjacentHTML("beforeend", modalHtml)
    const modal = new window.bootstrap.Modal(document.getElementById("fundWalletModal"))
    modal.show()

    // Setup form handler
    document.getElementById("fundWalletForm").addEventListener("submit", handleFundWallet)
}

// Verification handlers
async function handleNINVerification(e) {
    e.preventDefault()

    const ninNumber = document.getElementById("ninInput").value
    const phoneNumber = document.getElementById("phoneInput").value
    const submitBtn = e.target.querySelector('button[type="submit"]')

    if (!validateNIN(ninNumber)) {
        showAlert("Please enter a valid 11-digit NIN", "danger")
        return
    }

    setLoadingState(submitBtn, true)

    try {
        const response = await fetch("api/verify-nin.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${localStorage.getItem("authToken")}`,
            },
            body: JSON.stringify({
                nin: ninNumber,
                phone: phoneNumber,
            }),
        })

        const data = await response.json()
        const resultDiv = document.getElementById("verificationResult")
        resultDiv.style.display = "block"

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success mt-3">
                    <h6><i class="fas fa-check-circle"></i> Verification Successful</h6>
                    <p><strong>Name:</strong> ${data.data.name || "N/A"}</p>
                    <p><strong>Phone:</strong> ${data.data.phone || phoneNumber}</p>
                    <p><strong>Status:</strong> Verified</p>
                </div>
            `

            // Update verification count
            currentUser.totalVerifications++
            updateUserInfo()
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <h6><i class="fas fa-times-circle"></i> Verification Failed</h6>
                    <p>${data.message || "Unable to verify NIN"}</p>
                </div>
            `
        }
    } catch (error) {
        console.error("Verification error:", error)
        showAlert("Network error. Please try again.", "danger")
    } finally {
        setLoadingState(submitBtn, false)
    }
}

async function handleBVNVerification(e) {
    e.preventDefault()

    const bvnNumber = document.getElementById("bvnInput").value
    const submitBtn = e.target.querySelector('button[type="submit"]')

    if (!validateBVN(bvnNumber)) {
        showAlert("Please enter a valid 11-digit BVN", "danger")
        return
    }

    setLoadingState(submitBtn, true)

    try {
        const response = await fetch("api/verify-bvn.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${localStorage.getItem("authToken")}`,
            },
            body: JSON.stringify({
                bvn: bvnNumber,
            }),
        })

        const data = await response.json()
        const resultDiv = document.getElementById("bvnVerificationResult")
        resultDiv.style.display = "block"

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success mt-3">
                    <h6><i class="fas fa-check-circle"></i> BVN Verification Successful</h6>
                    <p><strong>Name:</strong> ${data.data.name || "N/A"}</p>
                    <p><strong>Bank:</strong> ${data.data.bank || "N/A"}</p>
                    <p><strong>Status:</strong> Verified</p>
                </div>
            `

            // Update verification count
            currentUser.totalVerifications++
            updateUserInfo()
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <h6><i class="fas fa-times-circle"></i> Verification Failed</h6>
                    <p>${data.message || "Unable to verify BVN"}</p>
                </div>
            `
        }
    } catch (error) {
        console.error("BVN verification error:", error)
        showAlert("Network error. Please try again.", "danger")
    } finally {
        setLoadingState(submitBtn, false)
    }
}

async function handleFundWallet(e) {
    e.preventDefault()
    
    showAlert("Wallet funding feature is coming soon!", "warning")
    
    // Close modal after showing alert
    setTimeout(() => {
        const modal = document.getElementById("fundWalletModal")
        if (modal) {
            const bsModal = window.bootstrap.Modal.getInstance(modal)
            if (bsModal) bsModal.hide()
        }
    }, 2000)
}

// Settings handlers
async function handleProfileUpdate(e) {
    e.preventDefault()
    
    showAlert("Profile update feature is coming soon!", "warning")
    
    // Reset form
    e.target.reset()
}

async function handlePasswordChange(e) {
    e.preventDefault()
    
    showAlert("Password change feature is coming soon!", "warning")
    
    // Reset form
    e.target.reset()
}

// Developer functions
function copyToClipboard(inputId) {
    const input = document.getElementById(inputId)
    input.select()
    input.setSelectionRange(0, 99999)
    document.execCommand("copy")
    showAlert("Copied to clipboard!", "success")
}

function toggleKeyVisibility(inputId) {
    const input = document.getElementById(inputId)
    const toggleBtn = document.getElementById(inputId + "Toggle")

    if (input.type === "password") {
        input.type = "text"
        toggleBtn.className = "fas fa-eye-slash"
    } else {
        input.type = "password"
        toggleBtn.className = "fas fa-eye"
    }
}

async function generateNewKeys() {
    showAlert("API key generation feature is coming soon!", "warning")
}

function openDocumentation(type) {
    showAlert(`Opening ${type.replace("-", " ")} documentation...`, "info")
    // In a real app, this would open the documentation page
}

// Utility functions
function validateNIN(nin) {
    return /^\d{11}$/.test(nin)
}

function validateBVN(bvn) {
    return /^\d{11}$/.test(bvn)
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
}

function updateUserInfo() {
    // Update user name and email in sidebar
    const userName = document.getElementById("userName")
    const userEmail = document.getElementById("userEmail")
    const walletBalance = document.getElementById("walletBalance")
    const totalVerifications = document.getElementById("totalVerifications")

    if (userName) userName.textContent = currentUser.name
    if (userEmail) userEmail.textContent = currentUser.email
    if (walletBalance) walletBalance.textContent = `₦${currentUser.balance.toLocaleString()}`
    if (totalVerifications) totalVerifications.textContent = currentUser.totalVerifications
}

function saveUserData() {
    localStorage.setItem("userData", JSON.stringify(currentUser))
}

function setLoadingState(button, isLoading) {
    if (isLoading) {
        // Store original text if not already stored
        if (!button.hasAttribute("data-original-text")) {
            button.setAttribute("data-original-text", button.innerHTML)
        }
        button.disabled = true
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
    } else {
        button.disabled = false
        button.innerHTML = button.getAttribute("data-original-text") || "Submit"
    }
}

function showAlert(message, type = "info") {
    // Create alert element
    const alertDiv = document.createElement("div")
    alertDiv.className = `alert alert-${type === "error" ? "danger" : type} alert-dismissible fade show position-fixed`
    alertDiv.style.cssText = "top: 100px; right: 20px; z-index: 9999; min-width: 300px;"
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `

    document.body.appendChild(alertDiv)

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove()
        }
    }, 5000)
}

function showNotifications() {
    showAlert("You have no new notifications", "info")
}

function showProfile() {
    showSettings()
}

function showServiceSection(sectionName) {
    const contentArea = document.getElementById("contentArea")
    contentArea.innerHTML = `
        <div class="service-section">
            <h2>${sectionName}</h2>
            <p class="text-muted mb-4">Access all ${sectionName.toLowerCase()} related services</p>
            
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        ${sectionName} are currently being updated. Please check back soon!
                    </div>
                </div>
            </div>
        </div>
    `
}

function showHistorySection(type) {
    const contentArea = document.getElementById("contentArea")
    const title = type === "funding" ? "Funding History" : "Transaction History"

    contentArea.innerHTML = `
        <div class="history-section">
            <h2>${title}</h2>
            <p class="text-muted mb-4">View your ${type} history and details</p>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No ${type} history found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `
}

function showActivitySection() {
    const contentArea = document.getElementById("contentArea")
    contentArea.innerHTML = `
        <div class="activity-section">
            <h2>Activity</h2>
            <p class="text-muted mb-4">Monitor your account activity and usage statistics</p>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="activity-item">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="mb-1">Account created successfully</p>
                                    <small class="text-muted">Today at ${new Date().toLocaleTimeString()}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Usage Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="stat-row">
                                <span>API Calls Today</span>
                                <span class="fw-bold">0</span>
                            </div>
                            <div class="stat-row">
                                <span>Verifications This Month</span>
                                <span class="fw-bold">${currentUser.totalVerifications}</span>
                            </div>
                            <div class="stat-row">
                                <span>Account Status</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
}

// Account action functions
function downloadData() {
    showAlert("Preparing your data for download...", "info")

    setTimeout(() => {
        const data = {
            user: currentUser,
            downloadDate: new Date().toISOString(),
            dataType: "user_account_data",
        }

        const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" })
        const url = URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = url
        a.download = `robost-tech-data-${new Date().toISOString().split("T")[0]}.json`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)

        showAlert("Data downloaded successfully", "success")
    }, 2000)
}

function deactivateAccount() {
    if (confirm("Are you sure you want to deactivate your account? You can reactivate it later.")) {
        showAlert("Account deactivation feature is coming soon", "warning")
    }
}

function deleteAccount() {
    const confirmation = prompt('Type "DELETE" to confirm account deletion:')
    if (confirmation === "DELETE") {
        showAlert("Account deletion feature is coming soon", "warning")
    } else if (confirmation) {
        showAlert("Account deletion cancelled", "info")
    }
}
