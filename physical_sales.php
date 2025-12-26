<?php
$page_title = 'Physical Sales Management';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(1);

// Get current user
$user = current_user();

// Get search query if exists
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Fetch products with search and category filter
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.categorie_id = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        {$where_clause} 
        ORDER BY p.name ASC";

$stmt = $db->con->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();

// Get categories for filter
$categories = find_all('categories');

// Include promotions functions
require_once('customer/includes/promotions.php');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<div class="row">
    <!-- Product Search and Filter -->
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>
                    <span class="glyphicon glyphicon-th"></span>
                    <span>Products Available for Sale</span>
                </strong>
            </div>
            <div class="panel-body">
                <!-- Search and Filter Form -->
                <form method="GET" style="margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search products by name or description..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="0">All Categories</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="glyphicon glyphicon-search"></i> Search
                                </button>
                                <?php if (!empty($search) || $category_filter > 0): ?>
                                    <a href="physical_sales.php" class="btn btn-default">
                                        <i class="glyphicon glyphicon-remove"></i> Clear Filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Products Grid -->
                <div id="products-grid">
                    <?php 
                    $product_count = 0;
                    while($product = $products_result->fetch_assoc()): 
                        // Start new row every 3 products on medium screens, every 2 on small screens
                        if ($product_count % 3 == 0): ?>
                            <div class="row">
                        <?php endif; ?>
                        <?php
                        // Get promotions for this product
                        $promotions = getProductPromotions($product['id'], $product['categorie_id']);
                        $promotion_info = getPromotionInfo($product['sale_price'], $promotions);
                        $final_price = $promotion_info ? $promotion_info['discounted_price'] : $product['sale_price'];
                        ?>
                        <div class="col-md-4 col-sm-6 product-item" data-product-id="<?php echo $product['id']; ?>">
                            <div class="panel panel-default product-card">
                                <div class="panel-body text-center">
                                    <!-- Product Image -->
                                    <div class="product-image">
                                        <?php 
                                        // Get primary image from new system
                                        $primary_image = get_primary_product_image($product['id']);
                                        if ($primary_image && file_exists('uploads/products/' . $primary_image['image_filename'])): ?>
                                            <img src="uploads/products/<?php echo $primary_image['image_filename']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="img-responsive product-img">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="glyphicon glyphicon-picture"></i>
                                                <p>No Image</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Product Info -->
                                    <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <?php if (!empty($product['description'])): ?>
                                    <p class="product-description text-muted small">
                                        <?php 
                                        $description = htmlspecialchars($product['description']);
                                        echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                                        ?>
                                    </p>
                                    <?php endif; ?>
                                    <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                    
                                    <!-- Pricing -->
                                    <div class="pricing">
                                        <?php if($promotion_info): ?>
                                            <span class="original-price">LKR <?php echo number_format($product['sale_price'], 2); ?></span>
                                            <span class="sale-price">LKR <?php echo number_format($final_price, 2); ?></span>
                                            <div class="promotion-badge">
                                                <span class="label label-success">
                                                    <?php if($promotion_info['promotion']['discount_type'] === 'percentage'): ?>
                                                        <?php echo $promotion_info['promotion']['discount_value']; ?>% OFF
                                                    <?php else: ?>
                                                        LKR <?php echo number_format($promotion_info['promotion']['discount_value'], 2); ?> OFF
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="regular-price">LKR <?php echo number_format($final_price, 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Stock Info -->
                                    <p class="stock-info">
                                        <span class="label <?php echo $product['quantity'] > 10 ? 'label-success' : ($product['quantity'] > 0 ? 'label-warning' : 'label-danger'); ?>">
                                            Stock: <?php echo $product['quantity']; ?>
                                        </span>
                                    </p>
                                    
                                    <!-- Add to Cart Button -->
                                    <div class="add-to-cart">
                                        <?php if($product['quantity'] > 0): ?>
                                            <button class="btn btn-success btn-sm add-to-cart-btn" 
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                    data-product-price="<?php echo $final_price; ?>"
                                                    data-original-price="<?php echo $product['sale_price']; ?>"
                                                    data-product-stock="<?php echo $product['quantity']; ?>"
                                                    data-promotion-id="<?php echo $promotion_info ? $promotion_info['promotion']['id'] : ''; ?>">
                                                <i class="glyphicon glyphicon-shopping-cart"></i> Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-danger btn-sm" disabled>
                                                <i class="glyphicon glyphicon-ban-circle"></i> Out of Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                        $product_count++;
                        // Close row every 3 products
                        if ($product_count % 3 == 0): ?>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <?php 
                    // Close any remaining open row
                    if ($product_count % 3 != 0): ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopping Cart -->
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>
                    <span class="glyphicon glyphicon-shopping-cart"></span>
                    <span>Shopping Cart</span>
                </strong>
            </div>
            <div class="panel-body">
                <div id="cart-container">
                    <div id="empty-cart" class="text-center text-muted">
                        <i class="glyphicon glyphicon-shopping-cart" style="font-size: 48px;"></i>
                        <p>Your cart is empty</p>
                        <p>Add products to start a sale</p>
                    </div>
                    <div id="cart-items" style="display: none;">
                        <!-- Cart items will be populated here -->
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div id="cart-summary" style="display: none;">
                    <hr>
                    <div class="row">
                        <div class="col-xs-6"><strong>Subtotal:</strong></div>
                        <div class="col-xs-6 text-right"><span id="cart-subtotal">LKR 0.00</span></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6"><strong>Discount:</strong></div>
                        <div class="col-xs-6 text-right"><span id="cart-discount">LKR 0.00</span></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6"><strong>Total:</strong></div>
                        <div class="col-xs-6 text-right"><span id="cart-total">LKR 0.00</span></div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div id="cart-actions" style="display: none;">
                    <hr>
                    <button class="btn btn-primary btn-block" id="proceed-to-checkout">
                        <i class="glyphicon glyphicon-credit-card"></i> Proceed to Checkout
                    </button>
                    <button class="btn btn-warning btn-block" id="clear-cart">
                        <i class="glyphicon glyphicon-trash"></i> Clear Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Complete Sale</h4>
            </div>
            <div class="modal-body">
                <form id="checkout-form">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Customer Information (Optional)</h5>
                            <div class="form-group">
                                <label>Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" placeholder="Enter customer name">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" name="customer_phone" placeholder="Enter phone number">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="customer_email" placeholder="Enter email address">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Information</h5>
                            <div class="form-group">
                                <label>Payment Method *</label>
                                <select class="form-control" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                </select>
                            </div>
                            <div id="payment-details">
                                <!-- Payment details will be shown based on selected method -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="complete-sale">Complete Sale</button>
            </div>
        </div>
    </div>
</div>

<style>
.product-card {
    margin-bottom: 15px;
    transition: transform 0.2s;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-image {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 4px;
}

.product-img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.no-image {
    color: #999;
    text-align: center;
}

.no-image i {
    font-size: 48px;
    margin-bottom: 10px;
}

.product-name {
    font-size: 14px;
    margin: 8px 0 4px 0;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-category {
    font-size: 12px;
    color: #666;
}

.pricing {
}

.original-price {
    text-decoration: line-through;
    color: #999;
    font-size: 12px;
    display: block;
}

.sale-price, .regular-price {
    font-weight: bold;
    color: #28a745;
    font-size: 16px;
}

.promotion-badge {
    margin-top: 5px;
}

.stock-info {
}

.cart-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.cart-item:last-child {
    border-bottom: none;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-controls button {
    width: 25px;
    height: 25px;
    padding: 0;
    font-size: 12px;
}

.quantity-controls input {
    width: 50px;
    text-align: center;
    height: 25px;
}

.remove-item {
    color: #dc3545;
    cursor: pointer;
}

.remove-item:hover {
    color: #c82333;
}

/* Additional spacing fixes */
#products-grid .row {
    margin-bottom: 0;
}

.product-item {
    margin-bottom: 15px;
    padding-left: 7.5px;
    padding-right: 7.5px;
}

.panel-body {
    padding: 12px;
}

/* Remove extra margins from product elements */
.product-image {
    margin-bottom: 8px;
}

.product-category {
    margin-bottom: 8px;
}

.pricing {
    margin-bottom: 8px;
}

.stock-info {
    margin-bottom: 8px;
}

/* Add to Cart Confirmation Message */
.add-to-cart-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background: #28a745;
    color: white;
    padding: 15px 20px;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease-in-out;
    max-width: 300px;
}

.add-to-cart-message.show {
    transform: translateX(0);
    opacity: 1;
}

.add-to-cart-message .message-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.add-to-cart-message .message-content i {
    font-size: 18px;
    color: #fff;
}

.add-to-cart-message .message-content span {
    font-weight: 500;
    font-size: 14px;
}

/* Confirmation Modal */
.confirmation-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease-in-out;
    padding: 20px;
    box-sizing: border-box;
}

.confirmation-modal.show {
    opacity: 1;
    visibility: visible;
}

.confirmation-modal .modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0);
    cursor: pointer;
    backdrop-filter: blur(0px);
}

.confirmation-modal .modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    max-width: 500px;
    width: 100%;
    transform: scale(0.8);
    transition: transform 0.3s ease-in-out;
    margin: auto;
    border: 2px solid #f8f9fa;
    z-index: 10002;
    pointer-events: auto;
}

.confirmation-modal.show .modal-content {
    transform: scale(1);
}

.confirmation-modal .modal-header {
    padding: 30px 25px 20px 25px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px 12px 0 0;
}

.confirmation-modal .modal-header h4 {
    margin: 0;
    color: #dc3545;
    font-size: 22px;
    font-weight: 700;
    flex: 1;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.confirmation-modal .close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.confirmation-modal .close-btn:hover {
    color: #333;
}

.confirmation-modal .modal-body {
    padding: 30px 25px;
    text-align: center;
    background: #fff;
}

.confirmation-modal .modal-body p {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
    line-height: 1.7;
    font-weight: 500;
    text-shadow: 0 1px 1px rgba(0,0,0,0.05);
}

.confirmation-modal .modal-footer {
    padding: 20px 25px 30px 25px;
    display: flex;
    gap: 20px;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0 0 12px 12px;
}

.confirmation-modal .modal-footer .btn {
    padding: 15px 30px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    min-width: 120px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
    z-index: 10001;
    pointer-events: auto;
}

.confirmation-modal .modal-footer .cancel-btn {
    background: #ffffff;
    color: #6c757d;
    border: 2px solid #dee2e6;
}

.confirmation-modal .modal-footer .cancel-btn:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.confirmation-modal .modal-footer .confirm-btn {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: 2px solid #dc3545;
}

.confirmation-modal .modal-footer .confirm-btn:hover {
    background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
    border-color: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

/* Success Message */
.success-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background: #28a745;
    color: white;
    padding: 15px 20px;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease-in-out;
    max-width: 300px;
}

.success-message.show {
    transform: translateX(0);
    opacity: 1;
}

.success-message .message-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.success-message .message-content i {
    font-size: 18px;
    color: #fff;
}

.success-message .message-content span {
    font-weight: 500;
    font-size: 14px;
}

/* Sale Success Dialog */
.sale-success-dialog {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 99999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease-in-out;
    padding: 20px;
    box-sizing: border-box;
    margin: 0 !important;
}

.sale-success-dialog.show {
    opacity: 1;
    visibility: visible;
}

.sale-success-dialog .dialog-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
}

.sale-success-dialog .dialog-content {
    position: relative;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    max-width: 550px;
    width: 100%;
    transform: scale(0.9);
    transition: transform 0.3s ease-in-out;
    border: 2px solid #e9ecef;
    z-index: 100000;
    overflow: hidden;
    margin: auto;
}

.sale-success-dialog.show .dialog-content {
    transform: scale(1);
}

.sale-success-dialog .dialog-header {
    padding: 30px 25px 20px 25px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    text-align: center;
    border-bottom: 2px solid #e9ecef;
}

.sale-success-dialog .dialog-header .success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 15px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sale-success-dialog .dialog-header .success-icon i {
    font-size: 45px;
    color: #28a745;
}

.sale-success-dialog .dialog-header h3 {
    margin: 0;
    color: white;
    font-size: 24px;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.sale-success-dialog .dialog-body {
    padding: 30px 25px;
    text-align: center;
    background: #fff;
}

.sale-success-dialog .dialog-body .sale-info {
    margin-bottom: 20px;
}

.sale-success-dialog .dialog-body .sale-info p {
    margin: 8px 0;
    color: #2c3e50;
    font-size: 16px;
    line-height: 1.6;
}

.sale-success-dialog .dialog-body .sale-info .sale-number {
    font-size: 20px;
    font-weight: 700;
    color: #28a745;
}

.sale-success-dialog .dialog-body .sale-info .total-amount {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
}

.sale-success-dialog .dialog-footer {
    padding: 20px 25px 30px 25px;
    display: flex;
    gap: 15px;
    justify-content: center;
    background: #f8f9fa;
    border-top: 2px solid #e9ecef;
}

.sale-success-dialog .dialog-footer .btn {
    padding: 12px 30px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s ease;
    min-width: 140px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sale-success-dialog .dialog-footer .btn-close {
    background: #ffffff;
    color: #6c757d;
    border: 2px solid #dee2e6;
}

.sale-success-dialog .dialog-footer .btn-close:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.sale-success-dialog .dialog-footer .btn-invoice {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border: 2px solid #007bff;
}

.sale-success-dialog .dialog-footer .btn-invoice:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
    border-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}
</style>


<?php include_once('layouts/footer.php'); ?>

<script>
$(document).ready(function() {
    let cart = [];
    let sessionId = '<?php echo session_id(); ?>';
    
    console.log('Session ID:', sessionId);
    console.log('Cart initialized:', cart);
    
    // Load existing cart from server
    loadCart();
    
    // Add to cart functionality
    $(document).on('click', '.add-to-cart-btn', function() {
        console.log('Add to cart button clicked');
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        const productPrice = parseFloat($(this).data('product-price'));
        const originalPrice = parseFloat($(this).data('original-price'));
        const productStock = parseInt($(this).data('product-stock'));
        const promotionId = $(this).data('promotion-id') || null;
        
        console.log('Product data:', {productId, productName, productPrice, originalPrice, productStock, promotionId});
        
        // Show styled confirmation message
        showAddToCartMessage(productName);
        
        addToCart(productId, productName, productPrice, originalPrice, productStock, promotionId);
    });
    
    // Quantity change functionality
    $(document).on('change', '.cart-quantity', function() {
        const productId = $(this).data('product-id');
        const newQuantity = parseInt($(this).val());
        
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else {
            updateCartQuantity(productId, newQuantity);
        }
    });
    
    // Quantity increase button
    $(document).on('click', '.quantity-increase', function() {
        const productId = $(this).data('product-id');
        const item = cart.find(item => item.product_id == productId);
        if (item) {
            const productStock = item.product_stock || 999;
            
            if (item.quantity < productStock) {
                item.quantity += 1;
                updateCartDisplay();
                saveCartToServer();
            } else {
                alert('Not enough stock available');
            }
        }
    });
    
    // Quantity decrease button
    $(document).on('click', '.quantity-decrease', function() {
        const productId = $(this).data('product-id');
        const item = cart.find(item => item.product_id == productId);
        if (item && item.quantity > 1) {
            item.quantity -= 1;
            updateCartDisplay();
            saveCartToServer();
        } else if (item && item.quantity === 1) {
            removeFromCart(productId);
        }
    });
    
    // Remove item functionality
    $(document).on('click', '.remove-item', function() {
        const productId = $(this).data('product-id');
        const item = cart.find(item => item.product_id == productId);
        const productName = item ? item.product_name : 'Item';
        
        showConfirmationModal(
            'Remove Item from Cart',
            `Do you want to remove "${productName}" from your shopping cart?`,
            function() {
                removeFromCart(productId);
                showSuccessMessage(`"${productName}" has been removed from your cart.`);
            }
        );
    });
    
    // Clear cart functionality
    $('#clear-cart').click(function() {
        showConfirmationModal(
            'Clear Shopping Cart',
            'Are you sure you want to remove all items from your shopping cart? This action cannot be undone.',
            function() {
                clearCart();
                showSuccessMessage('Your shopping cart has been cleared successfully.');
            }
        );
    });
    
    // Proceed to checkout
    $('#proceed-to-checkout').click(function() {
        if (cart.length === 0) {
            alert('Cart is empty');
            return;
        }
        $('#checkoutModal').modal('show');
    });
    
    // Payment method change
    $('select[name="payment_method"]').change(function() {
        showPaymentDetails($(this).val());
    });
    
    // Complete sale
    $('#complete-sale').click(function() {
        completeSale();
    });
    
    function addToCart(productId, productName, productPrice, originalPrice, productStock, promotionId) {
        console.log('addToCart called with:', {productId, productName, productPrice, originalPrice, productStock, promotionId});
        
        const existingItem = cart.find(item => item.product_id == productId);
        
        if (existingItem) {
            if (existingItem.quantity >= productStock) {
                alert('Not enough stock available');
                return;
            }
            existingItem.quantity += 1;
            console.log('Updated existing item quantity to:', existingItem.quantity);
        } else {
            cart.push({
                product_id: productId,
                product_name: productName,
                product_price: productPrice,
                original_price: originalPrice || productPrice,
                quantity: 1,
                promotion_id: promotionId,
                product_stock: productStock
            });
            console.log('Added new item to cart. Cart now has:', cart.length, 'items');
        }
        
        updateCartDisplay();
        saveCartToServer();
    }
    
    function updateCartQuantity(productId, newQuantity) {
        const item = cart.find(item => item.product_id == productId);
        if (item) {
            item.quantity = newQuantity;
            updateCartDisplay();
            saveCartToServer();
        }
    }
    
    function removeFromCart(productId) {
        console.log('removeFromCart called with productId:', productId);
        console.log('Cart before removal:', cart);
        cart = cart.filter(item => item.product_id != productId);
        console.log('Cart after removal:', cart);
        updateCartDisplay();
        saveCartToServer();
    }
    
    function clearCart() {
        console.log('clearCart called');
        console.log('Cart before clearing:', cart);
        cart = [];
        console.log('Cart after clearing:', cart);
        updateCartDisplay();
        saveCartToServer();
    }
    
    function updateCartDisplay() {
        const cartItems = $('#cart-items');
        const emptyCart = $('#empty-cart');
        const cartSummary = $('#cart-summary');
        const cartActions = $('#cart-actions');
        
        if (cart.length === 0) {
            emptyCart.show();
            cartItems.hide();
            cartSummary.hide();
            cartActions.hide();
            return;
        }
        
        emptyCart.hide();
        cartItems.show();
        cartSummary.show();
        cartActions.show();
        
        let html = '';
        let originalSubtotal = 0;
        let discountedSubtotal = 0;
        let totalDiscount = 0;
        
        cart.forEach(item => {
            // Check if this item has a promotion applied
            const originalPrice = item.original_price || item.product_price;
            const discountedPrice = item.product_price;
            const itemDiscountedTotal = discountedPrice * item.quantity;
            const itemOriginalTotal = originalPrice * item.quantity;
            const itemDiscount = itemOriginalTotal - itemDiscountedTotal;
            
            originalSubtotal += itemOriginalTotal;
            discountedSubtotal += itemDiscountedTotal;
            totalDiscount += itemDiscount;
            
            // Build item display with discount badge if applicable
            let priceDisplay = '';
            if (item.original_price && item.original_price !== discountedPrice) {
                priceDisplay = `
                    <small><del>LKR ${originalPrice.toFixed(2)}</del></small>
                    <small class="text-success">LKR ${discountedPrice.toFixed(2)} each</small>
                    <br><span class="badge badge-success">Discount Applied</span>
                `;
            } else {
                priceDisplay = `<small>LKR ${discountedPrice.toFixed(2)} each</small>`;
            }
            
            html += `
                <div class="cart-item">
                    <div class="row">
                        <div class="col-xs-8">
                            <strong>${item.product_name}</strong>
                            <br>
                            ${priceDisplay}
                        </div>
                        <div class="col-xs-4 text-right">
                            <div class="quantity-controls">
                                <button type="button" class="btn btn-xs btn-default quantity-decrease" data-product-id="${item.product_id}">-</button>
                                <input type="number" class="form-control cart-quantity" value="${item.quantity}" min="1" data-product-id="${item.product_id}">
                                <button type="button" class="btn btn-xs btn-default quantity-increase" data-product-id="${item.product_id}">+</button>
                            </div>
                            <div class="remove-item" data-product-id="${item.product_id}">
                                <i class="glyphicon glyphicon-trash"></i> Remove
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItems.html(html);
        
        // Display subtotal as original price, discount, and final total
        $('#cart-subtotal').text('LKR ' + originalSubtotal.toFixed(2));
        $('#cart-discount').text('LKR ' + totalDiscount.toFixed(2));
        $('#cart-total').text('LKR ' + discountedSubtotal.toFixed(2));
    }
    
    function showPaymentDetails(paymentMethod) {
        const paymentDetails = $('#payment-details');
        let html = '';
        
        // Get the discounted total amount from cart (after promotions)
        let discountedTotal = 0;
        cart.forEach(item => {
            discountedTotal += item.product_price * item.quantity;
        });
        
        switch(paymentMethod) {
            case 'cash':
                html = `
                    <div class="form-group">
                        <label>Cash Received *</label>
                        <input type="number" class="form-control" id="cash-received" name="cash_amount" step="0.01" min="0" value="" placeholder="${discountedTotal.toFixed(2)}" required autofocus>
                        <small class="text-muted">Default: LKR ${discountedTotal.toFixed(2)}</small>
                    </div>
                    <div id="change-warning" style="display: none;" class="alert alert-warning">
                        <i class="glyphicon glyphicon-warning-sign"></i> Cash received is less than total amount!
                    </div>
                    <div class="form-group">
                        <label>Change Given</label>
                        <input type="number" class="form-control" id="change-given" name="change_given" step="0.01" readonly style="background-color: #f8f9fa;">
                        <small class="text-muted">This will be calculated automatically</small>
                    </div>
                `;
                // Calculate initial change
                setTimeout(() => {
                    $('#cash-received').val(discountedTotal.toFixed(2));
                    calculateChange();
                }, 100);
                break;
            case 'card':
                html = `
                    <div class="form-group">
                        <label>Card Amount *</label>
                        <input type="number" class="form-control" name="card_amount" step="0.01" min="0" value="${discountedTotal.toFixed(2)}" required>
                    </div>
                `;
                break;
        }
        
        paymentDetails.html(html);
        
        // Add event listener for cash received input
        if (paymentMethod === 'cash') {
            setTimeout(() => {
                $('#cash-received').on('input', calculateChange);
                $('#cash-received').on('keyup', calculateChange);
                $('#cash-received').on('change', calculateChange);
            }, 100);
        }
    }
    
    function calculateChange() {
        const cashReceived = parseFloat($('#cash-received').val()) || 0;
        let discountedTotal = 0;
        cart.forEach(item => {
            discountedTotal += item.product_price * item.quantity;
        });
        const change = cashReceived - discountedTotal;
        $('#change-given').val(change.toFixed(2));
        
        // Show warning if cash received is less than total
        if (cashReceived < discountedTotal && cashReceived > 0) {
            $('#change-warning').show();
        } else {
            $('#change-warning').hide();
        }
    }
    
    function saveCartToServer() {
        console.log('saveCartToServer called with cart:', cart);
        console.log('Session ID:', sessionId);
        
        $.ajax({
            url: 'ajax/physical_sales_cart.php',
            method: 'POST',
            data: {
                action: 'save_cart',
                session_id: sessionId,
                cart: JSON.stringify(cart)
            },
            success: function(response) {
                console.log('Cart saved successfully:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error saving cart:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }
    
    function loadCart() {
        $.ajax({
            url: 'ajax/physical_sales_cart.php',
            method: 'GET',
            data: {
                action: 'load_cart',
                session_id: sessionId
            },
            success: function(response) {
                if (response.success && response.cart) {
                    cart = response.cart;
                    updateCartDisplay();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading cart:', error);
            }
        });
    }
    
    function showAddToCartMessage(productName) {
        // Remove any existing messages
        $('.add-to-cart-message').remove();
        
        // Create styled message
        const message = $(`
            <div class="add-to-cart-message">
                <div class="message-content">
                    <i class="glyphicon glyphicon-ok-circle"></i>
                    <span>${productName} added to cart!</span>
                </div>
            </div>
        `);
        
        // Add to page
        $('body').append(message);
        
        // Show with animation
        setTimeout(() => {
            message.addClass('show');
        }, 10);
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            message.removeClass('show');
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 3000);
    }
    
    function showConfirmationModal(title, message, onConfirm) {
        console.log('showConfirmationModal called with:', {title, message, onConfirm});
        
        // Prevent multiple modals
        if ($('.confirmation-modal').length > 0) {
            console.log('Modal already exists, not creating new one');
            return;
        }
        
        // Remove any existing modals and success messages
        $('.confirmation-modal, .success-message').remove();
        
        // Create modal
        const modal = $(`
            <div class="confirmation-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h4>${title}</h4>
                        <button type="button" class="close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default cancel-btn">Cancel</button>
                        <button type="button" class="btn btn-danger confirm-btn">Confirm</button>
                    </div>
                </div>
            </div>
        `);
        
        console.log('Modal created:', modal);
        
        // Add to page
        $('body').append(modal);
        console.log('Modal added to page');
        
        // Show modal immediately
        modal.addClass('show');
        console.log('Modal shown');
        
        // Event handlers
        modal.find('.close-btn, .cancel-btn').click(function() {
            console.log('Cancel/Close button clicked');
            hideConfirmationModal(modal);
        });
        
        // Add multiple event handlers to ensure click is detected
        let confirmClicked = false;
        modal.find('.confirm-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Prevent multiple clicks
            if (confirmClicked) {
                console.log('Confirm already clicked, ignoring');
                return;
            }
            confirmClicked = true;
            
            console.log('Confirm button clicked (on handler)');
            console.log('onConfirm function:', onConfirm);
            
            // Remove modal immediately from DOM
            modal.remove();
            console.log('Modal removed immediately');
            
            if (onConfirm) {
                console.log('Calling onConfirm function');
                try {
                    onConfirm();
                    console.log('onConfirm function executed successfully');
                } catch (error) {
                    console.error('Error in onConfirm function:', error);
                }
            } else {
                console.log('No onConfirm function provided');
            }
        });
        
        // Also add mousedown event as backup
        modal.find('.confirm-btn').on('mousedown', function(e) {
            console.log('Confirm button mousedown detected');
        });
        
        // Add direct DOM event listener as backup
        modal.find('.confirm-btn')[0].addEventListener('click', function(e) {
            console.log('Confirm button clicked (direct DOM)');
        });
        
        // Close on backdrop click
        modal.find('.modal-backdrop').click(function() {
            hideConfirmationModal(modal);
        });
    }
    
    function hideConfirmationModal(modal) {
        console.log('hideConfirmationModal called');
        modal.removeClass('show');
        // Remove immediately without delay
        setTimeout(() => {
            modal.remove();
            console.log('Modal removed from DOM');
        }, 100);
    }
    
    function showSuccessMessage(message) {
        console.log('showSuccessMessage called with:', message);
        
        // Remove any existing success messages
        $('.success-message').remove();
        
        // Create styled message
        const successMsg = $(`
            <div class="success-message show">
                <div class="message-content">
                    <i class="glyphicon glyphicon-check"></i>
                    <span>${message}</span>
                </div>
            </div>
        `);
        
        // Add to page and show immediately
        $('body').append(successMsg);
        console.log('Success message added and shown immediately');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            successMsg.removeClass('show');
            setTimeout(() => {
                successMsg.remove();
                console.log('Success message removed');
            }, 300);
        }, 3000);
    }
    
    function completeSale() {
        // Validate cash payment
        const paymentMethod = $('select[name="payment_method"]').val();
        
        // Calculate discount amount
        let totalDiscount = 0;
        cart.forEach(item => {
            const originalPrice = item.original_price || item.product_price;
            const itemTotalOriginal = originalPrice * item.quantity;
            const itemTotalDiscounted = item.product_price * item.quantity;
            totalDiscount += (itemTotalOriginal - itemTotalDiscounted);
        });
        
        if (paymentMethod === 'cash') {
            const cashReceived = parseFloat($('#cash-received').val()) || 0;
            let discountedTotal = 0;
            cart.forEach(item => {
                discountedTotal += item.product_price * item.quantity;
            });
            
            if (cashReceived < discountedTotal) {
                alert('Cash received amount is less than the total amount. Please enter a sufficient amount.');
                $('#cash-received').focus();
                return;
            }
        }
        
        const formData = $('#checkout-form').serialize();
        const cartData = JSON.stringify(cart);
        
        console.log('Submitting sale with data:', formData);
        console.log('Cart data:', cartData);
        console.log('Total discount:', totalDiscount);
        console.log('Payment method value:', $('select[name="payment_method"]').val());
        console.log('Payment method selected option:', $('select[name="payment_method"] option:selected').text());
        
        $.ajax({
            url: 'ajax/physical_sales_checkout_simple.php',
            method: 'POST',
            data: formData + '&cart=' + cartData,
            success: function(response) {
                console.log('Checkout response:', response);
                if (response.success) {
                    // Close checkout modal first
                    $('#checkoutModal').modal('hide');
                    clearCart();
                    
                    // Wait for modal to fully close before showing success dialog
                    $('#checkoutModal').on('hidden.bs.modal', function() {
                        // Show styled success dialog
                        showSaleSuccessDialog(response.sale_number, response.total_amount, response.invoice_url);
                        // Remove the event listener to prevent multiple calls
                        $(this).off('hidden.bs.modal');
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                alert('An error occurred while processing the sale. Check console for details.');
            }
        });
    }
    
    function showSaleSuccessDialog(saleNumber, totalAmount, invoiceUrl) {
        // Remove any existing dialogs
        $('.sale-success-dialog').remove();
        
        // Format total amount
        const formattedTotal = 'LKR ' + parseFloat(totalAmount).toFixed(2);
        
        // Create dialog
        const dialog = $(`
            <div class="sale-success-dialog">
                <div class="dialog-backdrop"></div>
                <div class="dialog-content">
                    <div class="dialog-header">
                        <div class="success-icon">
                            <i class="glyphicon glyphicon-ok"></i>
                        </div>
                        <h3>Sale Completed Successfully!</h3>
                    </div>
                    <div class="dialog-body">
                        <div class="sale-info">
                            <p><strong>Sale Number:</strong></p>
                            <p class="sale-number">${saleNumber}</p>
                            <p><strong>Total Amount:</strong></p>
                            <p class="total-amount">${formattedTotal}</p>
                        </div>
                    </div>
                    <div class="dialog-footer">
                        <button type="button" class="btn btn-close">Continue</button>
                        ${invoiceUrl ? `<button type="button" class="btn btn-invoice">View Invoice</button>` : ''}
                    </div>
                </div>
            </div>
        `);
        
        // Add to page
        $('body').append(dialog);
        
        // Show dialog
        setTimeout(() => {
            dialog.addClass('show');
        }, 10);
        
        // Event handlers
        dialog.find('.btn-close').click(function() {
            hideSaleSuccessDialog(dialog);
        });
        
        if (invoiceUrl) {
            dialog.find('.btn-invoice').click(function() {
                window.open(invoiceUrl, '_blank');
                hideSaleSuccessDialog(dialog);
            });
        }
        
        // Close on backdrop click
        dialog.find('.dialog-backdrop').click(function() {
            hideSaleSuccessDialog(dialog);
        });
    }
    
    function hideSaleSuccessDialog(dialog) {
        dialog.removeClass('show');
        setTimeout(() => {
            dialog.remove();
        }, 300);
    }
});
</script>
