<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../../includes/config.php');
require_once('../../includes/database.php');

header('Content-Type: application/json; charset=utf-8');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit();
}

// Check if product exists and is in stock
$product_sql = "SELECT * FROM products WHERE id = " . $product_id . " AND quantity > 0";
$product_result = $db->query($product_sql);

if ($db->num_rows($product_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found or out of stock']);
    exit();
}

$product = $db->fetch_assoc($product_result);

// Check if quantity is available
if ($quantity > $product['quantity']) {
    echo json_encode(['success' => false, 'message' => 'Requested quantity not available in stock']);
    exit();
}

// Check if item already exists in cart
$existing_cart_sql = "SELECT * FROM cart WHERE customer_id = " . $customer_id . " AND product_id = " . $product_id;
$existing_cart_result = $db->query($existing_cart_sql);

if ($db->num_rows($existing_cart_result) > 0) {
    // Update existing cart item
    $existing_item = $db->fetch_assoc($existing_cart_result);
    $new_quantity = $existing_item['quantity'] + $quantity;
    
    // Check if new quantity exceeds stock
    if ($new_quantity > $product['quantity']) {
        echo json_encode(['success' => false, 'message' => 'Total quantity would exceed available stock']);
        exit();
    }
    
    $update_sql = "UPDATE cart SET quantity = " . $new_quantity . ", updated_at = NOW() 
                   WHERE customer_id = " . $customer_id . " AND product_id = " . $product_id;
    
    if ($db->query($update_sql)) {
        // Get updated cart count
        $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $customer_id;
        $cart_count_result = $db->query($cart_count_sql);
        $cart_count = $db->fetch_assoc($cart_count_result)['count'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cart updated successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
} else {
    // Add new item to cart
    $insert_sql = "INSERT INTO cart (customer_id, product_id, quantity, created_at, updated_at) 
                   VALUES (" . $customer_id . ", " . $product_id . ", " . $quantity . ", NOW(), NOW())";
    
    if ($db->query($insert_sql)) {
        // Get updated cart count
        $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $customer_id;
        $cart_count_result = $db->query($cart_count_sql);
        $cart_count = $db->fetch_assoc($cart_count_result)['count'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    }   
}
?> 