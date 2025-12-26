<?php
require_once('../includes/load.php');

// Check if user has permission
page_require_level(1);

// Set content type to JSON
header('Content-Type: application/json');

// Check if product_id is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = (int)$_GET['product_id'];

// Get product details
$product = find_by_id('products', $product_id);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Return product price information
echo json_encode([
    'success' => true,
    'sale_price' => $product['sale_price'],
    'buy_price' => $product['buy_price'],
    'product_name' => $product['name']
]);
?>
