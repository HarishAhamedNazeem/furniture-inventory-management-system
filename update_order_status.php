<?php

// Turn off output buffering
if (ob_get_length()) {
    ob_end_clean();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('includes/load.php');
require_once('includes/db.php');

header('Content-Type: application/json');

if(isset($_POST['update_status'])) {
    try {
        $order_id = (int)$_POST['order_id'];
        $new_status = $conn->real_escape_string($_POST['status']);
        
        // Validate status
        $valid_statuses = ['pending', 'processing', 'delivered', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception('Invalid status value');
        }
        
        // Check if order exists
        $check_sql = "SELECT id FROM orders WHERE id = $order_id";
        $check_result = $conn->query($check_sql);
        if (!$check_result || $check_result->num_rows === 0) {
            throw new Exception('Order not found');
        }
        
        // Update the status
        $sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

exit();

?> 