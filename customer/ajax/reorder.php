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
    echo json_encode(['success' => false, 'message' => 'Please login to reorder items']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // Verify the order belongs to the customer
    $order_sql = "SELECT id FROM orders WHERE id = " . $order_id . " AND customer_id = " . $customer_id;
    $order_result = $db->query($order_sql);
    
    if ($db->num_rows($order_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $items_sql = "SELECT oi.product_id, oi.quantity, p.name, p.quantity as available_quantity 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = " . $order_id;
    $items_result = $db->query($items_sql);
    
    if ($db->num_rows($items_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'No items found in this order']);
        exit();
    }
    
    $added_items = [];
    $unavailable_items = [];
    $insufficient_stock_items = [];
    
    // Start transaction
    $db->query("START TRANSACTION");
    
    while ($item = $db->fetch_assoc($items_result)) {
        // Check if product is still available
        if ($item['available_quantity'] <= 0) {
            $unavailable_items[] = $item['name'];
            continue;
        }
        
        // Check if requested quantity is available
        $requested_quantity = $item['quantity'];
        if ($requested_quantity > $item['available_quantity']) {
            $requested_quantity = $item['available_quantity'];
            $insufficient_stock_items[] = [
                'name' => $item['name'],
                'requested' => $item['quantity'],
                'available' => $item['available_quantity']
            ];
        }
        
        // Check if item is already in cart
        $existing_cart_sql = "SELECT * FROM cart WHERE customer_id = " . $customer_id . " AND product_id = " . $item['product_id'];
        $existing_cart_result = $db->query($existing_cart_sql);
        
        if ($db->num_rows($existing_cart_result) > 0) {
            // Update existing cart item
            $existing_item = $db->fetch_assoc($existing_cart_result);
            $new_quantity = $existing_item['quantity'] + $requested_quantity;
            
            // Check if new quantity exceeds stock
            if ($new_quantity > $item['available_quantity']) {
                $new_quantity = $item['available_quantity'];
            }
            
            $update_sql = "UPDATE cart SET quantity = " . $new_quantity . ", updated_at = NOW() 
                          WHERE customer_id = " . $customer_id . " AND product_id = " . $item['product_id'];
            $db->query($update_sql);
        } else {
            // Add new item to cart
            $insert_sql = "INSERT INTO cart (customer_id, product_id, quantity, created_at, updated_at) 
                          VALUES (" . $customer_id . ", " . $item['product_id'] . ", " . $requested_quantity . ", NOW(), NOW())";
            $db->query($insert_sql);
        }
        
        $added_items[] = $item['name'];
    }
    
    // Commit transaction
    $db->query("COMMIT");
    
    // Prepare response message
    $message = '';
    if (!empty($added_items)) {
        $message .= count($added_items) . ' item(s) added to cart successfully';
    }
    
    if (!empty($unavailable_items)) {
        $message .= (!empty($message) ? '. ' : '') . 'Some items are no longer available: ' . implode(', ', $unavailable_items);
    }
    
    if (!empty($insufficient_stock_items)) {
        $stock_message = [];
        foreach ($insufficient_stock_items as $stock_item) {
            $stock_message[] = $stock_item['name'] . ' (only ' . $stock_item['available'] . ' available)';
        }
        $message .= (!empty($message) ? '. ' : '') . 'Limited stock for: ' . implode(', ', $stock_message);
    }
    
    // Get updated cart count
    $cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $customer_id;
    $cart_count_result = $db->query($cart_count_sql);
    $cart_count = $db->fetch_assoc($cart_count_result)['count'];
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count,
        'added_items' => count($added_items),
        'unavailable_items' => count($unavailable_items),
        'insufficient_stock_items' => count($insufficient_stock_items)
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->query("ROLLBACK");
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
