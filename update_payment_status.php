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

if(isset($_POST['update_payment_status'])) {
    try {
        $order_id = (int)$_POST['order_id'];
        $new_payment_status = $conn->real_escape_string($_POST['payment_status']);
        
        // Validate payment status
        $valid_payment_statuses = ['pending', 'paid', 'failed'];
        if (!in_array($new_payment_status, $valid_payment_statuses)) {
            throw new Exception('Invalid payment status value');
        }
        
        // Check if order exists
        $check_sql = "SELECT id FROM orders WHERE id = $order_id";
        $check_result = $conn->query($check_sql);
        if (!$check_result || $check_result->num_rows === 0) {
            throw new Exception('Order not found');
        }
        
        // Update the payment status
        $sql = "UPDATE orders SET payment_status = '$new_payment_status' WHERE id = $order_id";
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
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