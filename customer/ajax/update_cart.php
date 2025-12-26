<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../../includes/config.php');
require_once('../../includes/database.php');

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($cart_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
    exit();
}

// Check if cart item belongs to customer and get product info
$cart_sql = "SELECT c.*, p.quantity as stock_quantity, p.sale_price 
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             WHERE c.id = " . $cart_id . " AND c.customer_id = " . $customer_id;
$cart_result = $db->query($cart_sql);

if ($db->num_rows($cart_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit();
}

$cart_item = $db->fetch_assoc($cart_result);

// Check if quantity is available in stock
if ($quantity > $cart_item['stock_quantity']) {
    echo json_encode(['success' => false, 'message' => 'Requested quantity not available in stock']);
    exit();
}

// Update cart quantity
$update_sql = "UPDATE cart SET quantity = " . $quantity . ", updated_at = NOW() 
               WHERE id = " . $cart_id . " AND customer_id = " . $customer_id;

if ($db->query($update_sql)) {
    $subtotal = $quantity * $cart_item['sale_price'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cart updated successfully',
        'subtotal' => number_format($subtotal, 2)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
?> 