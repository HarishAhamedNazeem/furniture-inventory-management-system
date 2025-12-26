<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Check customer credentials
        $sql = "SELECT * FROM customers WHERE email = '" . $db->escape($email) . "' AND status = 1";
        $result = $db->query($sql);
        
        if ($db->num_rows($result) > 0) {
            $customer = $db->fetch_assoc($result);
            
            if (password_verify($password, $customer['password'])) {
                // Set session variables
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_name'] = $customer['name'];
                $_SESSION['customer_email'] = $customer['email'];
                
                // Update last login
                $db->query("UPDATE customers SET last_login = NOW() WHERE id = " . $customer['id']);
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Email not found or account inactive';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
</head>
<body class="customer-portal">

    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <!-- Brand Header -->
                    <div class="auth-brand text-center mb-4">
                        <div class="brand-logo-large mx-auto mb-3">
                            <img src="../libs/images/SW.png" alt="SWISS WOODWORKS Logo" class="logo-image">
                        </div>
                        <h2 class="brand-title">Swisswood Works</h2>
                        <p class="brand-subtitle">Welcome Back</p>
                    </div>

                    <div class="auth-card">
                        <div class="auth-header">
                            <div class="auth-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <h3>Sign In to Your Account</h3>
                            <p class="mb-0">Access your personalized dashboard</p>
                        </div>
                        
                        <div class="auth-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           placeholder="Email Address" required>
                                    <label for="email">
                                        <i class="fas fa-envelope me-2"></i>Email Address
                                    </label>
                                </div>
                                
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Password" required>
                                    <label for="password">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3 auth-btn">
                                    <span class="btn-text">
                                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                    </span>
                                    <span class="btn-spinner" style="display: none;">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Signing In...
                                    </span>
                                </button>
                                
                                <div class="text-center mt-4">
                                    <p class="mb-0">Don't have an account? 
                                        <a href="register.php" class="auth-link">
                                            <i class="fas fa-user-plus me-1"></i>Create Account
                                        </a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        // Form validation and enhanced UX
        (function() {
            'use strict';
            
            // Form submission with loading state
            const form = document.querySelector('.needs-validation');
            const submitBtn = document.querySelector('.auth-btn');
            const btnText = document.querySelector('.btn-text');
            const btnSpinner = document.querySelector('.btn-spinner');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    // Show loading state
                    btnText.style.display = 'none';
                    btnSpinner.style.display = 'inline';
                    submitBtn.disabled = true;
                }
                
                form.classList.add('was-validated');
            });
            
            // Enhanced form field interactions
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                control.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
                
                // Check if field has value on load
                if (control.value !== '') {
                    control.parentElement.classList.add('focused');
                }
            });
            
            // Password visibility toggle
            const passwordInput = document.getElementById('password');
            const passwordLabel = passwordInput.nextElementSibling;
            
            // Add eye icon to password field
            const eyeIcon = document.createElement('i');
            eyeIcon.className = 'fas fa-eye password-toggle';
            eyeIcon.style.position = 'absolute';
            eyeIcon.style.right = '15px';
            eyeIcon.style.top = '50%';
            eyeIcon.style.transform = 'translateY(-50%)';
            eyeIcon.style.cursor = 'pointer';
            eyeIcon.style.color = '#666';
            eyeIcon.style.zIndex = '10';
            
            passwordInput.parentElement.style.position = 'relative';
            passwordInput.parentElement.appendChild(eyeIcon);
            
            eyeIcon.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        })();
    </script> 