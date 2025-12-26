<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email already exists
        $check_email = $db->query("SELECT id FROM customers WHERE email = '" . $db->escape($email) . "'");
        if ($db->num_rows($check_email) > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert customer with registration type
            $sql = "INSERT INTO customers (name, email, phone, address, password, registration_type, created_at) 
                    VALUES ('" . $db->escape($name) . "', '" . $db->escape($email) . "', 
                    '" . $db->escape($phone) . "', '" . $db->escape($address) . "', 
                    '" . $db->escape($hashed_password) . "', 'online', NOW())";
            
            if ($db->query($sql)) {
                $success = 'Registration successful! You can now login.';
                
                // Send welcome email to new customer
                try {
                    require_once('../includes/email_functions.php');
                    
                    $customer_data = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $email_sent = sendCustomerRegistrationEmail($customer_data);
                    
                    if ($email_sent) {
                        error_log("Welcome email sent successfully to new customer: $email");
                    } else {
                        error_log("Failed to send welcome email to new customer: $email");
                    }
                } catch (Exception $email_error) {
                    error_log("Email sending error for customer registration: " . $email_error->getMessage());
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Swisswood Works</title>
    
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
                <div class="col-md-8 col-lg-6">
                    <!-- Brand Header -->
                    <div class="auth-brand text-center mb-4">
                        <div class="brand-logo-large mx-auto mb-3">
                            <img src="../libs/images/SW.png" alt="SWISS WOODWORKS Logo" class="logo-image">
                        </div>
                        <h2 class="brand-title">Swisswood Works</h2>
                        <p class="brand-subtitle">Premium Products</p>
                    </div>

                    <div class="auth-card">
                        <div class="auth-header">
                            <div class="auth-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Create Your Account</h3>
                            <p class="mb-0">Join our community of premium product enthusiasts</p>
                        </div>
                        
                        <div class="auth-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                                   placeholder="Full Name" required>
                                            <label for="name">
                                                <i class="fas fa-user me-2"></i>Full Name
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                   placeholder="Email Address" required>
                                            <label for="email">
                                                <i class="fas fa-envelope me-2"></i>Email Address
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                                   placeholder="Phone Number">
                                            <label for="phone">
                                                <i class="fas fa-phone me-2"></i>Phone Number
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Password" required minlength="6">
                                            <label for="password">
                                                <i class="fas fa-lock me-2"></i>Password
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="address" name="address" 
                                              placeholder="Address" style="height: 80px"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                    <label for="address">
                                        <i class="fas fa-map-marker-alt me-2"></i>Address
                                    </label>
                                </div>
                                
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm Password" required>
                                    <label for="confirm_password">
                                        <i class="fas fa-lock me-2"></i>Confirm Password
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3 auth-btn">
                                    <span class="btn-text">
                                        <i class="fas fa-user-plus me-2"></i>Create Account
                                    </span>
                                    <span class="btn-spinner" style="display: none;">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Creating Account...
                                    </span>
                                </button>
                                
                                <div class="text-center mt-4">
                                    <p class="mb-0">Already have an account? 
                                        <a href="login.php" class="auth-link">
                                            <i class="fas fa-sign-in-alt me-1"></i>Sign In
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

        // Form validation and password strength
        (function() {
            'use strict';
            
            // Password strength checker
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            // Only add password strength functionality if elements exist
            if (passwordInput && passwordStrength && strengthFill && strengthText) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);
                    
                    if (password.length > 0) {
                        passwordStrength.style.display = 'block';
                        strengthFill.style.width = strength.percentage + '%';
                        strengthFill.className = 'strength-fill ' + strength.class;
                        strengthText.textContent = strength.text;
                    } else {
                        passwordStrength.style.display = 'none';
                    }
                });
            }
            
            // Password confirmation validation
            if (confirmPasswordInput && passwordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    const password = passwordInput.value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword.length > 0) {
                        if (password === confirmPassword) {
                            this.setCustomValidity('');
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.setCustomValidity('Passwords do not match');
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    } else {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid', 'is-valid');
                    }
                });
            }
            
            // Form submission with loading state
            const form = document.querySelector('.needs-validation');
            const submitBtn = document.querySelector('.auth-btn');
            const btnText = document.querySelector('.btn-text');
            const btnSpinner = document.querySelector('.btn-spinner');
            
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        // Show loading state only if elements exist
                        if (btnText && btnSpinner && submitBtn) {
                            btnText.style.display = 'none';
                            btnSpinner.style.display = 'inline';
                            submitBtn.disabled = true;
                        }
                    }
                    
                    form.classList.add('was-validated');
                });
            }
            
            // Password strength checker function
            function checkPasswordStrength(password) {
                let strength = 0;
                let feedback = [];
                
                // Length check
                if (password.length >= 8) strength += 25;
                else feedback.push('at least 8 characters');
                
                // Lowercase check
                if (/[a-z]/.test(password)) strength += 25;
                else feedback.push('lowercase letters');
                
                // Uppercase check
                if (/[A-Z]/.test(password)) strength += 25;
                else feedback.push('uppercase letters');
                
                // Number check
                if (/\d/.test(password)) strength += 25;
                else feedback.push('numbers');
                
                // Special character check
                if (/[^A-Za-z0-9]/.test(password)) strength += 10;
                
                let strengthClass = 'weak';
                let strengthText = 'Weak';
                
                if (strength >= 80) {
                    strengthClass = 'strong';
                    strengthText = 'Strong';
                } else if (strength >= 60) {
                    strengthClass = 'medium';
                    strengthText = 'Medium';
                } else if (strength >= 40) {
                    strengthClass = 'weak';
                    strengthText = 'Weak';
                } else {
                    strengthClass = 'very-weak';
                    strengthText = 'Very Weak';
                }
                
                return {
                    percentage: Math.min(strength, 100),
                    class: strengthClass,
                    text: strengthText
                };
            }
        })();
    </script> 