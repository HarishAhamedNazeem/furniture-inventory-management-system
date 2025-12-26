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

$customer_id = $_SESSION['customer_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Get order details
$order_sql = "SELECT * FROM orders WHERE id = " . $order_id . " AND customer_id = " . $customer_id;
$order_result = $db->query($order_sql);

if ($db->num_rows($order_result) == 0) {
    header('Location: orders.php');
    exit();
}

$order = $db->fetch_assoc($order_result);

// Get order items
$items_sql = "SELECT oi.*, p.name as product_name, p.images, p.sale_price 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = " . $order_id;
$items_result = $db->query($items_sql);

// Process items to extract images
$order_items = [];
while ($item = $db->fetch_assoc($items_result)) {
    // Process images from JSON
    if (!empty($item['images'])) {
        $images_array = json_decode($item['images'], true);
        if (is_array($images_array) && !empty($images_array)) {
            $item['image'] = $images_array[0]; // Use first image as primary
        } else {
            $item['image'] = null;
        }
    } else {
        $item['image'] = null;
    }
    $order_items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Swisswood Works</title>
    
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
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="py-5" style="background: var(--bg-ash);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-2" style="color: var(--primary-color); font-weight: 700;">Order Details</h1>
                    <p class="mb-0 text-muted">Order #<?php echo $order['id']; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="orders.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i>Back to Orders
                    </a>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Order Details Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Order Information -->
                <div class="col-lg-8">
                    <div class="modern-card mb-4">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-shopping-bag"></i>Order Items
                                </h5>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <?php if ($db->num_rows($items_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php 
                                                            $image_path = !empty($item['image']) ? "../uploads/products/" . $item['image'] : "../uploads/products/default.jpg";
                                                            ?>
                                                            <img src="<?php echo $image_path; ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                                 class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                                <small class="text-muted">Product ID: <?php echo $item['product_id']; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                                            <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No items found for this order.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="modern-card mb-4">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle"></i>Order Summary
                                </h5>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Order ID:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    #<?php echo $order['id']; ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Order Date:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Status:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Payment Status:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="badge <?php echo $order['payment_status'] == 'paid' ? 'bg-success' : ($order['payment_status'] == 'pending' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Subtotal:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    LKR <?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Shipping:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    LKR 0.00
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Total:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-shipping-fast"></i>Shipping Information
                                </h5>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <p class="mb-2"><strong>Address:</strong></p>
                            <p class="text-muted mb-3">
                                <?php echo htmlspecialchars($order['shipping_address'] ?? 'Not provided'); ?>
                            </p>
                            
                            <?php if (!empty($order['notes'])): ?>
                                <p class="mb-2"><strong>Notes:</strong></p>
                                <p class="text-muted"><?php echo htmlspecialchars($order['notes']); ?></p>
                            <?php endif; ?>
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