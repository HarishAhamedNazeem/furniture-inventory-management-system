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
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$customer_id = $_SESSION['customer_id'];

try {
    // Check for recent order status updates (within last 5 minutes)
    $recent_updates_sql = "SELECT o.id as order_id, o.order_number, o.status, o.payment_status, o.updated_at
                          FROM orders o 
                          WHERE o.customer_id = " . $customer_id . " 
                          AND o.updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                          ORDER BY o.updated_at DESC";
    $updates_result = $db->query($recent_updates_sql);
    
    $updates = [];
    while ($update = $db->fetch_assoc($updates_result)) {
        $updates[] = [
            'order_id' => $update['order_id'],
            'order_number' => $update['order_number'] ?? '#' . $update['order_id'],
            'status' => $update['status'],
            'payment_status' => $update['payment_status'],
            'updated_at' => $update['updated_at']
        ];
    }
    
    // Get unread notifications count
    $notifications_sql = "SELECT COUNT(*) as unread_count FROM notifications 
                         WHERE customer_id = " . $customer_id . " AND is_read = 0";
    $notifications_result = $db->query($notifications_sql);
    $unread_notifications = $db->fetch_assoc($notifications_result)['unread_count'];
    
    // Get recent notifications (within last hour)
    $recent_notifications_sql = "SELECT id, title, message, type, created_at 
                                FROM notifications 
                                WHERE customer_id = " . $customer_id . " 
                                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                                AND is_read = 0
                                ORDER BY created_at DESC 
                                LIMIT 5";
    $recent_notifications_result = $db->query($recent_notifications_sql);
    
    $recent_notifications = [];
    while ($notification = $db->fetch_assoc($recent_notifications_result)) {
        $recent_notifications[] = [
            'id' => $notification['id'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'created_at' => $notification['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'updates' => $updates,
        'unread_notifications' => $unread_notifications,
        'recent_notifications' => $recent_notifications,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error checking for updates: ' . $e->getMessage()
    ]);
}
?>
