<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$cart_count = 0;
if (isset($db) && isset($_SESSION['customer_id'])) {
    try {
        $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $_SESSION['customer_id'];
        $cart_count_result = $db->query($cart_count_sql);
        if ($cart_count_result) {
            $cart_count = $db->fetch_assoc($cart_count_result)['count'];
        }
    } catch (Exception $e) {
        $cart_count = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="customer-portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swisswood Works - Customer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/modern-customer.css" rel="stylesheet">
</head>


<?php $cpage = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <div class="brand-logo me-2">
                <img src="../libs/images/SW.png" alt="Swisswood Works Logo" class="logo-image">
            </div>
            <div class="brand-text">
                <span class="brand-name">Swisswood Works</span>
                <span class="brand-subtitle">Customer Portal</span>
            </div>
        </a>
        
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            
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
            
            <ul class="navbar-nav">
                <li class="nav-item me-3">
                    <a class="nav-link position-relative" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                </li>
                
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info d-none d-lg-block">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Customer'); ?></span>
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
                                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Customer'); ?></div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>