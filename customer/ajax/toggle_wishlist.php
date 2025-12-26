<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../../includes/config.php');
require_once('../../includes/database.php');

// Set JSON header
header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to use wishlist']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$product_id = (int)$_POST['product_id'];

try {
    // Check if product exists
    $product_sql = "SELECT id FROM products WHERE id = " . $product_id;
    $product_result = $db->query($product_sql);
    
    if ($db->num_rows($product_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    // Check if item is already in wishlist
    $wishlist_sql = "SELECT id FROM wishlist WHERE customer_id = " . $customer_id . " AND product_id = " . $product_id;
    $wishlist_result = $db->query($wishlist_sql);
    
    if ($db->num_rows($wishlist_result) > 0) {
        // Remove from wishlist
        $delete_sql = "DELETE FROM wishlist WHERE customer_id = " . $customer_id . " AND product_id = " . $product_id;
        $db->query($delete_sql);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Removed from wishlist',
            'action' => 'removed'
        ]);
    } else {
        // Add to wishlist
        $insert_sql = "INSERT INTO wishlist (customer_id, product_id, created_at) VALUES (" . $customer_id . ", " . $product_id . ", NOW())";
        $db->query($insert_sql);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Added to wishlist',
            'action' => 'added'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 