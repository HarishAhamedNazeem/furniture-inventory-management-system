<?php
/*
|--------------------------------------------------------------------------
| Delete Customer
|--------------------------------------------------------------------------
| Delete customer functionality for the inventory management system
| Author: Assistant
| Version: 1.0
|
*/

require_once('includes/load.php');
require_once('includes/db.php');

// Check if user is admin
page_require_level(1);

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_type = isset($_GET['type']) ? $_GET['type'] : 'online';

// Debug logging
error_log("Delete customer request: ID=$customer_id, Type=$customer_type");

if ($customer_id <= 0) {
    $_SESSION['error'] = 'Invalid customer ID';
    header('Location: customers.php?type=' . $customer_type);
    exit();
}

// Helper function to check if table exists
function table_exists($conn, $table_name) {
    $check_table = "SHOW TABLES LIKE '$table_name'";
    $result = $conn->query($check_table);
    return $result && $result->num_rows > 0;
}

try {
    // Start transaction
    $conn->query("START TRANSACTION");
    
    // Get customer details first
    $get_customer_sql = "SELECT name, email, registration_type FROM customers WHERE id = $customer_id";
    $customer_result = $conn->query($get_customer_sql);
    
    if (!$customer_result || $customer_result->num_rows == 0) {
        throw new Exception('Customer not found');
    }
    
    $customer_data = $customer_result->fetch_assoc();
    $customer_name = $customer_data['name'];
    $registration_type = $customer_data['registration_type'];
    
    error_log("Deleting customer: ID=$customer_id, Name=$customer_name, Type=$registration_type");
    
    // For online customers, delete related data first
    if ($registration_type === 'online') {
        // Delete notifications
        if (table_exists($conn, 'notifications')) {
            $delete_notifications_sql = "DELETE FROM notifications WHERE customer_id = $customer_id";
            $conn->query($delete_notifications_sql);
        }
        
        // Delete cart items
        if (table_exists($conn, 'cart')) {
            $delete_cart_sql = "DELETE FROM cart WHERE customer_id = $customer_id";
            $conn->query($delete_cart_sql);
        }
        
        // Delete wishlist items
        if (table_exists($conn, 'wishlist')) {
            $delete_wishlist_sql = "DELETE FROM wishlist WHERE customer_id = $customer_id";
            $conn->query($delete_wishlist_sql);
        }
        
        // Delete order status history (delete before orders since it references orders)
        if (table_exists($conn, 'order_status_history')) {
            $delete_status_history_sql = "DELETE osh FROM order_status_history osh 
                                          INNER JOIN orders o ON osh.order_id = o.id 
                                          WHERE o.customer_id = $customer_id";
            $conn->query($delete_status_history_sql);
        }
        
        // Delete invoices (delete before orders since it references orders)
        if (table_exists($conn, 'invoices')) {
            $delete_invoices_sql = "DELETE i FROM invoices i 
                                    INNER JOIN orders o ON i.order_id = o.id 
                                    WHERE o.customer_id = $customer_id";
            $conn->query($delete_invoices_sql);
        }
        
        // Delete order items (delete before orders since it references orders)
        if (table_exists($conn, 'order_items')) {
            $delete_order_items_sql = "DELETE oi FROM order_items oi 
                                       INNER JOIN orders o ON oi.order_id = o.id 
                                       WHERE o.customer_id = $customer_id";
            $conn->query($delete_order_items_sql);
        }
        
        // Delete orders
        if (table_exists($conn, 'orders')) {
            $delete_orders_sql = "DELETE FROM orders WHERE customer_id = $customer_id";
            $conn->query($delete_orders_sql);
        }
    }
    
    // Note: physical_sales has ON DELETE SET NULL constraint, so sales records are preserved
    // Only delete physical sales items for this customer
    if (table_exists($conn, 'physical_sales_items')) {
        $delete_sales_items_sql = "DELETE psi FROM physical_sales_items psi 
                                   INNER JOIN physical_sales ps ON psi.sale_id = ps.id 
                                   WHERE ps.customer_id = $customer_id";
        $conn->query($delete_sales_items_sql);
    }
    
    // Delete physical sales records (for both online and walk-in customers)
    if (table_exists($conn, 'physical_sales')) {
        $delete_sales_sql = "DELETE FROM physical_sales WHERE customer_id = $customer_id";
        $conn->query($delete_sales_sql);
    }
    
    // Finally, delete the customer record
    $delete_customer_sql = "DELETE FROM customers WHERE id = $customer_id";
    
    if ($conn->query($delete_customer_sql)) {
        $customer_type_text = $registration_type === 'online' ? 'online' : 'walk-in';
        $_SESSION['success'] = ucfirst($customer_type_text) . ' customer "' . htmlspecialchars($customer_name) . '" and all associated data have been deleted successfully';
    } else {
        throw new Exception('Failed to delete customer: ' . $conn->error);
    }
    
    // Commit transaction
    $conn->query("COMMIT");
    
    // Log the action
    error_log("Customer deletion performed by admin user {$_SESSION['user_id']} for customer ID $customer_id (type: $customer_type)");
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->query("ROLLBACK");
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    error_log("Customer deletion error: " . $e->getMessage());
}

// Redirect back to customers page
$redirect_url = 'customers.php?type=' . $customer_type;
header('Location: ' . $redirect_url);
exit();
?>
