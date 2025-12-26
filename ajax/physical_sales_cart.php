<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/database.php');

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch($action) {
        case 'save_cart':
            saveCart();
            break;
        case 'load_cart':
            loadCart();
            break;
        case 'add_to_cart':
            addToCart();
            break;
        case 'update_cart':
            updateCart();
            break;
        case 'remove_from_cart':
            removeFromCart();
            break;
        case 'clear_cart':
            clearCart();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action', 'cart' => []]);
            break;
    }
} catch (Exception $e) {
    error_log("Physical sales cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred', 'cart' => []]);
} catch (Error $e) {
    error_log("Physical sales cart fatal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A fatal error occurred', 'cart' => []]);
}

function saveCart() {
    global $db;
    
    $session_id = $_POST['session_id'] ?? '';
    $cart_data = $_POST['cart'] ?? '[]';
    
    // Add debugging
    error_log("saveCart called with session_id: " . $session_id);
    error_log("saveCart cart_data: " . $cart_data);
    
    if (empty($session_id)) {
        throw new Exception('Session ID is required');
    }
    
    // Clear existing cart for this session
    $clear_sql = "DELETE FROM physical_sales_cart WHERE session_id = ?";
    $stmt = $db->con->prepare($clear_sql);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    
    $cart = json_decode($cart_data, true);
    error_log("saveCart decoded cart: " . print_r($cart, true));
    
    if (!empty($cart)) {
        foreach ($cart as $item) {
            $insert_sql = "INSERT INTO physical_sales_cart (session_id, product_id, quantity, unit_price, discount_amount, total_price, promotion_id, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $db->con->prepare($insert_sql);
            
            $total_price = $item['product_price'] * $item['quantity'];
            $promotion_id = !empty($item['promotion_id']) ? $item['promotion_id'] : null;
            
            // Calculate discount amount if original price exists
            $discount_amount = 0.00;
            if (isset($item['original_price']) && $item['original_price'] != $item['product_price']) {
                $original_total = $item['original_price'] * $item['quantity'];
                $discount_amount = $original_total - $total_price;
            }
            
            error_log("Inserting cart item: " . print_r($item, true));
            
            $stmt->bind_param('siidddi', 
                $session_id, 
                $item['product_id'], 
                $item['quantity'], 
                $item['product_price'], 
                $discount_amount,
                $total_price, 
                $promotion_id
            );
            
            if ($stmt->execute()) {
                error_log("Cart item inserted successfully");
            } else {
                error_log("Cart item insert failed: " . $stmt->error);
            }
        }
    }
    
    echo json_encode(['success' => true]);
}

function loadCart() {
    global $db;
    
    $session_id = $_GET['session_id'] ?? '';
    
    if (empty($session_id)) {
        echo json_encode(['success' => false, 'message' => 'Session ID is required', 'cart' => []]);
        return;
    }
    
    try {
        $sql = "SELECT c.*, p.name as product_name, p.image as product_image 
                FROM physical_sales_cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.session_id = ? 
                ORDER BY c.created_at ASC";
        
        $stmt = $db->con->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $db->con->error);
        }
        
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart = [];
        while ($row = $result->fetch_assoc()) {
            $cart[] = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'product_price' => floatval($row['unit_price']),
                'quantity' => intval($row['quantity']),
                'promotion_id' => $row['promotion_id'],
                'product_stock' => 999 // Default stock value
            ];
        }
        
        echo json_encode(['success' => true, 'cart' => $cart]);
        
    } catch (Exception $e) {
        error_log("loadCart error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading cart', 'cart' => []]);
    }
}

function addToCart() {
    global $db;
    
    $session_id = $_POST['session_id'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (empty($session_id) || $product_id <= 0) {
        throw new Exception('Invalid parameters');
    }
    
    // Get product details
    $product_sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->con->prepare($product_sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Check stock
    if ($product['quantity'] < $quantity) {
        throw new Exception('Not enough stock available');
    }
    
    // Use regular price for now (promotions disabled)
    $final_price = $product['sale_price'];
    $promotion_id = null;
    
    // Check if item already exists in cart
    $check_sql = "SELECT * FROM physical_sales_cart WHERE session_id = ? AND product_id = ?";
    $stmt = $db->con->prepare($check_sql);
    $stmt->bind_param('si', $session_id, $product_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        if ($new_quantity > $product['quantity']) {
            throw new Exception('Not enough stock available');
        }
        
        $update_sql = "UPDATE physical_sales_cart SET quantity = ?, total_price = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->con->prepare($update_sql);
        $total_price = $final_price * $new_quantity;
        $stmt->bind_param('idi', $new_quantity, $total_price, $existing['id']);
        $stmt->execute();
    } else {
        // Insert new item
        $insert_sql = "INSERT INTO physical_sales_cart (session_id, product_id, quantity, unit_price, discount_amount, total_price, promotion_id, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $db->con->prepare($insert_sql);
        
        $discount_amount = 0;
        $total_price = $final_price * $quantity;
        
        $stmt->bind_param('siidddi', 
            $session_id, 
            $product_id, 
            $quantity, 
            $final_price, 
            $discount_amount, 
            $total_price, 
            $promotion_id
        );
        $stmt->execute();
    }
    
    echo json_encode(['success' => true]);
}

function updateCart() {
    global $db;
    
    $session_id = $_POST['session_id'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if (empty($session_id) || $product_id <= 0) {
        throw new Exception('Invalid parameters');
    }
    
    if ($quantity <= 0) {
        // Remove item
        removeFromCart();
        return;
    }
    
    // Check stock
    $product_sql = "SELECT quantity FROM products WHERE id = ?";
    $stmt = $db->con->prepare($product_sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product || $product['quantity'] < $quantity) {
        throw new Exception('Not enough stock available');
    }
    
    // Update cart item
    $update_sql = "UPDATE physical_sales_cart SET quantity = ?, total_price = unit_price * ?, updated_at = NOW() WHERE session_id = ? AND product_id = ?";
    $stmt = $db->con->prepare($update_sql);
    $stmt->bind_param('iisi', $quantity, $quantity, $session_id, $product_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Cart item not found');
    }
    
    echo json_encode(['success' => true]);
}

function removeFromCart() {
    global $db;
    
    $session_id = $_POST['session_id'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if (empty($session_id) || $product_id <= 0) {
        throw new Exception('Invalid parameters');
    }
    
    $delete_sql = "DELETE FROM physical_sales_cart WHERE session_id = ? AND product_id = ?";
    $stmt = $db->con->prepare($delete_sql);
    $stmt->bind_param('si', $session_id, $product_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
}

function clearCart() {
    global $db;
    
    $session_id = $_POST['session_id'] ?? '';
    
    if (empty($session_id)) {
        throw new Exception('Session ID is required');
    }
    
    $delete_sql = "DELETE FROM physical_sales_cart WHERE session_id = ?";
    $stmt = $db->con->prepare($delete_sql);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
}
?>
