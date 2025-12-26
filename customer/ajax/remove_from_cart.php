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
    echo json_encode(['success' => false, 'message' => 'Please login to remove items from cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

if ($cart_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit();
}

// Check if cart item belongs to customer
$check_sql = "SELECT id FROM cart WHERE id = " . $cart_id . " AND customer_id = " . $customer_id;
$check_result = $db->query($check_sql);

if ($db->num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit();
}

// Remove cart item
$delete_sql = "DELETE FROM cart WHERE id = " . $cart_id . " AND customer_id = " . $customer_id;

if ($db->query($delete_sql)) {
    // Get updated cart count
    $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $customer_id;
    $cart_count_result = $db->query($cart_count_sql);
    $cart_count = $db->fetch_assoc($cart_count_result)['count'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Item removed from cart successfully',
        'cart_count' => $cart_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
}
?> 