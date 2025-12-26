<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('../customer/includes/promotions.php');

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    $cashier_id = $_SESSION['user_id'];
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $cart_data = $_POST['cart'] ?? '[]';
    
    // Log the received data for debugging
    error_log("Checkout data: " . print_r($_POST, true));
    
    // Validate required fields
    if (empty($payment_method)) {
        throw new Exception('Payment method is required');
    }
    
    $cart = json_decode($cart_data, true);
    if (empty($cart)) {
        throw new Exception('Cart is empty');
    }
    
    // Generate sale number
    $sale_number = 'PS' . date('Ymd') . sprintf('%04d', rand(1, 9999));
    
    // Calculate totals
    $subtotal = 0;
    $total_discount = 0;
    
    foreach ($cart as $item) {
        $item_total = $item['product_price'] * $item['quantity'];
        $subtotal += $item_total;
    }
    
    $total_amount = $subtotal - $total_discount;
    
    // Handle payment details
    $cash_amount = null;
    $card_amount = null;
    $bank_transfer_amount = null;
    $change_given = 0;
    
    switch ($payment_method) {
        case 'cash':
            $cash_amount = floatval($_POST['cash_amount'] ?? 0);
            if ($cash_amount < $total_amount) {
                throw new Exception('Cash amount is less than total amount');
            }
            $change_given = $cash_amount - $total_amount;
            break;
        case 'card':
            $card_amount = floatval($_POST['card_amount'] ?? 0);
            if ($card_amount != $total_amount) {
                throw new Exception('Card amount must equal total amount');
            }
            break;
        case 'bank_transfer':
            $bank_transfer_amount = floatval($_POST['bank_transfer_amount'] ?? 0);
            if ($bank_transfer_amount != $total_amount) {
                throw new Exception('Bank transfer amount must equal total amount');
            }
            break;
        case 'mixed':
            $cash_amount = floatval($_POST['cash_amount'] ?? 0);
            $card_amount = floatval($_POST['card_amount'] ?? 0);
            $bank_transfer_amount = floatval($_POST['bank_transfer_amount'] ?? 0);
            $total_payment = $cash_amount + $card_amount + $bank_transfer_amount;
            if ($total_payment != $total_amount) {
                throw new Exception('Total payment amount must equal total amount');
            }
            break;
    }
    
    // Start transaction
    $db->query("START TRANSACTION");
    
    try {
        // Prepare variables for bind_param
        $status = 'completed';
        
        // Handle customer creation/linking for walk-in sales
        $customer_id = null;
        
        // If customer info is provided, create or link customer record
        if (!empty($customer_name) && (!empty($customer_email) || !empty($customer_phone))) {
            // Check if customer already exists by email or phone
            $existing_customer_sql = "SELECT id FROM customers WHERE email = ? OR phone = ?";
            $stmt = $db->con->prepare($existing_customer_sql);
            $stmt->bind_param('ss', $customer_email, $customer_phone);
            $stmt->execute();
            $existing_customer = $stmt->get_result()->fetch_assoc();
            
            if ($existing_customer) {
                // Customer exists, use existing ID to link the sale
                $customer_id = $existing_customer['id'];
            } else {
                // Create new walk-in customer record
                $create_customer_sql = "INSERT INTO customers (name, email, phone, address, password, registration_type, status, created_at) 
                                       VALUES (?, ?, ?, '', '', 'walkin', 1, NOW())";
                $stmt = $db->con->prepare($create_customer_sql);
                $stmt->bind_param('sss', $customer_name, $customer_email, $customer_phone);
                
                if ($stmt->execute()) {
                    $customer_id = $db->insert_id();
                    error_log("Created new walk-in customer with ID: " . $customer_id);
                } else {
                    error_log("Failed to create walk-in customer: " . $stmt->error);
                }
            }
        }
        
        // Insert sale record with customer_id
        $sale_sql = "INSERT INTO physical_sales (sale_number, cashier_id, customer_id, customer_name, customer_phone, customer_email, 
                     subtotal, discount_amount, total_amount, payment_method, cash_amount, card_amount, 
                     bank_transfer_amount, change_given, status, notes, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->con->prepare($sale_sql);
        $stmt->bind_param('siisssdddsddddss', 
            $sale_number, 
            $cashier_id, 
            $customer_id,
            $customer_name, 
            $customer_phone, 
            $customer_email, 
            $subtotal, 
            $total_discount, 
            $total_amount, 
            $payment_method, 
            $cash_amount, 
            $card_amount, 
            $bank_transfer_amount, 
            $change_given, 
            $status,
            $notes
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create sale record');
        }
        
        $sale_id = $db->insert_id();
        
        // Insert sale items and update inventory
        foreach ($cart as $item) {
            // Get product details and promotions
            $product_sql = "SELECT p.*, c.name as category_name FROM products p 
                           LEFT JOIN categories c ON p.categorie_id = c.id 
                           WHERE p.id = ?";
            $stmt = $db->con->prepare($product_sql);
            $stmt->bind_param('i', $item['product_id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            
            if (!$product) {
                throw new Exception('Product not found: ' . $item['product_name']);
            }
            
            // Check stock again
            if ($product['quantity'] < $item['quantity']) {
                throw new Exception('Not enough stock for: ' . $item['product_name']);
            }
            
            // Get promotion details
            $promotions = getProductPromotions($item['product_id'], $product['categorie_id']);
            $promotion_info = getPromotionInfo($product['sale_price'], $promotions);
            $final_price = $promotion_info ? $promotion_info['discounted_price'] : $product['sale_price'];
            $discount_amount = $promotion_info ? $promotion_info['discount_amount'] : 0;
            $promotion_id = $promotion_info ? $promotion_info['promotion']['id'] : null;
            
            // Insert sale item
            $item_sql = "INSERT INTO physical_sales_items (sale_id, product_id, quantity, unit_price, discount_amount, 
                         total_price, promotion_id, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->con->prepare($item_sql);
            $total_price = $final_price * $item['quantity'];
            $stmt->bind_param('iiidddi', 
                $sale_id, 
                $item['product_id'], 
                $item['quantity'], 
                $final_price, 
                $discount_amount, 
                $total_price, 
                $promotion_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert sale item');
            }
            
            // Update product quantity
            $update_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
            $stmt = $db->con->prepare($update_sql);
            $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update product quantity');
            }
        }
        
        // Clear cart
        $session_id = session_id();
        $clear_sql = "DELETE FROM physical_sales_cart WHERE session_id = ?";
        $stmt = $db->con->prepare($clear_sql);
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        
        // Commit transaction
        $db->query("COMMIT");
        
        // Generate invoice URL
        $invoice_url = "generate_physical_sales_invoice.php?id=" . $sale_id;
        
        echo json_encode([
            'success' => true,
            'sale_number' => $sale_number,
            'sale_id' => $sale_id,
            'total_amount' => $total_amount,
            'invoice_url' => $invoice_url
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->query("ROLLBACK");
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Error $e) {
    error_log("Checkout fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
}
?>
