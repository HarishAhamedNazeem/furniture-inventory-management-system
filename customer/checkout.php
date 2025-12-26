<?php
session_start();

// Check if this is an AJAX request - need to check early
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Turn off output buffering for AJAX requests to prevent any output before JSON
if ($isAjax) {
    ob_clean();
}

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('includes/promotions.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit();
    }
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Generate CSRF token for security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get customer details
$customer_sql = "SELECT * FROM customers WHERE id = " . $customer_id;
$customer_result = $db->query($customer_sql);
$customer = $db->fetch_assoc($customer_result);

// Get cart items with promotions
$cart_sql = "SELECT c.*, p.name, p.sale_price, p.quantity as available_quantity, p.categorie_id, p.images 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.customer_id = " . $customer_id;
$cart_result = $db->query($cart_sql);

if ($db->num_rows($cart_result) == 0) {
    header('Location: cart.php');
    exit();
}

// Calculate total with promotions
$total = 0;
$original_total = 0;
$total_discount = 0;
$items = [];

while($item = $db->fetch_assoc($cart_result)) {
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
    
    // Get promotions for this product
    $promotions = getProductPromotions($item['product_id'], $item['categorie_id']);
    $promotion_info = getPromotionInfo($item['sale_price'], $promotions);
    
    if ($promotion_info) {
        $item['original_price'] = $item['sale_price'];
        $item['sale_price'] = $promotion_info['discounted_price'];
        $item['promotion'] = $promotion_info['promotion'];
        $item['discount_per_item'] = $promotion_info['discount_amount'];
    } else {
        $item['original_price'] = $item['sale_price'];
        $item['discount_per_item'] = 0;
    }
    
    $item_total = $item['quantity'] * $item['sale_price'];
    $original_item_total = $item['quantity'] * $item['original_price'];
    $item_discount = $original_item_total - $item_total;
    
    $total += $item_total;
    $original_total += $original_item_total;
    $total_discount += $item_discount;
    
    $items[] = $item;
}

// Initialize variables
$error = '';
$success = '';
$errors = [];
$form_data = [];

// Define shipping options
$shipping_options = [
    'standard' => ['name' => 'Standard Delivery', 'cost' => 0, 'days' => '3-5 business days'],
    'express' => ['name' => 'Express Delivery', 'cost' => 500, 'days' => '1-2 business days'],
    'overnight' => ['name' => 'Overnight Delivery', 'cost' => 1000, 'days' => 'Next business day']
];

// Tax removed as requested

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug logging
    error_log("Checkout: POST request received");
    error_log("Checkout: POST data: " . print_r($_POST, true));
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request. Please try again.';
        
        // Handle AJAX error response for CSRF failure
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request. Please try again.',
                'error' => 'CSRF token validation failed'
            ]);
            exit();
        }
    } else {
        // Sanitize and validate form data
        $form_data = [
            'shipping_address' => trim(filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_STRING)),
            'payment_method' => filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING),
            'shipping_method' => filter_input(INPUT_POST, 'shipping_method', FILTER_SANITIZE_STRING),
            'phone_number' => trim(filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING)),
            'card_number' => trim(filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING)),
            'expiry_date' => trim(filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING)),
            'cvv' => trim(filter_input(INPUT_POST, 'cvv', FILTER_SANITIZE_STRING)),
            'cardholder_name' => trim(filter_input(INPUT_POST, 'cardholder_name', FILTER_SANITIZE_STRING))
        ];
        
        // Validation
        if(empty($form_data['shipping_address'])) {
            $errors[] = 'Please enter a valid shipping address';
        } elseif(strlen($form_data['shipping_address']) < 10) {
            $errors[] = 'Shipping address must be at least 10 characters long';
        }
        
        if(empty($form_data['payment_method'])) {
            $errors[] = 'Please select a payment method';
        }
        
        if(empty($form_data['shipping_method'])) {
            $errors[] = 'Please select a shipping method';
        }
        
        if(empty($form_data['phone_number'])) {
            $errors[] = 'Please enter your phone number';
        } elseif(!preg_match('/^[0-9+\-\s()]{10,15}$/', $form_data['phone_number'])) {
            $errors[] = 'Please enter a valid phone number';
        }
        
        // Validate card details if credit card is selected
        if($form_data['payment_method'] === 'credit_card') {
            if(empty($form_data['card_number'])) {
                $errors[] = 'Please enter your card number';
            } elseif(!preg_match('/^[0-9\s]{16,19}$/', str_replace(' ', '', $form_data['card_number']))) {
                $errors[] = 'Please enter a valid 16-digit card number';
            }
            
            if(empty($form_data['expiry_date'])) {
                $errors[] = 'Please enter card expiry date';
            } elseif(!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $form_data['expiry_date'])) {
                $errors[] = 'Please enter expiry date in MM/YY format';
            }
            
            if(empty($form_data['cvv'])) {
                $errors[] = 'Please enter CVV';
            } elseif(!preg_match('/^[0-9]{3,4}$/', $form_data['cvv'])) {
                $errors[] = 'Please enter a valid CVV (3 or 4 digits)';
            }
            
            if(empty($form_data['cardholder_name'])) {
                $errors[] = 'Please enter cardholder name';
            } elseif(strlen($form_data['cardholder_name']) < 2) {
                $errors[] = 'Please enter a valid cardholder name';
            }
        }
        
        if(empty($errors)) {
            // Debug logging
            error_log("Checkout: Form validation passed");
            
            // Calculate shipping cost
            $shipping_cost = $shipping_options[$form_data['shipping_method']]['cost'];
            
            // Calculate final total (no tax, no discounts)
            $subtotal = $total;
            $final_total = $subtotal + $shipping_cost;
            
            // Debug logging
            error_log("Checkout: Starting order processing for customer ID: $customer_id");
            error_log("Checkout: Final total: $final_total, Shipping cost: $shipping_cost");
            
            // Start transaction
            $db->query("START TRANSACTION");
            
            try {
                // Generate order number
                $order_number = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                
                // Check if order number exists (very unlikely but let's be safe)
                $check_order_sql = "SELECT id FROM orders WHERE order_number = '" . $db->escape($order_number) . "'";
                $check_result = $db->query($check_order_sql);
                while ($db->num_rows($check_result) > 0) {
                    $order_number = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $check_order_sql = "SELECT id FROM orders WHERE order_number = '" . $db->escape($order_number) . "'";
                    $check_result = $db->query($check_order_sql);
                }
                
                // Create order with simplified details
                $order_sql = "INSERT INTO orders (order_number, customer_id, total_amount, discount_amount, 
                             status, payment_status, payment_method, shipping_address, 
                             shipping_method, shipping_cost, phone_number, created_at, updated_at) 
                             VALUES ('" . $db->escape($order_number) . "', " . (int)$customer_id . ", " . (float)$final_total . ", " . (float)$total_discount . ", 
                             'pending', 'pending', '" . $db->escape($form_data['payment_method']) . "', '" . $db->escape($form_data['shipping_address']) . "', 
                             '" . $db->escape($form_data['shipping_method']) . "', " . (float)$shipping_cost . ", '" . $db->escape($form_data['phone_number']) . "', NOW(), NOW())";
                
                if (!$db->query($order_sql)) {
                    throw new Exception('Failed to create order: ' . $db->con->error);
                }
                
                $order_id = $db->insert_id();
                
                // Add order items
                foreach($items as $item) {
                    // Check if enough stock
                    if($item['quantity'] > $item['available_quantity']) {
                        throw new Exception("Not enough stock for " . $item['name']);
                    }
                    
                    // Add order item with promotional price
                    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                VALUES ({$order_id}, {$item['product_id']}, {$item['quantity']}, {$item['sale_price']})";
                    if (!$db->query($item_sql)) {
                        throw new Exception("Failed to add order item: " . $db->con->error);
                    }
                    
                    // Update product quantity
                    $update_sql = "UPDATE products SET quantity = quantity - {$item['quantity']} WHERE id = {$item['product_id']}";
                    if (!$db->query($update_sql)) {
                        throw new Exception("Failed to update product quantity: " . $db->con->error);
                    }
                }
            
                // Clear cart
                $clear_sql = "DELETE FROM cart WHERE customer_id = {$customer_id}";
                if (!$db->query($clear_sql)) {
                    throw new Exception("Failed to clear cart: " . $db->con->error);
                }
                
                // Create customer notification
                $customer_notification_sql = "INSERT INTO notifications (customer_id, order_id, type, title, message, created_at) 
                                             VALUES ({$customer_id}, {$order_id}, 'order_status', 
                                             'Order Placed Successfully', 
                                             'Your order #{$order_number} has been placed successfully. Total amount: LKR " . number_format($final_total, 2) . ". We will process your order shortly.', 
                                             NOW())";
                if (!$db->query($customer_notification_sql)) {
                    throw new Exception("Failed to create customer notification: " . $db->con->error);
                }
                
                // Create admin notification
                $admin_notification_sql = "INSERT INTO admin_notifications (order_id, type, title, message, created_at) 
                                          VALUES ({$order_id}, 'new_order', 
                                          'New Order Received', 
                                          'A new order #{$order_number} has been placed by {$customer['name']} ({$customer['email']}). Total amount: LKR " . number_format($final_total, 2) . ". Please review and process the order.', 
                                          NOW())";
                if (!$db->query($admin_notification_sql)) {
                    throw new Exception("Failed to create admin notification: " . $db->con->error);
                }
                
                // Create initial order status history
                $status_history_sql = "INSERT INTO order_status_history (order_id, status, payment_status, notes, created_at) 
                                      VALUES ({$order_id}, 'pending', 'pending', 'Order placed via online checkout', NOW())";
                if (!$db->query($status_history_sql)) {
                    throw new Exception("Failed to create order status history: " . $db->con->error);
                }
            
                // Commit transaction
                $db->query("COMMIT");
                
                // Send order confirmation email
                try {
                    require_once('../includes/email_functions.php');
                    
                    // Get order details for email
                    $order_details = [
                        'id' => $order_id,
                        'order_number' => $order_number,
                        'created_at' => date('Y-m-d H:i:s'),
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'subtotal' => $subtotal,
                        'discount_amount' => $total_discount,
                        'total_amount' => $final_total
                    ];
                    
                    // Send order confirmation email
                    $email_sent = sendOrderConfirmationEmail($order_details, $customer, $items);
                    
                    if ($email_sent) {
                        error_log("Order confirmation email sent successfully for order #{$order_number}");
                    } else {
                        error_log("Failed to send order confirmation email for order #{$order_number}");
                    }
                    
                    // Send admin notification email
                    $admin_message = "A new order #{$order_number} has been placed by {$customer['name']} ({$customer['email']}). Total amount: LKR " . number_format($final_total, 2) . ". Please review and process the order.";
                    $admin_data = [
                        'order_number' => $order_number,
                        'customer_name' => $customer['name'],
                        'customer_email' => $customer['email'],
                        'total_amount' => $final_total,
                        'order_id' => $order_id
                    ];
                    
                    sendAdminNotificationEmail('New Order Received', $admin_message, $admin_data);
                    
                } catch (Exception $email_error) {
                    // Log email error but don't fail the order
                    error_log("Email sending error: " . $email_error->getMessage());
                }
                
                // Redirect to order confirmation
                error_log("Checkout: Order created successfully, redirecting to order confirmation with ID: $order_id");
                
                // Handle AJAX vs regular requests
                if ($isAjax) {
                    // Return JSON response for AJAX
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'order_id' => $order_id,
                        'redirect' => 'order_confirmation.php?id=' . $order_id,
                        'message' => 'Order created successfully'
                    ]);
                    exit();
                } else {
                    // Clear any output buffer to ensure clean redirect
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    header('Location: order_confirmation.php?id=' . $order_id);
                    exit();
                }
                
            } catch(Exception $e) {
                // Rollback transaction on error
                $db->query("ROLLBACK");
                error_log("Checkout: Exception occurred: " . $e->getMessage());
                error_log("Checkout: Exception trace: " . $e->getTraceAsString());
                $errors[] = $e->getMessage();
                
                // Handle AJAX error response
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'message' => 'Order processing failed'
                    ]);
                    exit();
                }
            }
        } else {
            // Debug logging
            error_log("Checkout: Form validation failed with errors: " . implode(', ', $errors));
            
            // Handle AJAX error response
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'errors' => $errors
                ]);
                exit();
            }
            
            // Set error message for display
            $error = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
    body.cool-theme {
        min-height: 100vh;
        background-color: #b7bdbb;
        font-family: 'Segoe UI', 'Roboto', 'Arial', sans-serif;
    }
    .checkout-section-header {
        background: var(--primary-color);
        color: #fff;
        border-radius: 18px;
        padding: 28px 32px 18px 32px;
        margin-bottom: 32px;
        box-shadow: 0 8px 32px 0 rgba(80, 45, 10, 0.10);
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .checkout-section-header .icon {
        font-size: 2.2rem;
        background: #f3e7d3;
        color: var(--primary-color);
        border-radius: 50%;
        padding: 12px 16px;
        box-shadow: 0 2px 8px 0 rgba(80, 45, 10, 0.10);
    }
    .checkout-section-header h2 {
        margin: 0;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .checkout-card, .order-summary {
        border-radius: 16px;
        border: 1.5px solid #e0c9a6;
        box-shadow: 0 4px 16px 0 rgba(80, 45, 10, 0.07);
        background: #f9f6f2;
        margin-bottom: 24px;
    }
    .checkout-card .card-body, .order-summary .card-body {
        padding: 2rem 1.5rem;
    }
    .checkout-card-title {
        color: var(--primary-color);
        font-weight: 600;
        letter-spacing: 0.5px;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-label {
        color: #8d5524;
        font-weight: 500;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1.5px solid #e0c9a6;
        background: #fff;
        color: #4e2e0e;
        font-weight: 500;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(166,85,23,.10);
    }
    .order-summary {
        background: #fff;
    }
    .order-summary .summary-title {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .order-summary .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.7rem;
        font-size: 1.05rem;
    }
    .order-summary .summary-row.total {
        font-weight: 700;
        color: #7c4a03;
        font-size: 1.15rem;
    }
    .badge {
        background: #e0c9a6;
        color: var(--primary-color);
        font-size: 0.98rem;
        font-weight: 500;
        border-radius: 8px;
        padding: 0.3em 0.7em;
    }
    .badge.text-success {
        background: #d4edda;
        color: #155724;
    }
    .btn, .btn-primary {
        font-weight: 600;
        border-radius: 8px;
        background: var(--primary-color) !important;
        color: #fff;
        border: none;
        transition: background 0.2s, color 0.2s;
    }
    .btn:hover, .btn-primary:hover, .btn-primary:focus {
        background: var(--primary-dark) !important;
        color: #fff;
    }
    .text-success {
        color: #155724 !important;
    }
    .text-danger {
        color: #dc3545 !important;
    }
    .text-decoration-line-through {
        text-decoration: line-through !important;
    }
    .fw-bold {
        font-weight: 700 !important;
    }
    .small {
        font-size: 0.875em !important;
    }
    .alert {
        font-size: 1rem;
        padding: 0.75rem 1rem;
        color: #4e2e0e;
        border-radius: 8px;
        background: #f3e7d3;
        border: 1.5px solid #e0c9a6;
    }
    
    /* Card Details Styling */
    #cardDetails {
        background: #fff;
        border: 1px solid #e0c9a6;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    #cardDetails .form-control {
        border: 1.5px solid #e0c9a6;
        transition: border-color 0.3s ease;
    }
    
    #cardDetails .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(166,85,23,.10);
    }
    
    #cardDetails .form-control.is-invalid {
        border-color: #dc3545;
    }
    
    .card-icon {
        display: inline-block;
        width: 30px;
        height: 20px;
        background: #f0f0f0;
        border-radius: 3px;
        margin-right: 8px;
        vertical-align: middle;
    }
    @media (max-width: 767px) {
        .checkout-section-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 18px 10px 10px 10px;
        }
        .checkout-section-header .icon {
            font-size: 1.5rem;
            padding: 7px 10px;
        }
        .checkout-card .card-body, .order-summary .card-body {
            padding: 1rem 0.5rem;
        }
    }
</style>
</head>
<body class="cool-theme">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="checkout-section-header mb-4">
    <span class="icon"><i class="fa fa-credit-card"></i></span>
    <div>
        <h2>Checkout</h2>
        <div class="text-muted" style="font-size:1.1rem;">Enter your shipping and payment details</div>
    </div>
</div>

<!-- Order Summary -->
<div class="row">
    <div class="col-md-12">
        <div class="order-summary">
            <div class="card-body">
                <div class="summary-title"><i class="fa fa-receipt"></i> Order Summary</div>
                <hr>
                
                <!-- Items List -->
                <div class="mb-3">
                    <?php foreach($items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                <?php echo htmlspecialchars($item['name']); ?> 
                                <small class="text-muted">x<?php echo $item['quantity']; ?></small>
                                <?php if ($item['discount_per_item'] > 0): ?>
                                    <br><small class="text-success"><?php echo $item['promotion']['discount_value']; ?>% OFF</small>
                                <?php endif; ?>
                            </span>
                            <span>
                                <?php if ($item['discount_per_item'] > 0): ?>
                                    <div class="text-danger fw-bold">LKR <?php echo number_format($item['quantity'] * $item['sale_price'], 2); ?></div>
                                    <div class="text-decoration-line-through text-muted small">LKR <?php echo number_format($item['quantity'] * $item['original_price'], 2); ?></div>
                                <?php else: ?>
                                    LKR <?php echo number_format($item['quantity'] * $item['sale_price'], 2); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <hr>
                
                <!-- Price Breakdown -->
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="badge">LKR <span id="subtotal"><?php echo number_format($original_total, 2); ?></span></span>
                </div>
                
                <?php if ($total_discount > 0): ?>
                <div class="summary-row">
                    <span class="text-success">Product Discount</span>
                    <span class="badge text-success">-LKR <span id="productDiscount"><?php echo number_format($total_discount, 2); ?></span></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="badge">LKR <span id="shippingCost">0.00</span></span>
                </div>
                
                <hr>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>LKR <span id="finalTotal"><?php echo number_format($total, 2); ?></span></span>
                </div>
                
                <!-- Security Badges -->
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fa fa-lock"></i> Secure SSL Encryption<br>
                        <i class="fa fa-shield"></i> Protected by Swisswood Works
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shipping Details -->
<div class="row">
    <div class="col-md-12">
        <div class="checkout-card">
            <div class="card-body">
                <h5 class="checkout-card-title"><i class="fa fa-truck"></i> Shipping Details</h5>
                <hr>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="checkoutForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Contact Information -->
                    <div class="mb-4">
                        <h6 class="checkout-card-title"><i class="fa fa-phone"></i> Contact Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                           value="<?php echo htmlspecialchars($form_data['phone_number'] ?? $customer['phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" readonly>
                                    <small class="text-muted">Email notifications will be sent to this address</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="mb-4">
                        <h6 class="checkout-card-title"><i class="fa fa-map-marker"></i> Shipping Address</h6>
                        <div class="form-group">
                            <label for="shipping_address" class="form-label">Complete Address *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                      rows="3" required placeholder="Street address, City, Postal Code, Country"><?php echo htmlspecialchars($form_data['shipping_address'] ?? $customer['address'] ?? ''); ?></textarea>
                            <small class="text-muted">Include apartment number, building name, etc.</small>
                        </div>
                    </div>
                    
                    <!-- Shipping Method -->
                    <div class="mb-4">
                        <h6 class="checkout-card-title"><i class="fa fa-truck"></i> Shipping Method</h6>
                        <?php foreach($shipping_options as $key => $option): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="shipping_method" id="shipping_<?php echo $key; ?>" 
                                   value="<?php echo $key; ?>" <?php echo ($form_data['shipping_method'] ?? 'standard') === $key ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="shipping_<?php echo $key; ?>">
                                <strong><?php echo $option['name']; ?></strong>
                                <span class="text-muted">(<?php echo $option['days']; ?>)</span>
                                <?php if($option['cost'] > 0): ?>
                                    <span class="badge badge-secondary">LKR <?php echo number_format($option['cost'], 2); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success">FREE</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="mb-4">
                        <h6 class="checkout-card-title"><i class="fa fa-credit-card"></i> Payment Method</h6>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="credit_card" 
                                   value="credit_card" <?php echo ($form_data['payment_method'] ?? '') === 'credit_card' ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="credit_card">
                                <i class="fa fa-credit-card"></i> Credit/Debit Card
                                <small class="text-muted d-block">Visa, Mastercard, American Express</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery" 
                                   value="cash_on_delivery" <?php echo ($form_data['payment_method'] ?? 'cash_on_delivery') === 'cash_on_delivery' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cash_on_delivery">
                                <i class="fa fa-money"></i> Cash on Delivery
                                <small class="text-muted d-block">Pay when your order arrives</small>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Card Details (shown when credit card is selected) -->
                    <div class="mb-4" id="cardDetails" style="display: none;">
                        <h6 class="checkout-card-title"><i class="fa fa-credit-card"></i> Card Details</h6>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="card_number" class="form-label">Card Number *</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" 
                                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                                    <small class="text-muted">Enter your 16-digit card number</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date *</label>
                                    <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                           placeholder="MM/YY" maxlength="5" required>
                                    <small class="text-muted">Format: MM/YY</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="cvv" class="form-label">CVV *</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" 
                                           placeholder="123" maxlength="4" required>
                                    <small class="text-muted">3 or 4 digit security code</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="cardholder_name" class="form-label">Cardholder Name *</label>
                                    <input type="text" class="form-control" id="cardholder_name" name="cardholder_name" 
                                           placeholder="Name as it appears on card" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Notice -->
                        <div class="alert alert-info">
                            <i class="fa fa-shield"></i>
                            <strong>Secure Payment:</strong> Your card details are encrypted and processed securely. We do not store your card information.
                        </div>
                    </div>
                    
                    
                    <button type="button" class="btn btn-primary btn-lg btn-block" id="submitOrderBtn" onclick="submitOrder()">
                        <i class="fa fa-lock"></i> Place Secure Order
                    </button>
                    
                    <!-- Hidden submit button as backup -->
                    <button type="submit" id="hiddenSubmitBtn" style="display: none;"></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Order Confirmation Modal -->
<div class="modal fade" id="orderConfirmationModal" tabindex="-1" aria-labelledby="orderConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-color); color: white;">
                <h5 class="modal-title" id="orderConfirmationModalLabel">
                    <i class="fa fa-check-circle"></i> Confirm Your Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>Please review your order details before confirming:</strong>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fa fa-shopping-cart"></i> Order Summary</h6>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="modalSubtotal">LKR 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Product Discount:</span>
                                <span id="modalProductDiscount">LKR 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span id="modalShipping">LKR 0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total Amount:</span>
                                <span id="modalTotal" style="color: var(--primary-color);">LKR 0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fa fa-truck"></i> Delivery Details</h6>
                        <div class="border rounded p-3 mb-3">
                            <div class="mb-2">
                                <strong>Shipping Method:</strong><br>
                                <span id="modalShippingMethod">Not selected</span>
                            </div>
                            <div class="mb-2">
                                <strong>Payment Method:</strong><br>
                                <span id="modalPaymentMethod">Not selected</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Important:</strong> By clicking "Confirm Order", you agree to our terms and conditions. 
                    This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmOrderBtn">
                    <i class="fa fa-check"></i> Confirm Order
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Shipping options data
    const shippingOptions = {
        'standard': { cost: 0, name: 'Standard Delivery' },
        'express': { cost: 500, name: 'Express Delivery' },
        'overnight': { cost: 1000, name: 'Overnight Delivery' }
    };
    
    
    // Base values
    const subtotal = <?php echo $original_total; ?>;
    const productDiscount = <?php echo $total_discount; ?>;
    
    // Update totals when shipping method changes
    function updateTotals() {
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        const shippingCost = selectedShipping ? shippingOptions[selectedShipping.value].cost : 0;
        
        const finalTotal = subtotal - productDiscount + shippingCost;
        
        // Update display
        document.getElementById('shippingCost').textContent = shippingCost.toFixed(2);
        document.getElementById('finalTotal').textContent = finalTotal.toFixed(2);
        
        // Update product discount display if it exists
        const productDiscountElement = document.getElementById('productDiscount');
        if (productDiscountElement) {
            productDiscountElement.textContent = productDiscount.toFixed(2);
        }
    }
    

    
    // Show/hide card details based on payment method
    function toggleCardDetails() {
        const creditCardRadio = document.getElementById('credit_card');
        const cardDetailsDiv = document.getElementById('cardDetails');
        
        if (creditCardRadio && creditCardRadio.checked) {
            cardDetailsDiv.style.display = 'block';
            // Make card fields required
            document.querySelectorAll('#cardDetails input').forEach(input => {
                input.required = true;
                input.removeAttribute('disabled');
            });
        } else {
            cardDetailsDiv.style.display = 'none';
            // Remove required from card fields and disable them
            document.querySelectorAll('#cardDetails input').forEach(input => {
                input.required = false;
                input.disabled = true;
                input.value = ''; // Clear values when hidden
            });
        }
    }
    
    // Format card number with spaces
    function formatCardNumber(input) {
        let value = input.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
        input.value = formattedValue;
    }
    
    // Format expiry date
    function formatExpiryDate(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        input.value = value;
    }
    
    // Format CVV (numbers only)
    function formatCVV(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value.length > 4) {
            input.value = input.value.substring(0, 4);
        }
    }
    
    // Form validation
    function validateForm() {
        const requiredFields = [
            'shipping_address',
            'payment_method',
            'shipping_method',
            'phone_number'
        ];
        
        let isValid = true;
        let errorMessages = [];
        
        // Check required fields
        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (!field || (!field.value.trim() && !field.disabled)) {
                isValid = false;
                field.classList.add('is-invalid');
                errorMessages.push(`${fieldName.replace('_', ' ')} is required`);
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Phone number validation
        const phoneField = document.querySelector('[name="phone_number"]');
        if (phoneField && phoneField.value.trim()) {
            const phonePattern = /^[0-9+\-\s()]{10,15}$/;
            if (!phonePattern.test(phoneField.value)) {
                isValid = false;
                phoneField.classList.add('is-invalid');
                errorMessages.push('Please enter a valid phone number');
            } else {
                phoneField.classList.remove('is-invalid');
            }
        }
        
        // Card validation if credit card is selected
        const creditCardRadio = document.getElementById('credit_card');
        if (creditCardRadio && creditCardRadio.checked) {
            const cardNumber = document.querySelector('[name="card_number"]');
            const expiryDate = document.querySelector('[name="expiry_date"]');
            const cvv = document.querySelector('[name="cvv"]');
            const cardholderName = document.querySelector('[name="cardholder_name"]');
            
            // Card number validation
            if (!cardNumber.value.trim() && !cardNumber.disabled) {
                isValid = false;
                cardNumber.classList.add('is-invalid');
                errorMessages.push('Card number is required');
            } else if (!cardNumber.disabled) {
                const cleanNumber = cardNumber.value.replace(/\s/g, '');
                if (!/^[0-9]{16}$/.test(cleanNumber)) {
                    isValid = false;
                    cardNumber.classList.add('is-invalid');
                    errorMessages.push('Please enter a valid 16-digit card number');
                } else {
                    cardNumber.classList.remove('is-invalid');
                }
            }
            
            // Expiry date validation
            if (!expiryDate.value.trim() && !expiryDate.disabled) {
                isValid = false;
                expiryDate.classList.add('is-invalid');
                errorMessages.push('Expiry date is required');
            } else if (!expiryDate.disabled && !/^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(expiryDate.value)) {
                isValid = false;
                expiryDate.classList.add('is-invalid');
                errorMessages.push('Please enter expiry date in MM/YY format');
            } else if (!expiryDate.disabled) {
                expiryDate.classList.remove('is-invalid');
            }
            
            // CVV validation
            if (!cvv.value.trim() && !cvv.disabled) {
                isValid = false;
                cvv.classList.add('is-invalid');
                errorMessages.push('CVV is required');
            } else if (!cvv.disabled && !/^[0-9]{3,4}$/.test(cvv.value)) {
                isValid = false;
                cvv.classList.add('is-invalid');
                errorMessages.push('Please enter a valid CVV (3 or 4 digits)');
            } else if (!cvv.disabled) {
                cvv.classList.remove('is-invalid');
            }
            
            // Cardholder name validation
            if (!cardholderName.value.trim() && !cardholderName.disabled) {
                isValid = false;
                cardholderName.classList.add('is-invalid');
                errorMessages.push('Cardholder name is required');
            } else if (!cardholderName.disabled && cardholderName.value.trim().length < 2) {
                isValid = false;
                cardholderName.classList.add('is-invalid');
                errorMessages.push('Please enter a valid cardholder name');
            } else if (!cardholderName.disabled) {
                cardholderName.classList.remove('is-invalid');
            }
        }
        
        // Log validation results for debugging
        console.log('Form validation result:', isValid);
        if (!isValid) {
            console.log('Validation errors:', errorMessages);
        }
        
        return isValid;
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Checkout page loaded');
        
        // Debug: Check if form exists
        const form = document.getElementById('checkoutForm');
        if (form) {
            console.log('Form found:', form);
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
        } else {
            console.error('Form not found!');
        }
        
        // Debug: Check if submit button exists
        const submitBtnElement = document.getElementById('submitOrderBtn');
        if (submitBtnElement) {
            console.log('Submit button found:', submitBtnElement);
        } else {
            console.error('Submit button not found!');
        }
        
        // Add event listeners
        document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
            radio.addEventListener('change', updateTotals);
        });
        
        // Payment method change listeners
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', toggleCardDetails);
        });
        
        // Card input formatting
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function() {
                formatCardNumber(this);
            });
        }
        
        const expiryDateInput = document.getElementById('expiry_date');
        if (expiryDateInput) {
            expiryDateInput.addEventListener('input', function() {
                formatExpiryDate(this);
            });
        }
        
        const cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', function() {
                formatCVV(this);
            });
        }
        
        // Initialize card details visibility
        toggleCardDetails();
        
        
        const submitBtn = document.getElementById('submitOrderBtn');
        if (submitBtn) {
            console.log('Adding click handler to submit button');
            submitBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked at:', new Date().toISOString());
                console.log('Button disabled state:', this.disabled);
                console.log('Form data:', new FormData(form));
                
                
                console.log('Running form validation...');
                const isValid = validateForm();
                console.log('Form validation result:', isValid);
                
                
                setTimeout(function() {
                    if (submitBtn.disabled) {
                        console.log('Manual form submission triggered after timeout');
                        form.submit();
                    }
                }, 3000);
            });
        } else {
            console.error('Submit button not found!');
        }
        
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            console.log('Form submission started');
            
            if (!validateForm()) {
                e.preventDefault();
                console.log('Form validation failed');
                alert('Please fill in all required fields correctly');
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Show success message
            console.log('Order is being processed. You will be redirected to the confirmation page shortly.');
            
            // Re-enable button after 10 seconds as fallback
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                console.log('Button re-enabled after timeout');
            }, 10000);
            
            // Allow form to submit normally
            return true;
        });
        
        // Initialize totals
        updateTotals();
        
        // Auto-save form data to localStorage
        const formInputs = document.querySelectorAll('#checkoutForm input, #checkoutForm textarea, #checkoutForm select');
        formInputs.forEach(input => {
            // Load saved data
            const savedValue = localStorage.getItem(`checkout_${input.name}`);
            if (savedValue && !input.value) {
                input.value = savedValue;
            }
            
            // Save data on change
            input.addEventListener('change', function() {
                localStorage.setItem(`checkout_${this.name}`, this.value);
            });
        });
        
        // Clear saved data on successful submission
        window.addEventListener('beforeunload', function() {
            if (document.querySelector('#checkoutForm').checkValidity()) {
                formInputs.forEach(input => {
                    localStorage.removeItem(`checkout_${input.name}`);
                });
            }
        });
    });
    
    // Address autocomplete (placeholder for Google Places API)
    function initAddressAutocomplete() {
        // This would integrate with Google Places API in a real implementation
        const addressField = document.getElementById('shipping_address');
        
        // Simple validation
        addressField.addEventListener('blur', function() {
            if (this.value.length < 10) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Initialize address autocomplete
    initAddressAutocomplete();
    
     // Comprehensive submit order function with confirmation popup
     async function submitOrder() {
         console.log('=== CHECKOUT SUBMISSION STARTED ===');
         
         const submitBtn = document.getElementById('submitOrderBtn');
         const form = document.getElementById('checkoutForm');
         
         if (!form) {
             console.error('Form not found!');
             alert('Error: Form not found. Please refresh the page and try again.');
             return false;
         }
         
         // Step 1: Validate form first
         console.log('Step 1: Validating form...');
         if (!validateForm()) {
             console.log('Form validation failed');
             alert('Please fill in all required fields correctly');
             return false;
         }
         
         console.log('Form validation passed');
         
         // Step 2: Show confirmation popup
         try {
             const confirmed = await confirmOrderSubmission();
             if (!confirmed) {
                 console.log('Order submission cancelled by user');
                 return false;
             }
             
             // Step 3: Proceed with order submission
             console.log('Step 3: Proceeding with order submission...');
             proceedWithOrderSubmission(form, submitBtn);
         } catch (error) {
             console.error('Error in confirmation process:', error);
             alert('An error occurred. Please try again.');
         }
     }
     
     // Show confirmation popup with order details
     function confirmOrderSubmission() {
         // Get order summary details
         const subtotal = document.getElementById('subtotal').textContent;
         const shippingCost = document.getElementById('shippingCost').textContent;
         const finalTotal = document.getElementById('finalTotal').textContent;
         const shippingMethod = document.querySelector('input[name="shipping_method"]:checked');
         const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
         
         const shippingMethodName = shippingMethod ? shippingMethod.nextElementSibling.querySelector('strong').textContent : 'Not selected';
         const paymentMethodName = paymentMethod ? paymentMethod.nextElementSibling.textContent.trim() : 'Not selected';
         
        // Update modal content
        document.getElementById('modalSubtotal').textContent = `LKR ${subtotal}`;
        document.getElementById('modalProductDiscount').textContent = `LKR ${productDiscount.toFixed(2)}`;
        document.getElementById('modalShipping').textContent = `LKR ${shippingCost}`;
        document.getElementById('modalTotal').textContent = `LKR ${finalTotal}`;
        document.getElementById('modalShippingMethod').textContent = shippingMethodName;
        document.getElementById('modalPaymentMethod').textContent = paymentMethodName;
         
         // Show the modal
         const modal = new bootstrap.Modal(document.getElementById('orderConfirmationModal'));
         modal.show();
         
         // Return a promise that resolves when user confirms
         return new Promise((resolve) => {
             const confirmBtn = document.getElementById('confirmOrderBtn');
             const modalElement = document.getElementById('orderConfirmationModal');
             
             // Handle confirm button click
             confirmBtn.onclick = function() {
                 modal.hide();
                 resolve(true);
             };
             
             // Handle modal close/cancel
             modalElement.addEventListener('hidden.bs.modal', function() {
                 resolve(false);
             }, { once: true });
         });
     }
     
     // Proceed with order submission after confirmation
     function proceedWithOrderSubmission(form, submitBtn) {
         // Show loading state
         const originalText = submitBtn.innerHTML;
         submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing Order...';
         submitBtn.disabled = true;
         
         // Try AJAX submission first
         console.log('Attempting AJAX submission...');
         submitOrderAjax(form, submitBtn, originalText);
     }
    
    // AJAX submission function
    function submitOrderAjax(form, submitBtn, originalText) {
        const formData = new FormData(form);
        
        fetch('checkout.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log('AJAX response received:', response.status);
            
            if (response.ok) {
                // Clone the response to avoid consuming it
                const clonedResponse = response.clone();
                
                // Try to parse as JSON first
                return response.json().then(data => {
                    console.log('JSON response received:', data);
                    
                    if (data.success) {
                        // Success! Redirect to order confirmation
                        console.log('Order created successfully with ID:', data.order_id);
                        window.location.href = data.redirect;
                        return;
                    } else {
                        // Show error message
                        alert('Error: ' + (data.message || 'Failed to place order'));
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }).catch(jsonError => {
                    // If not JSON, try to parse as text using cloned response
                    console.log('Not JSON response, trying text parsing...', jsonError);
                    return clonedResponse.text().then(text => {
                        console.log('Response text:', text);
                        
                        // Look for redirect in response
                        if (text.includes('order_confirmation.php')) {
                            const match = text.match(/order_confirmation\.php\?id=(\d+)/);
                            if (match) {
                                const orderId = match[1];
                                console.log('Order created successfully with ID:', orderId);
                                window.location.href = `order_confirmation.php?id=${orderId}`;
                                return;
                            }
                        }
                        
                        // Fallback: try to redirect anyway
                        console.log('AJAX success but no clear redirect found');
                        alert('Order placed successfully! Redirecting...');
                        setTimeout(() => {
                            window.location.href = 'dashboard.php?orders=1';
                        }, 1000);
                    });
                });
            } else {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
        })
        .catch(error => {
            console.error('AJAX submission failed:', error);
            console.log('Step 3: Falling back to traditional form submission...');
            
            // Fallback: Traditional form submission
            submitOrderTraditional(form, submitBtn, originalText);
        });
    }
    
    // Traditional form submission fallback
    function submitOrderTraditional(form, submitBtn, originalText) {
        console.log('Using traditional form submission...');
        
        // Update button text
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting Order...';
        
        // Add a small delay to show the loading state
        setTimeout(() => {
            // Trigger the hidden submit button
            const hiddenSubmitBtn = document.getElementById('hiddenSubmitBtn');
            if (hiddenSubmitBtn) {
                console.log('Triggering hidden submit button...');
                hiddenSubmitBtn.click();
            } else {
                console.log('Submitting form directly...');
                form.submit();
            }
        }, 500);
        
        // Re-enable button after 10 seconds as ultimate fallback
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.log('Button re-enabled after timeout');
        }, 10000);
    }
    </script>
</body>
</html> 