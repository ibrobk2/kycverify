// Global variables
let currentUser = null
let isLoggedIn = false
const bootstrap = window.bootstrap // Declare the bootstrap variable

// DOM Content Loaded
document.addEventListener("DOMContentLoaded", () => {
  initializeApp()
  setupEventListeners()
  animateOnScroll()
})

// Initialize Application
function initializeApp() {
  // Check if user is logged in
  checkAuthStatus()

  // Initialize carousel indicators
  initializeCarousel()

  // Setup form validation
  setupFormValidation()

  console.log("AgentVerify App Initialized")
}

// Setup Event Listeners
function setupEventListeners() {
  // Login form
  const loginForm = document.getElementById("loginForm")
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin)
  }

  // Signup form
  const signupForm = document.getElementById("signupForm")
  if (signupForm) {
    signupForm.addEventListener("submit", handleSignup)
  }

  // Verification form
  const verificationForm = document.getElementById("verificationForm")
  if (verificationForm) {
    verificationForm.addEventListener("submit", handleVerification)
  }

  // NIN input formatting
  const ninInput = document.getElementById("ninNumber")
  if (ninInput) {
    ninInput.addEventListener("input", formatNINInput)
  }

  // Phone input formatting
  const phoneInput = document.getElementById("phoneNumber")
  if (phoneInput) {
    phoneInput.addEventListener("input", formatPhoneInput)
  }

  // Carousel indicators
  const indicators = document.querySelectorAll(".indicator")
  indicators.forEach((indicator, index) => {
    indicator.addEventListener("click", () => switchSlide(index))
  })
}

// Authentication Functions
async function handleLogin(e) {
  e.preventDefault()

  const email = document.getElementById("loginEmail").value
  const password = document.getElementById("loginPassword").value

  if (!validateEmail(email)) {
    showAlert("Please enter a valid email address", "danger")
    return
  }

  if (password.length < 6) {
    showAlert("Password must be at least 6 characters", "danger")
    return
  }

  const submitBtn = e.target.querySelector('button[type="submit"]')
  setLoadingState(submitBtn, true)

  try {
    const response = await fetch("api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        email: email,
        password: password,
      }),
    })

    const data = await response.json()

    if (data.success) {
      currentUser = data.user
      isLoggedIn = true
      localStorage.setItem("authToken", data.token)
      // Store user data for dashboard
      localStorage.setItem("userData", JSON.stringify(data.user))

      showAlert("Login successful!", "success")
      closeModal("loginModal")
      updateUIForLoggedInUser()
      
      // Redirect to dashboard immediately
      setTimeout(() => {
        window.location.href = "dashboard.html"
      }, 1000)
    } else {
        if (data.email_not_verified) {
            window.location.href = `otp-verification.html?email=${email}`;
        } else {
            showAlert(data.message || "Login failed", "danger");
        }
    }
  } catch (error) {
    console.error("Login error:", error)
    showAlert("Network error. Please try again.", "danger")
  } finally {
    setLoadingState(submitBtn, false)
  }
}

async function handleSignup(e) {
  e.preventDefault()

  const name = document.getElementById("signupName").value
  const email = document.getElementById("signupEmail").value
  const phone = document.getElementById("signupPhone").value
  const password = document.getElementById("signupPassword").value
  const confirmPassword = document.getElementById("signupConfirmPassword").value

  // Validation
  if (name.length < 2) {
    showAlert("Name must be at least 2 characters", "danger")
    return
  }

  if (!validateEmail(email)) {
    showAlert("Please enter a valid email address", "danger")
    return
  }

  if (!validatePhone(phone)) {
    showAlert("Please enter a valid phone number", "danger")
    return
  }

  if (password.length < 6) {
    showAlert("Password must be at least 6 characters", "danger")
    return
  }

  if (password !== confirmPassword) {
    showAlert("Passwords do not match", "danger")
    return
  }

  const submitBtn = e.target.querySelector('button[type="submit"]')
  setLoadingState(submitBtn, true)

  try {
    const response = await fetch("api/signup.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        name: name,
        email: email,
        phone: phone,
        password: password,
      }),
    })

    // Check if response is JSON
    const responseText = await response.text();
    try {
      const data = JSON.parse(responseText);
      
      if (data.success) {
        window.location.href = `otp-verification.html?email=${email}`;
      } else {
        showAlert(data.message || "Signup failed", "danger")
      }
    } catch (jsonError) {
      // Handle non-JSON responses (like HTML error pages)
      console.error("Non-JSON response received:", responseText);
      showAlert("Server error occurred. Please try again later.", "danger")
    }
  } catch (error) {
    console.error("Signup error:", error)
    showAlert("Network error. Please try again.", "danger")
  } finally {
    setLoadingState(submitBtn, false)
  }
}



// NIN Verification Function
async function handleVerification(e) {
  e.preventDefault()

  const ninNumber = document.getElementById("ninNumber").value
  const phoneNumber = document.getElementById("phoneNumber").value

  // Validation
  if (!validateNIN(ninNumber)) {
    showAlert("Please enter a valid 11-digit NIN", "danger")
    return
  }

  if (!validatePhone(phoneNumber)) {
    showAlert("Please enter a valid phone number", "danger")
    return
  }

  const submitBtn = e.target.querySelector('button[type="submit"]')
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
        <div class="alert alert-success">
          <h6><i class="fas fa-check-circle"></i> Verification Successful</h6>
          <p><strong>Name:</strong> ${data.data.name}</p>
          <p><strong>Phone:</strong> ${data.data.phone}</p>
          <p><strong>Status:</strong> ${data.data.status}</p>
          <small class="text-muted">Verified on ${new Date().toLocaleString()}</small>
        </div>
      `
    } else {
      resultDiv.innerHTML = `
        <div class="alert alert-danger">
          <h6><i class="fas fa-times-circle"></i> Verification Failed</h6>
          <p>${data.message}</p>
        </div>
      `
    }
  } catch (error) {
    console.error("Verification error:", error)
    const resultDiv = document.getElementById("verificationResult")
    resultDiv.style.display = "block"
    resultDiv.innerHTML = `
      <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle"></i> Network Error</h6>
        <p>Unable to verify NIN. Please try again later.</p>
      </div>
    `
  } finally {
    setLoadingState(submitBtn, false)
  }
}

// Validation Functions
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function validateNIN(nin) {
  return /^\d{11}$/.test(nin)
}

function validatePhone(phone) {
  const phoneRegex = /^(\+234|0)[789]\d{9}$/
  return phoneRegex.test(phone.replace(/\s/g, ""))
}

// Input Formatting Functions
function formatNINInput(e) {
  let value = e.target.value.replace(/\D/g, "")
  if (value.length > 11) {
    value = value.substring(0, 11)
  }
  e.target.value = value

  // Real-time validation feedback
  const isValid = validateNIN(value)
  e.target.classList.toggle("is-valid", isValid && value.length === 11)
  e.target.classList.toggle("is-invalid", !isValid && value.length > 0)
}

function formatPhoneInput(e) {
  let value = e.target.value.replace(/\D/g, "")

  // Format as Nigerian phone number
  if (value.startsWith("234")) {
    value = "+" + value
  } else if (value.startsWith("0")) {
    value = "+234" + value.substring(1)
  } else if (value.length === 10) {
    value = "+234" + value
  }

  e.target.value = value

  // Real-time validation feedback
  const isValid = validatePhone(value)
  e.target.classList.toggle("is-valid", isValid)
  e.target.classList.toggle("is-invalid", !isValid && value.length > 0)
}

// UI Helper Functions
function showAlert(message, type = "info") {
  // Create alert element
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`
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

function setLoadingState(button, isLoading) {
  if (isLoading) {
    button.disabled = true
    button.innerHTML = '<span class="spinner"></span> Processing...'
  } else {
    button.disabled = false
    button.innerHTML = button.getAttribute("data-original-text") || "Submit"
  }
}

function openModal(modalId) {
  const modal = new bootstrap.Modal(document.getElementById(modalId))
  modal.show()
}

function closeModal(modalId) {
  const modal = bootstrap.Modal.getInstance(document.getElementById(modalId))
  if (modal) {
    modal.hide()
  }
}

// Carousel Functions
function initializeCarousel() {
  const indicators = document.querySelectorAll(".indicator")
  let currentSlide = 0

  setInterval(() => {
    indicators[currentSlide].classList.remove("active")
    currentSlide = (currentSlide + 1) % indicators.length
    indicators[currentSlide].classList.add("active")
  }, 3000)
}

function switchSlide(index) {
  const indicators = document.querySelectorAll(".indicator")
  indicators.forEach((indicator) => indicator.classList.remove("active"))
  indicators[index].classList.add("active")
}

// Scroll Functions
function scrollToServices() {
  document.getElementById("services").scrollIntoView({
    behavior: "smooth",
  })
}

function animateOnScroll() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1"
        entry.target.style.transform = "translateY(0)"
      }
    })
  })

  // Observe service cards
  document.querySelectorAll(".service-card").forEach((card) => {
    card.style.opacity = "0"
    card.style.transform = "translateY(30px)"
    card.style.transition = "all 0.6s ease"
    observer.observe(card)
  })

  // Observe stat cards
  document.querySelectorAll(".stat-card").forEach((card) => {
    card.style.opacity = "0"
    card.style.transform = "translateY(30px)"
    card.style.transition = "all 0.6s ease"
    observer.observe(card)
  })
}

// Authentication Status
function checkAuthStatus() {
  const token = localStorage.getItem("authToken")
  if (token) {
    // Verify token with server
    fetch("api/verify-token.php", {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          currentUser = data.user
          isLoggedIn = true
          updateUIForLoggedInUser()
        } else {
          localStorage.removeItem("authToken")
        }
      })
      .catch((error) => {
        console.error("Token verification error:", error)
        localStorage.removeItem("authToken")
      })
  }
}

function updateUIForLoggedInUser() {
  // Update navigation buttons
  const navButtons = document.querySelector(".navbar-nav")
  if (navButtons && currentUser) {
    navButtons.innerHTML = `
      <span class="text-white me-3">Welcome, ${currentUser.name}</span>
      <button class="btn btn-outline-light me-2" onclick="showDashboard()">Dashboard</button>
      <button class="btn btn-outline-light me-3" onclick="logout()">Logout</button>
      <div class="profile-icon">
        <i class="fas fa-user-circle fa-2x text-white"></i>
      </div>
    `
  }
}

function logout() {
  localStorage.removeItem("authToken")
  currentUser = null
  isLoggedIn = false
  location.reload()
}

function showDashboard() {
  showAlert("Dashboard feature coming soon!", "info")
}

// Form Validation Setup
function setupFormValidation() {
  // Add Bootstrap validation classes
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!form.checkValidity()) {
        e.preventDefault()
        e.stopPropagation()
      }
      form.classList.add("was-validated")
    })
  })
}

// Utility Functions
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

function throttle(func, limit) {
  let inThrottle
  return function () {
    const args = arguments
    
    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

// Error Handling
window.addEventListener("error", (e) => {
  console.error("Global error:", e.error)
  showAlert("An unexpected error occurred. Please refresh the page.", "danger")
})

// Service Worker Registration (for PWA capabilities)
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("/sw.js")
      .then((registration) => {
        console.log("ServiceWorker registration successful")
      })
      .catch((err) => {
        console.log("ServiceWorker registration failed")
      })
  })
}
