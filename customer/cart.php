<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();


define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('includes/promotions.php');


if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];


$cart_sql = "SELECT c.*, p.name, p.sale_price, p.quantity as stock_quantity, p.images, p.categorie_id, cat.name as category_name 
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             LEFT JOIN categories cat ON p.categorie_id = cat.id 
             WHERE c.customer_id = " . $customer_id . " 
             ORDER BY c.created_at DESC";
$cart_result = $db->query($cart_sql);

$cart_items = [];
$total_amount = 0;
$total_discount = 0;
$final_amount = 0;

while ($item = $db->fetch_assoc($cart_result)) {
    // Process images from JSON
    if (!empty($item['images'])) {
        $images_array = json_decode($item['images'], true);
        if (is_array($images_array) && !empty($images_array)) {
            $item['image'] = $images_array[0]; 
        } else {
            $item['image'] = null;
        }
    } else {
        $item['image'] = null;
    }
    
    // Get promotions for this product
    $promotions = getProductPromotions($item['product_id'], $item['categorie_id']);
    $promotion_info = getPromotionInfo($item['sale_price'], $promotions);
    
    if ($promotion_info) {
        $item['original_price'] = $item['sale_price'];
        $item['sale_price'] = $promotion_info['discounted_price'];
        $item['promotion'] = $promotion_info['promotion'];
        $item['discount_per_item'] = $promotion_info['discount_amount'];
    } else {
        $item['original_price'] = $item['sale_price'];
        $item['discount_per_item'] = 0;
    }
    
    $item['subtotal'] = $item['quantity'] * $item['sale_price'];
    $item['original_subtotal'] = $item['quantity'] * $item['original_price'];
    $item['subtotal_discount'] = $item['original_subtotal'] - $item['subtotal'];
    
    $total_amount += $item['original_subtotal'];
    $total_discount += $item['subtotal_discount'];
    $final_amount += $item['subtotal'];
    
    $cart_items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
    <style>
        
        .cart-hero {
            background: #b7bdbb;
            color: var(--text-white);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--border-dark);
        }
        
        .cart-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../libs/images/header5.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .cart-content {
            position: relative;
            z-index: 2;
        }
        
        h1.cart-title {
            font-size: var(--font-size-4xl);
            font-weight: 700;
            color: white;
            margin-bottom: var(--spacing-sm);
            line-height: 1.2;
        }
        p.cart-subtitle {
            font-size: var(--font-size-lg);
            color: white;
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
            max-width: 600px;
        }
        
        .cart-actions {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            position: relative;
            z-index: 3;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-btn.primary {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .action-btn.primary:hover {
            background: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: white;
        }
    </style>
</head>
<body class="customer-portal">
   
    <?php include 'includes/header.php'; ?>

    
    <section class="cart-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="cart-content">
                        <h1 class="cart-title">Shopping Cart</h1>
                        <p class="cart-subtitle">Review your selected items and proceed to secure checkout</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cart-actions">
                        <a href="shop.php" class="action-btn primary">
                            <i class="fas fa-store"></i>
                            <span>Continue Shopping</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

   
    <section class="py-4">
        <div class="container">
            <?php if (empty($cart_items)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h5>Your Cart is Empty</h5>
                    <p>Add some products to your cart to get started</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Enhanced Cart Items -->
                    <div class="col-12 mb-4">
                        <div class="modern-cart-card">
                            <div class="cart-header">
                                <div class="cart-header-content">
                                    <h5 class="cart-header-title">
                                        <i class="fas fa-shopping-cart me-2"></i>Cart Items
                                    </h5>
                                    <p class="cart-header-subtitle"><?php echo count($cart_items); ?> items in your cart</p>
                                </div>
                                <div class="cart-header-actions">
                                    <button class="btn btn-outline-primary btn-sm" id="selectAll">
                                        <i class="fas fa-check-square me-1"></i>Select All
                                    </button>
                                </div>
                            </div>
                            <div class="cart-body">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="modern-cart-item" data-cart-id="<?php echo $item['id']; ?>">
                                        <div class="cart-item-checkbox">
                                            <input type="checkbox" class="form-check-input item-checkbox" checked>
                                        </div>
                                        <div class="cart-item-image-wrapper">
                                            <?php
                                            $filename = !empty($item['image']) ? $item['image'] : 'default.jpg';
                                            $image_path = "../uploads/products/" . $filename;
                                            if (!file_exists($image_path)) {
                                                $image_path = "../uploads/products/default.jpg";
                                            }
                                            ?>
                                            <img src="<?php echo $image_path; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="cart-item-image">
                                            <?php if ($item['discount_per_item'] > 0): ?>
                                                <div class="discount-badge">
                                                    <?php echo $item['promotion']['discount_value']; ?>% OFF
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cart-item-details">
                                            <h6 class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="cart-item-category"><?php echo htmlspecialchars($item['category_name']); ?></p>
                                            <div class="cart-item-price">
                                                <?php if ($item['discount_per_item'] > 0): ?>
                                                    <span class="current-price">LKR <?php echo number_format($item['sale_price'], 2); ?></span>
                                                    <span class="original-price">LKR <?php echo number_format($item['original_price'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="current-price">LKR <?php echo number_format($item['sale_price'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                <div class="stock-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Only <?php echo $item['stock_quantity']; ?> available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cart-item-quantity">
                                            <div class="modern-quantity-control">
                                                <button class="quantity-btn decrease" 
                                                        data-cart-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                       data-cart-id="<?php echo $item['id']; ?>">
                                                <button class="quantity-btn increase" 
                                                        data-cart-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="cart-item-subtotal">
                                            <?php if ($item['subtotal_discount'] > 0): ?>
                                                <div class="subtotal-current">LKR <?php echo number_format($item['subtotal'], 2); ?></div>
                                                <div class="subtotal-original">LKR <?php echo number_format($item['original_subtotal'], 2); ?></div>
                                            <?php else: ?>
                                                <div class="subtotal-current">LKR <?php echo number_format($item['subtotal'], 2); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cart-item-actions">
                                            <button class="btn btn-outline-danger btn-sm remove-item" 
                                                    data-cart-id="<?php echo $item['id']; ?>"
                                                    title="Remove item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-12 mb-4">
                        <div class="row">
                            
                            <div class="col-lg-8 mb-4">
                                <div class="modern-summary-card">
                                    <div class="summary-header">
                                        <h5 class="summary-title">
                                            <i class="fas fa-receipt me-2"></i>Order Summary
                                        </h5>
                                        <p class="summary-subtitle">Review your order details</p>
                                    </div>
                                    <div class="summary-body">
                                        <div class="summary-line">
                                            <span class="summary-label">Subtotal (<?php echo count($cart_items); ?> items):</span>
                                            <span class="summary-value">LKR <?php echo number_format($total_amount, 2); ?></span>
                                        </div>
                                        <?php if ($total_discount > 0): ?>
                                        <div class="summary-line discount">
                                            <span class="summary-label">
                                                <i class="fas fa-tag me-1"></i>Discount:
                                            </span>
                                            <span class="summary-value">-LKR <?php echo number_format($total_discount, 2); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="summary-line">
                                            <span class="summary-label">
                                                <i class="fas fa-shipping-fast me-1"></i>Shipping:
                                            </span>
                                            <span class="summary-value free">Free</span>
                                        </div>
                                        <div class="summary-divider"></div>
                                        <div class="summary-total">
                                            <span class="total-label">Total:</span>
                                            <span class="total-value">LKR <?php echo number_format($final_amount, 2); ?></span>
                                        </div>
                                        
                                        <div class="summary-security">
                                            <div class="security-badge">
                                                <i class="fas fa-shield-alt"></i>
                                                <span>Secure Checkout</span>
                                            </div>
                                            <div class="security-features">
                                                <small>
                                                    <i class="fas fa-credit-card me-1 ms-3"></i>Multiple Payment Options
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="col-lg-4 mb-4">
                                <div class="checkout-actions-card" style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e9ecef;">
                                    <div class="checkout-header mb-4">
                                        <h5 class="checkout-title" style="font-weight: 600; color: #2c3e50; margin-bottom: 8px;">
                                            <i class="fas fa-credit-card me-2" style="color: #007bff;"></i>Ready to Checkout?
                                        </h5>
                                        <p class="checkout-subtitle" style="color: #6c757d; font-size: 14px; margin: 0;">Complete your purchase securely</p>
                                    </div>
                                    <div class="checkout-body">
                                        <div class="checkout-total-display mb-4" style="background: #f8f9fa; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div class="total-amount">
                                                <div class="total-label" style="font-size: 14px; color: #6c757d; margin-bottom: 4px;">Total Amount</div>
                                                <div class="total-value" style="font-size: 24px; font-weight: 700; color: #2c3e50;">LKR <?php echo number_format($final_amount, 2); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="checkout-actions mb-4">
                                            <a href="checkout.php" class="btn btn-dark btn-lg checkout-btn w-100 mb-3" style="background: #2c3e50; border: none; border-radius: 8px; padding: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                            </a>
                                            <a href="shop.php" class="btn btn-outline-dark w-100" style="border-radius: 8px; padding: 12px; font-weight: 500;">
                                                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                                            </a>
                                        </div>
                                        
                                        <div class="checkout-benefits" style="border-top: 1px solid #e9ecef; padding-top: 16px;">
                                            <div class="benefit-item d-flex align-items-center mb-2" style="font-size: 14px;">
                                                <i class="fas fa-shipping-fast me-2" style="color: #28a745; width: 16px;"></i>
                                                <span style="color: #495057;">Free Shipping</span>
                                            </div>
                                            <div class="benefit-item d-flex align-items-center" style="font-size: 14px;">
                                                <i class="fas fa-shield-alt me-2" style="color: #28a745; width: 16px;"></i>
                                                <span style="color: #495057;">Secure Payment</span>
                                            </div>
                                        </div>
                                    </div>

                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    
    <?php include 'includes/footer.php'; ?>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <script>
        // Modern Cart Management
        class CartManager {
            constructor() {
                this.init();
            }

            init() {
                this.bindEvents();
                this.updateCartCount();
            }

            bindEvents() {
                // Quantity controls
                document.querySelectorAll('.quantity-btn').forEach(button => {
                    button.addEventListener('click', (e) => this.handleQuantityChange(e));
                });

                // Quantity input changes
                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', (e) => this.handleQuantityInput(e));
                    input.addEventListener('blur', (e) => this.handleQuantityInput(e));
                });

                // Remove item
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.addEventListener('click', (e) => this.handleRemoveItem(e));
                });

                // Select all checkbox
                const selectAllBtn = document.getElementById('selectAll');
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', (e) => this.handleSelectAll(e));
                }

                // Individual item checkboxes
                document.querySelectorAll('.item-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', (e) => this.handleItemSelect(e));
                });
            }

            async handleQuantityChange(e) {
                const button = e.currentTarget;
                const cartId = button.dataset.cartId;
                const action = button.classList.contains('increase') ? 'increase' : 'decrease';
                const quantityInput = button.parentElement.querySelector('.quantity-input');
                const currentQuantity = parseInt(quantityInput.value);
                
                let newQuantity = currentQuantity;
                if (action === 'increase') {
                    newQuantity++;
                } else if (action === 'decrease') {
                    newQuantity = Math.max(1, currentQuantity - 1);
                }
                
                if (newQuantity === currentQuantity) return;
                
                await this.updateQuantity(cartId, newQuantity, button, quantityInput);
            }

            async handleQuantityInput(e) {
                const input = e.currentTarget;
                const cartId = input.dataset.cartId;
                const newQuantity = Math.max(1, parseInt(input.value) || 1);
                const currentQuantity = parseInt(input.value);
                
                if (newQuantity === currentQuantity) return;
                
                await this.updateQuantity(cartId, newQuantity, input, input);
            }

            async updateQuantity(cartId, newQuantity, element, quantityInput) {
                // Show loading state
                const originalContent = element.innerHTML;
                element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                element.disabled = true;
                
                try {
                    const response = await fetch('ajax/update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `cart_id=${cartId}&quantity=${newQuantity}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        quantityInput.value = newQuantity;
                        this.updateCartCount();
                        this.showNotification('Quantity updated successfully', 'success');
                        
                        // Update subtotal if available
                        if (data.subtotal) {
                            this.updateItemSubtotal(cartId, data.subtotal);
                        }
                        
                        // Reload page to update totals
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        this.showNotification(data.message || 'Error updating quantity', 'error');
                        quantityInput.value = quantityInput.dataset.originalValue || 1;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showNotification('An error occurred. Please try again.', 'error');
                    quantityInput.value = quantityInput.dataset.originalValue || 1;
                } finally {
                    element.innerHTML = originalContent;
                    element.disabled = false;
                }
            }

            async handleRemoveItem(e) {
                const button = e.currentTarget;
                const cartId = button.dataset.cartId;
                const cartItem = button.closest('.modern-cart-item');
                
                // Show styled confirmation modal
                const confirmed = await this.showConfirmationModal();
                if (!confirmed) {
                    return;
                }
                
                // Show loading state
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;
                
                try {
                    const response = await fetch('ajax/remove_from_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `cart_id=${cartId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Animate removal
                        cartItem.style.transition = 'all 0.3s ease';
                        cartItem.style.transform = 'translateX(-100%)';
                        cartItem.style.opacity = '0';
                        
                        setTimeout(() => {
                            cartItem.remove();
                            this.updateCartCount();
                            this.showNotification('Item removed from cart', 'success');
                            
                            // Check if cart is empty
                            const remainingItems = document.querySelectorAll('.modern-cart-item');
                            if (remainingItems.length === 0) {
                                setTimeout(() => {
                                    location.reload();
                                }, 500);
                            } else {
                                // Update totals
                                setTimeout(() => {
                                    location.reload();
                                }, 500);
                            }
                        }, 300);
                    } else {
                        this.showNotification(data.message || 'Error removing item', 'error');
                        button.innerHTML = originalContent;
                        button.disabled = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showNotification('An error occurred. Please try again.', 'error');
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }
            }

            handleSelectAll(e) {
                const button = e.currentTarget;
                const checkboxes = document.querySelectorAll('.item-checkbox');
                const isChecked = button.classList.contains('active');
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !isChecked;
                });
                
                button.classList.toggle('active');
                button.innerHTML = isChecked ? 
                    '<i class="fas fa-check-square me-1"></i>Select All' : 
                    '<i class="fas fa-square me-1"></i>Deselect All';
            }

            handleItemSelect(e) {
                const checkbox = e.currentTarget;
                const allCheckboxes = document.querySelectorAll('.item-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
                const selectAllBtn = document.getElementById('selectAll');
                
                if (selectAllBtn) {
                    if (checkedCheckboxes.length === allCheckboxes.length) {
                        selectAllBtn.classList.add('active');
                        selectAllBtn.innerHTML = '<i class="fas fa-square me-1"></i>Deselect All';
                    } else {
                        selectAllBtn.classList.remove('active');
                        selectAllBtn.innerHTML = '<i class="fas fa-check-square me-1"></i>Select All';
                    }
                }
            }

            updateCartCount() {
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    const cartItems = document.querySelectorAll('.modern-cart-item');
                    cartBadge.textContent = cartItems.length;
                    
                    if (cartItems.length === 0) {
                        cartBadge.style.display = 'none';
                    } else {
                        cartBadge.style.display = 'inline-block';
                    }
                }
            }

            updateItemSubtotal(cartId, subtotal) {
                const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
                if (cartItem) {
                    const subtotalElement = cartItem.querySelector('.subtotal-current');
                    if (subtotalElement) {
                        subtotalElement.textContent = `LKR ${parseFloat(subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    }
                }
            }

            showNotification(message, type = 'info') {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                `;
                
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.body.appendChild(notification);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }

            showConfirmationModal() {
                return new Promise((resolve) => {
                    // Create modal HTML
                    const modalHTML = `
                        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                                    <div class="modal-header" style="border-bottom: none; padding: 24px 24px 16px;">
                                        <div class="d-flex align-items-center w-100">
                                            <div class="modal-icon-wrapper me-3" style="width: 56px; height: 56px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash-alt" style="font-size: 24px; color: white;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="modal-title" id="deleteConfirmModalLabel" style="font-weight: 600; color: #2c3e50; margin: 0;">Remove Item?</h5>
                                                <p class="text-muted mb-0" style="font-size: 14px; margin-top: 4px;">Are you sure you want to remove this item from your cart?</p>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                    </div>
                                    <div class="modal-body" style="padding: 0 24px 24px;">
                                        <div class="alert alert-warning" style="border-radius: 8px; background: #fff3cd; border: 1px solid #ffc107;">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <small>This action cannot be undone.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="border-top: none; padding: 16px 24px 24px; gap: 12px;">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px; padding: 10px 20px; font-weight: 500;">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </button>
                                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="border-radius: 8px; padding: 10px 20px; font-weight: 500;">
                                            <i class="fas fa-trash me-2"></i>Yes, Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Remove existing modal if any
                    const existingModal = document.getElementById('deleteConfirmModal');
                    if (existingModal) {
                        existingModal.remove();
                    }

                    // Add modal to body
                    document.body.insertAdjacentHTML('beforeend', modalHTML);

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                    modal.show();

                    // Handle confirm button
                    const confirmBtn = document.getElementById('confirmDeleteBtn');
                    confirmBtn.addEventListener('click', () => {
                        modal.hide();
                        resolve(true);
                    });

                    // Handle cancel or close
                    const modalElement = document.getElementById('deleteConfirmModal');
                    modalElement.addEventListener('hidden.bs.modal', () => {
                        modalElement.remove();
                        resolve(false);
                    });
                });
            }
        }

        // Initialize cart manager when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new CartManager();
        });

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading states to buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function() {
                if (this.type === 'submit' || this.classList.contains('checkout-btn')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                    this.disabled = true;
                    
                    // Re-enable after 3 seconds (fallback)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html> 