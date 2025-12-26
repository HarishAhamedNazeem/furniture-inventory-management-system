<?php
session_start();
require_once('../../includes/config.php');
require_once('../../includes/database.php');

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add to wishlist']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

// Check if already in wishlist
$sql_check = "SELECT id FROM wishlist WHERE customer_id = $customer_id AND product_id = $product_id";
$result = $db->query($sql_check);

if ($db->num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already in wishlist']);
    exit();
}

// Insert into wishlist
$sql = "INSERT INTO wishlist (customer_id, product_id) VALUES ($customer_id, $product_id)";
if ($db->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
}
