<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/db.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$order_id = $_GET['id'];

// Debug logging
error_log("Order Confirmation: Customer ID: $customer_id, Order ID: $order_id");

// Get order details
$order_sql = "SELECT o.*, c.name as customer_name, c.email 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              WHERE o.id = ? AND o.customer_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $customer_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

// Debug logging
error_log("Order Confirmation: Order found: " . ($order ? 'Yes' : 'No'));
if ($order) {
    error_log("Order Confirmation: Order details - ID: " . $order['id'] . ", Number: " . $order['order_number'] . ", Total: " . $order['total_amount']);
}

if(!$order) {
    error_log("Order Confirmation: Order not found, redirecting to dashboard");
    header('Location: dashboard.php');
    exit();
}

// Get order items
$items_sql = "SELECT oi.*, p.name, p.images 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items_result = $items_stmt->get_result();

// Process order items to extract images
$order_items = [];
while ($item = $order_items_result->fetch_assoc()) {
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

// Initialize variables from the fetched order
$order_number = isset($order['order_number']) ? $order['order_number'] : $order['id'];
$order_date = isset($order['created_at']) ? $order['created_at'] : '';
$order_total = isset($order['total_amount']) ? $order['total_amount'] : 0;
$order_status = isset($order['status']) ? $order['status'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Variables -->
    <style>
        :root {
            --primary-color: #a65517;
            --primary-dark: #8d4a14;
        }
    </style>
</head>
<body class="cool-theme">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

<style>
    body.cool-theme {
        min-height: 100vh;
        background-color: #b7bdbb;
        font-family: 'Segoe UI', 'Roboto', 'Arial', sans-serif;
    }
    .confirmation-card {
        max-width: 540px;
        margin: 48px auto 32px auto;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 32px 0 rgba(80, 45, 10, 0.10);
        border: 1.5px solid #e0c9a6;
        padding: 2.5rem 2rem 2rem 2rem;
        text-align: center;
    }
    .confirmation-check {
        font-size: 3.5rem;
        color: var(--primary-color);
        background: #f3e7d3;
        border-radius: 50%;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.2rem auto;
        box-shadow: 0 2px 8px 0 rgba(80, 45, 10, 0.10);
    }
    .confirmation-title {
        color: #7c4a03;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .confirmation-message {
        color: #8d5524;
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }
    .order-details {
        background: #f9f6f2;
        border-radius: 14px;
        padding: 1.2rem 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px 0 rgba(80, 45, 10, 0.07);
        border: 1.5px solid #e0c9a6;
        text-align: left;
    }
    .order-details-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 1.05rem;
    }
    .order-details-row strong {
        color: var(--primary-color);
    }
    .order-items-table {
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 8px 0 rgba(80, 45, 10, 0.07);
        margin-bottom: 1.5rem;
    }
    .order-items-table th {
        color: var(--primary-color);
        font-weight: 600;
        background: #f3e7d3;
        border-bottom: 2px solid #e0c9a6;
    }
    .order-items-table td, .order-items-table th {
        vertical-align: middle;
        padding: 0.85rem 0.75rem;
    }
    .btn, .btn-primary {
        font-weight: 600;
        border-radius: 8px;
        background: var(--primary-color) !important;
        color: #fff;
        border: none;
        transition: background 0.2s, color 0.2s;
        margin: 0.2rem 0.3rem;
    }
    .btn:hover, .btn-primary:hover, .btn-primary:focus {
        background: var(--primary-dark) !important;
        color: #fff;
    }
    @media (max-width: 767px) {
        .confirmation-card {
            padding: 1.2rem 0.5rem 1.2rem 0.5rem;
        }
        .order-details {
            padding: 0.7rem 0.3rem;
        }
    }
</style>

<div class="confirmation-card">
    <div class="confirmation-check"><i class="fa fa-check"></i></div>
    <div class="confirmation-title">Thank You for Your Order!</div>
    <div class="confirmation-message">Your order has been placed successfully.<br>We appreciate your business.</div>
    <div class="order-details">
        <div class="order-details-row"><strong>Order #:</strong> <span><?php echo htmlspecialchars($order_number); ?></span></div>
        <div class="order-details-row"><strong>Date:</strong> <span><?php echo htmlspecialchars($order_date); ?></span></div>
                                <div class="order-details-row"><strong>Total:</strong> <span>LKR <?php echo number_format($order_total, 2); ?></span></div>
        <div class="order-details-row"><strong>Status:</strong> <span><?php echo ucfirst($order_status); ?></span></div>
    </div>
    <div class="table-responsive">
        <table class="table order-items-table align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($order_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                                                <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                            <td>LKR <?php echo number_format(isset($item['subtotal']) ? $item['subtotal'] : ($item['price'] * $item['quantity']), 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-home me-1"></i>Back to Dashboard</a>
    <a href="shop.php" class="btn btn-primary"><i class="fa fa-store me-1"></i>Continue Shopping</a>
</div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html> 