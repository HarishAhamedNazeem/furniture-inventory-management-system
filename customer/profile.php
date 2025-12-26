<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$success = '';
$error = '';

// Get customer information
$customer_sql = "SELECT * FROM customers WHERE id = " . $customer_id;
$customer_result = $db->query($customer_sql);
$customer = $db->fetch_assoc($customer_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        // Check if email is already taken by another customer
        $check_email_sql = "SELECT id FROM customers WHERE email = '" . $db->escape($email) . "' AND id != " . $customer_id;
        $check_email_result = $db->query($check_email_sql);
        
        if ($db->num_rows($check_email_result) > 0) {
            $error = 'Email is already registered by another customer';
        } else {
            // Update customer information
            $update_sql = "UPDATE customers SET 
                          name = '" . $db->escape($name) . "', 
                          email = '" . $db->escape($email) . "', 
                          phone = '" . $db->escape($phone) . "', 
                          address = '" . $db->escape($address) . "' 
                          WHERE id = " . $customer_id;
            
            if ($db->query($update_sql)) {
                $success = 'Profile updated successfully';
                // Update session name
                $_SESSION['customer_name'] = $name;
                $_SESSION['customer_email'] = $email;
                // Refresh customer data
                $customer_result = $db->query($customer_sql);
                $customer = $db->fetch_assoc($customer_result);
            } else {
                $error = 'Failed to update profile';
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
    <title>Profile - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
    <style>
        /* Profile Hero Section - Modern Dashboard Style */
        .profile-hero {
            background: #b7bdbb;
            color: var(--text-white);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--border-dark);
            background-image: url('../libs/images/header3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.2) 100%);
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: var(--font-size-4xl);
            font-weight: 700;
            color: var(--text-white);
            margin-bottom: var(--spacing-sm);
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: var(--font-size-lg);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
            max-width: 600px;
        }
        
        .profile-actions {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition-normal);
            border: 2px solid transparent;
        }
        
        .action-btn.primary {
            background: var(--primary-color);
            color: var(--text-white);
            border-color: var(--primary-color);
        }
        
        .action-btn.primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--text-white);
        }
    </style>
</head>
<body class="customer-portal">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Enhanced Page Header -->
    <section class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <h1 class="hero-title">My Profile</h1>
                        <p class="hero-subtitle">Update your personal information and manage your account settings</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="profile-actions">
                        <a href="change_password.php" class="action-btn primary">
                            <i class="fas fa-key"></i>
                            <span>Change Password</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>Profile Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($customer['phone']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('M d, Y', strtotime($customer['created_at'])); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Login</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo $customer['last_login'] ? date('M d, Y H:i', strtotime($customer['last_login'])) : 'Never'; ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 