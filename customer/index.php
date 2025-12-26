<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swisswood Works - Customer Portal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/customer.css" rel="stylesheet">
</head>




<body class="customer-portal">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas me-2"></i>Swisswood Works
        </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse custom-navbar-links" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
        <h1 class="display-4 fw-bold text-white mb-4">
            Welcome to Swisswood Works
        </h1>
        <p class="lead text-white-50 mb-4">
            Discover our premium collection of handcrafted products and furniture. 
            Quality craftsmanship meets timeless design for your home and office.
        </p>
                    <!-- <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>Shop Now
                        </a>
                    </div> -->
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <img src="../libs/images/sw.png" alt="Swisswood Works" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section
    <section class="features-section py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                        <h4>Fast Delivery</h4>
                        <p>Quick and reliable shipping to your doorstep</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h4>Secure Payment</h4>
                        <p>Multiple secure payment options available</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                        <h4>24/7 Support</h4>
                        <p>Round the clock customer support</p>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Footer
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Swisswood Works</h5>
                    <p>Handcrafted premium products and furniture for your home and office</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2024 Swisswood Works. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer> -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 