<?php
// Start output buffering to catch any unwanted output
ob_start();

// Turn off error display for AJAX requests to prevent JSON corruption
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set JSON header first
header('Content-Type: application/json');

require_once('includes/load.php');
require_once('includes/db.php');

// Clear any output that might have been generated
ob_clean();

// Log the request for debugging
error_log("update_statuses.php called with POST data: " . json_encode($_POST));

// Check if user is logged in (admin)
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    ob_end_flush();
    exit();
}

// Handle status update request
if(isset($_POST['update_statuses'])) {
    try {
        $order_id = (int)$_POST['order_id'];
        $new_status = $conn->real_escape_string($_POST['status']);
        $new_payment_status = $conn->real_escape_string($_POST['payment_status']);
        $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        $estimated_delivery = isset($_POST['estimated_delivery']) ? $conn->real_escape_string($_POST['estimated_delivery']) : '';
        $customer_message = isset($_POST['customer_message']) ? $conn->real_escape_string($_POST['customer_message']) : '';
        $tracking_number = isset($_POST['tracking_number']) ? $conn->real_escape_string($_POST['tracking_number']) : '';
        $carrier = isset($_POST['carrier']) ? $conn->real_escape_string($_POST['carrier']) : '';
        $send_email = isset($_POST['send_email']) ? (bool)$_POST['send_email'] : true;
        $user_id = $_SESSION['user_id'];
        
        // Validate status values
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $valid_payment_statuses = ['pending', 'paid', 'failed'];
        
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception('Invalid order status value');
        }
        
        if (!in_array($new_payment_status, $valid_payment_statuses)) {
            throw new Exception('Invalid payment status value');
        }
        
        // Check if order exists and get current details
        $check_sql = "SELECT o.*, c.name as customer_name, c.email as customer_email 
                      FROM orders o 
                      JOIN customers c ON o.customer_id = c.id 
                      WHERE o.id = $order_id";
        $check_result = $conn->query($check_sql);
        
        if (!$check_result || $check_result->num_rows === 0) {
            throw new Exception('Order not found');
        }
        
        $order = $check_result->fetch_assoc();
        $old_status = $order['status'];
        $old_payment_status = $order['payment_status'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update the order status
            $update_sql = "UPDATE orders SET 
                          status = '$new_status', 
                          payment_status = '$new_payment_status',
                          updated_at = NOW()";
            
            // Note: estimated_delivery, tracking_number, and carrier are handled in order_tracking table
            
            if (!empty($notes)) {
                $update_sql .= ", notes = CONCAT(COALESCE(notes, ''), '\n[" . date('Y-m-d H:i:s') . "] $notes')";
            }
            
            $update_sql .= " WHERE id = $order_id";
            
            if(!$conn->query($update_sql)) {
                throw new Exception($conn->error);
            }
            
            // Update or insert order tracking information
            if (!empty($tracking_number) || !empty($carrier) || !empty($estimated_delivery)) {
                // Check if tracking record exists
                $tracking_check = "SELECT id FROM order_tracking WHERE order_id = $order_id";
                $tracking_result = $conn->query($tracking_check);
                
                if ($tracking_result && $tracking_result->num_rows > 0) {
                    // Update existing tracking record
                    $tracking_update = "UPDATE order_tracking SET ";
                    $tracking_fields = array();
                    
                    if (!empty($tracking_number)) {
                        $tracking_fields[] = "tracking_number = '$tracking_number'";
                    }
                    if (!empty($carrier)) {
                        $tracking_fields[] = "carrier = '$carrier'";
                    }
                    if (!empty($estimated_delivery)) {
                        $tracking_fields[] = "estimated_delivery = '$estimated_delivery'";
                    }
                    
                    if (!empty($tracking_fields)) {
                        $tracking_update .= implode(", ", $tracking_fields) . ", updated_at = NOW() WHERE order_id = $order_id";
                        $conn->query($tracking_update);
                    }
                } else {
                    // Insert new tracking record
                    $tracking_insert = "INSERT INTO order_tracking (order_id, tracking_number, carrier, estimated_delivery, created_at, updated_at) 
                                      VALUES ($order_id, ";
                    $tracking_insert .= !empty($tracking_number) ? "'$tracking_number'" : "NULL";
                    $tracking_insert .= ", ";
                    $tracking_insert .= !empty($carrier) ? "'$carrier'" : "NULL";
                    $tracking_insert .= ", ";
                    $tracking_insert .= !empty($estimated_delivery) ? "'$estimated_delivery'" : "NULL";
                    $tracking_insert .= ", NOW(), NOW())";
                    $conn->query($tracking_insert);
                }
            }
            
            // Create status history entry (without changed_by column as it doesn't exist in the table)
            $history_sql = "INSERT INTO order_status_history (order_id, status, payment_status, notes, created_at) 
                           VALUES ($order_id, '$new_status', '$new_payment_status', '$notes', NOW())";
            $conn->query($history_sql);
            
            // Create customer notifications if status changed
            if ($old_status !== $new_status) {
                // Check if this is an order rejection
                $is_rejection = ($new_status === 'cancelled' && strpos($notes, 'Order rejected:') !== false);
                
                if ($is_rejection) {
                    // Special handling for order rejection
                    $rejection_reason = '';
                    if (!empty($notes)) {
                        $rejection_reason = str_replace('Order rejected: ', '', $notes);
                    }
                    
                    $rejection_message = "Your order {$order['order_number']} has been rejected by our team. " . 
                                       (!empty($rejection_reason) ? "Reason: " . $rejection_reason : "Please contact us for more information.");
                    
                    $notification_sql = "INSERT INTO notifications (customer_id, order_id, type, title, message, created_at) 
                                        VALUES ({$order['customer_id']}, $order_id, 'order_rejection', 
                                        'Order Rejected', '$rejection_message', NOW())";
                    $conn->query($notification_sql);
                    
                    // Send specialized rejection email
                    try {
                        if (file_exists('includes/email_functions.php')) {
                            require_once('includes/email_functions.php');
                            
                            // Get customer details
                            $customer_sql = "SELECT * FROM customers WHERE id = {$order['customer_id']}";
                            $customer_result = $conn->query($customer_sql);
                            if ($customer_result && $customer_result->num_rows > 0) {
                                $customer = $customer_result->fetch_assoc();
                                
                                // Check if the email function exists before calling it
                                if (function_exists('sendOrderRejectionEmail')) {
                                    $email_sent = sendOrderRejectionEmail($order, $customer, $rejection_reason);
                                    
                                    if ($email_sent) {
                                        error_log("Order rejection email sent successfully for order #{$order['order_number']}");
                                    } else {
                                        error_log("Failed to send order rejection email for order #{$order['order_number']}");
                                    }
                                } else {
                                    error_log("sendOrderRejectionEmail function not found");
                                }
                            }
                        } else {
                            error_log("Email functions file not found");
                        }
                    } catch (Exception $email_error) {
                        error_log("Email sending error for order rejection: " . $email_error->getMessage());
                    }
                } elseif ($new_status === 'processing' && strpos($notes, 'Order accepted:') !== false) {
                    // Special handling for order acceptance
                    $acceptance_message = "Great news! Your order {$order['order_number']} has been accepted and is now being processed.";
                    if (!empty($estimated_delivery)) {
                        $acceptance_message .= " Estimated delivery date: " . date('M d, Y', strtotime($estimated_delivery)) . ".";
                    }
                    
                    $notification_sql = "INSERT INTO notifications (customer_id, order_id, type, title, message, created_at) 
                                        VALUES ({$order['customer_id']}, $order_id, 'order_status', 
                                        'Order Accepted', '$acceptance_message', NOW())";
                    $conn->query($notification_sql);
                    
                    // Send order acceptance email if enabled
                    if ($send_email) {
                        try {
                            if (file_exists('includes/email_functions.php')) {
                                require_once('includes/email_functions.php');
                                
                                // Get customer details
                                $customer_sql = "SELECT * FROM customers WHERE id = {$order['customer_id']}";
                                $customer_result = $conn->query($customer_sql);
                                if ($customer_result && $customer_result->num_rows > 0) {
                                    $customer = $customer_result->fetch_assoc();
                                    
                                    // Check if the email function exists before calling it
                                    if (function_exists('sendOrderAcceptanceEmail')) {
                                        $email_sent = sendOrderAcceptanceEmail($order, $customer, $customer_message, $estimated_delivery);
                                        
                                        if ($email_sent) {
                                            error_log("Order acceptance email sent successfully for order #{$order['order_number']}");
                                        } else {
                                            error_log("Failed to send order acceptance email for order #{$order['order_number']}");
                                        }
                                    } else {
                                        error_log("sendOrderAcceptanceEmail function not found");
                                    }
                                }
                            } else {
                                error_log("Email functions file not found");
                            }
                        } catch (Exception $email_error) {
                            error_log("Email sending error for order acceptance: " . $email_error->getMessage());
                        }
                    }
                } else {
                    // Regular status update handling
                    $status_message = getStatusMessage($new_status, $order['order_number'] ?? '#' . $order_id);
                    $notification_sql = "INSERT INTO notifications (customer_id, order_id, type, title, message, created_at) 
                                        VALUES ({$order['customer_id']}, $order_id, 'order_status', 
                                        'Order Status Updated', '$status_message', NOW())";
                    $conn->query($notification_sql);
                    
                    // Send email notification for status change
                    try {
                        if (file_exists('includes/email_functions.php')) {
                            require_once('includes/email_functions.php');
                            
                            // Get customer details
                            $customer_sql = "SELECT * FROM customers WHERE id = {$order['customer_id']}";
                            $customer_result = $conn->query($customer_sql);
                            if ($customer_result && $customer_result->num_rows > 0) {
                                $customer = $customer_result->fetch_assoc();
                                
                                // Check if the email function exists before calling it
                                if (function_exists('sendOrderStatusUpdateEmail')) {
                                    $email_sent = sendOrderStatusUpdateEmail($order, $customer, $new_status);
                                    
                                    if ($email_sent) {
                                        error_log("Order status update email sent successfully for order #{$order['order_number']}");
                                    } else {
                                        error_log("Failed to send order status update email for order #{$order['order_number']}");
                                    }
                                } else {
                                    error_log("sendOrderStatusUpdateEmail function not found");
                                }
                            }
                        } else {
                            error_log("Email functions file not found");
                        }
                    } catch (Exception $email_error) {
                        error_log("Email sending error for status update: " . $email_error->getMessage());
                    }
                }
            }
            
            if ($old_payment_status !== $new_payment_status) {
                $payment_message = getPaymentStatusMessage($new_payment_status, $order['order_number'] ?? '#' . $order_id);
                $notification_sql = "INSERT INTO notifications (customer_id, order_id, type, title, message, created_at) 
                                    VALUES ({$order['customer_id']}, $order_id, 'payment_status', 
                                    'Payment Status Updated', '$payment_message', NOW())";
                $conn->query($notification_sql);
                
                // Send email notification for payment status change
                if ($new_payment_status === 'paid') {
                    try {
                        if (file_exists('includes/email_functions.php')) {
                            require_once('includes/email_functions.php');
                            
                            // Get customer details
                            $customer_sql = "SELECT * FROM customers WHERE id = {$order['customer_id']}";
                            $customer_result = $conn->query($customer_sql);
                            if ($customer_result && $customer_result->num_rows > 0) {
                                $customer = $customer_result->fetch_assoc();
                                
                                // Check if the email function exists before calling it
                                if (function_exists('sendPaymentConfirmationEmail')) {
                                    $email_sent = sendPaymentConfirmationEmail($order, $customer);
                                    
                                    if ($email_sent) {
                                        error_log("Payment confirmation email sent successfully for order #{$order['order_number']}");
                                    } else {
                                        error_log("Failed to send payment confirmation email for order #{$order['order_number']}");
                                    }
                                } else {
                                    error_log("sendPaymentConfirmationEmail function not found");
                                }
                            }
                        } else {
                            error_log("Email functions file not found");
                        }
                    } catch (Exception $email_error) {
                        error_log("Email sending error for payment confirmation: " . $email_error->getMessage());
                    }
                }
            }
            
            // Create invoice if payment status changed to paid
            // Note: Invoice functionality is disabled as the invoices table doesn't exist yet
            // if ($old_payment_status !== 'paid' && $new_payment_status === 'paid') {
            //     createInvoice($order_id, $order, $conn);
            // }
            
            // Commit transaction
            $conn->commit();
            
            // Clear any output before sending JSON
            ob_clean();
            
            // Log successful update
            error_log("Order status updated successfully for order ID: $order_id");
            
            $response = [
                'success' => true, 
                'message' => 'Order status updated successfully',
                'order_id' => $order_id,
                'new_status' => $new_status,
                'new_payment_status' => $new_payment_status
            ];
            
            // Ensure clean JSON output
            ob_clean();
            echo json_encode($response);
            ob_end_flush();
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        // Clear any output before sending JSON
        ob_clean();
        
        // Log the error
        error_log("Error in update_statuses.php: " . $e->getMessage());
        
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        ob_end_flush();
        exit();
    }
    
    // Clear any output before sending JSON
    ob_clean();
    ob_end_flush();
    exit();
}

// If not an AJAX request, redirect
header('Location: orders.php');
exit();

// Helper functions
function getStatusMessage($status, $order_number) {
    $messages = [
        'pending' => "Your order $order_number is pending review.",
        'processing' => "Great news! Your order $order_number has been accepted and is now being processed.",
        'shipped' => "Your order $order_number has been shipped and is on its way to you!",
        'delivered' => "Your order $order_number has been delivered successfully. Thank you for your purchase!",
        'cancelled' => "Unfortunately, your order $order_number has been cancelled. Please contact us for more information."
    ];
    
    return $messages[$status] ?? "Your order $order_number status has been updated to: " . ucfirst($status);
}

function getPaymentStatusMessage($payment_status, $order_number) {
    $messages = [
        'pending' => "Payment for your order $order_number is pending.",
        'paid' => "Payment for your order $order_number has been confirmed. Thank you!",
        'failed' => "Payment for your order $order_number has failed. Please contact us for assistance."
    ];
    
    return $messages[$payment_status] ?? "Payment status for your order $order_number has been updated to: " . ucfirst($payment_status);
}

function createInvoice($order_id, $order, $conn) {
    // Generate invoice number
    $invoice_number = 'INV-' . date('Y') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
    
    // Check if invoice already exists
    $check_invoice_sql = "SELECT id FROM invoices WHERE order_id = $order_id";
    $check_result = $conn->query($check_invoice_sql);
    
    if ($check_result->num_rows === 0) {
        // Create new invoice
        $invoice_sql = "INSERT INTO invoices (order_id, invoice_number, subtotal, discount_amount, 
                       total_amount, invoice_date, status, created_at, updated_at) 
                       VALUES ($order_id, '$invoice_number', {$order['total_amount']}, 
                       {$order['discount_amount']}, {$order['total_amount']}, NOW(), 'sent', NOW(), NOW())";
        $conn->query($invoice_sql);
        
        // Create customer notification about invoice
        $invoice_message = "Your invoice $invoice_number for order {$order['order_number']} is now available for download.";
        $notification_sql = "INSERT INTO notifications (customer_id, order_id, type, title, message, created_at) 
                            VALUES ({$order['customer_id']}, $order_id, 'general', 
                            'Invoice Available', '$invoice_message', NOW())";
        $conn->query($notification_sql);
        
        // Send invoice ready email
        try {
            require_once('includes/email_functions.php');
            
            // Get customer details
            $customer_sql = "SELECT * FROM customers WHERE id = {$order['customer_id']}";
            $customer_result = $conn->query($customer_sql);
            if ($customer_result && $customer_result->num_rows > 0) {
                $customer = $customer_result->fetch_assoc();
                
                // Send invoice ready email
                $email_sent = sendInvoiceReadyEmail($order, $customer, $invoice_number);
                
                if ($email_sent) {
                    error_log("Invoice ready email sent successfully for order #{$order['order_number']}");
                } else {
                    error_log("Failed to send invoice ready email for order #{$order['order_number']}");
                }
            }
        } catch (Exception $email_error) {
            error_log("Email sending error for invoice ready: " . $email_error->getMessage());
        }
    }
}
?> 