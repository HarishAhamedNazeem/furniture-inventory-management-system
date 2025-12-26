<?php
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

// Initialize cart count
$cart_count = 0;
if (isset($db) && isset($_SESSION['customer_id'])) {
    try {
        $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $_SESSION['customer_id'];
        $cart_count_result = $db->query($cart_count_sql);
        if ($cart_count_result) {
            $cart_count = $db->fetch_assoc($cart_count_result)['count'];
        }
    } catch (Exception $e) {
        $cart_count = 0; // Handle DB errors silently
    }
}

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } else {
        // In a real application, you would save this to a support_tickets table
        // and send an email notification
        $success = 'Your message has been sent successfully. We will get back to you soon!';
    }
}

// FAQ data
$faqs = [
    [
        'question' => 'How do I place an order?',
        'answer' => 'Browse our products, add items to your cart, and proceed to checkout. You can pay using credit card, bank transfer, or cash on delivery.'
    ],
    [
        'question' => 'What payment methods do you accept?',
        'answer' => 'We accept credit cards, bank transfers, and cash on delivery. All payments are processed securely.'
    ],
    [
        'question' => 'How long does shipping take?',
        'answer' => 'Standard shipping takes 3-5 business days. Express shipping is available for an additional fee and takes 1-2 business days.'
    ],
    [
        'question' => 'Can I cancel my order?',
        'answer' => 'Orders can be cancelled within 24 hours of placement. Please contact our support team for assistance.'
    ],
    /* [
        'question' => 'What is your return policy?',
        'answer' => 'We offer a 30-day return policy for unused items in original packaging. Return shipping costs are the responsibility of the customer.'
    ], */
    [
        'question' => 'Do you ship internationally?',
        'answer' => 'Currently, we only ship within the local area. International shipping may be available in the future.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="customer-portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - Swisswood Works</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/modern-customer.css" rel="stylesheet">
</head>

<!-- Modern Professional Navbar -->
<?php $cpage = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <div class="brand-logo me-2">
                <i class="fas fa-cube"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name">Swisswood Works</span>
                <span class="brand-subtitle">Customer Portal</span>
            </div>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Main Navigation -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $cpage==='dashboard.php'?'active':''; ?>" href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $cpage==='shop.php'?'active':''; ?>" href="shop.php">
                        <i class="fas fa-store"></i>
                        <span>Shop</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $cpage==='orders.php'?'active':''; ?>" href="orders.php">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $cpage==='wishlist.php'?'active':''; ?>" href="wishlist.php">
                        <i class="fas fa-heart"></i>
                        <span>Wishlist</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $cpage==='cart.php'?'active':''; ?>" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Cart</span>
                        <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <!-- Right Side Actions -->
            <ul class="navbar-nav">
                <!-- Notifications -->
                <li class="nav-item me-3">
                    <a class="nav-link position-relative" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                </li>
                
                <!-- Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info d-none d-lg-block">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></span>
                            <small class="user-status">Online</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar-large me-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></div>
                                    <small class="text-muted">Premium Member</small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-edit"></i>My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="orders.php">
                                <i class="fas fa-shopping-bag"></i>My Orders
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="wishlist.php">
                                <i class="fas fa-heart"></i>Wishlist
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="support.php">
                                <i class="fas fa-headset"></i>Support
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="change_password.php">
                                <i class="fas fa-key"></i>Change Password
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <!-- Support Hero Section -->
    <section class="support-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="support-title">Customer Support</h1>
                    <p class="support-subtitle">Get help and find answers to your questions. We're here to assist you with any inquiries or concerns.</p>
                    <div class="support-stats">
                        <div class="support-stat">
                            <i class="fas fa-headset"></i>
                            <span>24/7 Support</span>
                        </div>
                        <div class="support-stat">
                            <i class="fas fa-clock"></i>
                            <span>Quick Response</span>
                        </div>
                        <div class="support-stat">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure & Safe</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Content -->
    <section class="py-4">
        <div class="container">
            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-6 mb-4">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle me-2"></i>Contact Information
                                </h5>
                                <p class="card-subtitle">Get in touch with us through multiple channels</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="contact-item">
                                        <div class="contact-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="contact-details">
                                            <h6>Address</h6>
                                            <p>300/A<br>Dehipagoda, Muruthagahamula</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="contact-item">
                                        <div class="contact-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="contact-details">
                                            <h6>Phone</h6>
                                            <p>+94 76 132 1604<br>+94 75 361 4324</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="contact-item">
                                        <div class="contact-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="contact-details">
                                            <h6>Email</h6>
                                            <p>swisswoodworks@gmail.com</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="contact-item">
                                        <div class="contact-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="contact-details">
                                            <h6>Hours</h6>
                                            <p>Mon-Fri: 9AM-6PM<br>Sat: 10AM-4PM</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQs -->
            <div class="row">
                <div class="col-12">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-question-circle me-2"></i>Frequently Asked Questions
                                </h5>
                                <p class="card-subtitle">Find quick answers to common questions</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="accordion" id="faqAccordion">
                                <?php foreach ($faqs as $index => $faq): ?>
                                    <div class="accordion-item modern-accordion-item">
                                        <h2 class="accordion-header" id="faq<?php echo $index; ?>">
                                            <button class="accordion-button modern-accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                                    type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse<?php echo $index; ?>" 
                                                    aria-expanded="<?php echo $index == 0 ? 'true' : 'false'; ?>" 
                                                    aria-controls="collapse<?php echo $index; ?>">
                                                <i class="fas fa-question-circle me-2"></i>
                                                <?php echo htmlspecialchars($faq['question']); ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" 
                                             class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" 
                                             aria-labelledby="faq<?php echo $index; ?>" 
                                             data-bs-parent="#faqAccordion">
                                            <div class="accordion-body modern-accordion-body">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <?php echo htmlspecialchars($faq['answer']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Swisswood Works</h5>
                    <p class="mb-3">Premium products and furniture designed for modern living and timeless elegance.</p>
                    <div class="d-flex gap-3">
                        <a href="https://www.facebook.com/share/14NGWUa5oYC/" target="_blank" class="text-decoration-none"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/swiss_woodworks?igsh=YXFmNnhrMGZ1azNy" target="_blank" class="text-decoration-none"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@swiss.woodworks?_t=ZS-90akuvM6Qhb&_r=1" target="_blank" class="text-decoration-none"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="shop.php" class="text-decoration-none">Shop</a></li>
                        <li><a href="orders.php" class="text-decoration-none">Orders</a></li>
                        <li><a href="profile.php" class="text-decoration-none">Profile</a></li>
                        <li><a href="support.php" class="text-decoration-none">Support</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6>Customer Service</h6>
                    <ul class="list-unstyled">
                        <li><a href="support.php" class="text-decoration-none">Contact Us</a></li>
                        <li><a href="support.php#faq" class="text-decoration-none">FAQs</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6>Contact Info</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i>300/A, Dehipagoda, Muruthagahamula</li>
                        <li><i class="fas fa-phone me-2"></i>+94 76 132 1604</li>
                        <li><i class="fas fa-envelope me-2"></i>swisswoodworks@gmail.com</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: #374151;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Swisswood Works. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html> 