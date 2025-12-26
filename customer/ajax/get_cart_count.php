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
    echo json_encode(['success' => false, 'message' => 'Please login first', 'count' => 0]);
    exit();
}

$customer_id = $_SESSION['customer_id'];

try {
    // Get cart count
    $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $customer_id;
    $cart_count_result = $db->query($cart_count_sql);
    $cart_count = $db->fetch_assoc($cart_count_result)['count'];
    
    echo json_encode([
        'success' => true, 
        'count' => (int)$cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'count' => 0
    ]);
}
?>
