<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get orders with detailed information
$orders_sql = "SELECT o.*, COUNT(oi.id) as item_count,
               GROUP_CONCAT(p.name SEPARATOR ', ') as product_names,
               ot.tracking_number, ot.carrier, ot.estimated_delivery
               FROM orders o 
               LEFT JOIN order_items oi ON o.id = oi.order_id 
               LEFT JOIN products p ON oi.product_id = p.id
               LEFT JOIN order_tracking ot ON o.id = ot.order_id
               WHERE o.customer_id = " . $customer_id . " 
               GROUP BY o.id 
               ORDER BY o.created_at DESC";
$orders_result = $db->query($orders_sql);

// Get unread notifications count
$notifications_sql = "SELECT COUNT(*) as unread_count FROM notifications 
                     WHERE customer_id = " . $customer_id . " AND is_read = 0";
$notifications_result = $db->query($notifications_sql);
$unread_notifications = $db->fetch_assoc($notifications_result)['unread_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
    <style>
        /* Orders Hero Section - Modern Dashboard Style */
        .orders-hero {
            background: #b7bdbb;
            color: var(--text-white);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--border-dark);
            background-image: url('../libs/images/header3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .orders-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.2) 100%);
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: var(--font-size-4xl);
            font-weight: 700;
            color: var(--text-white);
            margin-bottom: var(--spacing-sm);
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: var(--font-size-lg);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
            max-width: 600px;
        }
        
        .orders-actions {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition-normal);
            border: 2px solid transparent;
        }
        
        .action-btn.primary {
            background: var(--primary-color);
            color: var(--text-white);
            border-color: var(--primary-color);
        }
        
        .action-btn.primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--text-white);
        }
        
        .orders-container {
            background: #b7bdbb;
            min-height: 100vh;
            padding-top: 2rem;
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            border-color: rgba(0, 0, 0, 0.2);
        }
        
        .order-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .order-number {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .order-date {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .order-body {
            padding: 25px;
        }
        
        .status-tracker {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            position: relative;
        }
        
        .status-tracker::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .status-step {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .status-step.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .status-step.completed {
            border-color: var(--primary-dark);
            background: var(--primary-dark);
            color: white;
        }
        
        .status-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .status-label {
            font-size: 0.8rem;
            color: #666;
            text-align: center;
            flex: 1;
        }
        
        .status-label.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .status-label.completed {
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .product-list {
            margin: 15px 0;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .tracking-info {
            background: linear-gradient(45deg, #f5f5f5, #eeeeee);
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .notification-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 1000;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-track {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-track:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .btn-reorder {
            background: var(--secondary-color);
            border: 2px solid var(--secondary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-reorder:hover {
            background: var(--secondary-dark);
            border-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .btn-invoice {
            background: var(--accent-color);
            border: 2px solid var(--accent-color);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-invoice:hover {
            background: var(--accent-dark);
            border-color: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .filter-tabs {
            background: white;
            border-radius: 15px;
            padding: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .filter-tab {
            background: transparent;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-tab:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }
        
        .filter-tab.active:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        /* Styled Confirmation Modal */
        .confirmation-modal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .confirmation-modal .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .confirmation-modal .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }
        
        .confirmation-modal .modal-header .btn-close:hover {
            opacity: 1;
        }
        
        .confirmation-modal .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .confirmation-modal .modal-body {
            padding: 30px 25px;
            text-align: center;
        }
        
        .confirmation-modal .modal-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            animation: pulse-icon 2s infinite;
        }
        
        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .confirmation-modal .modal-message {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .confirmation-modal .modal-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            font-size: 0.95rem;
            color: #666;
        }
        
        .confirmation-modal .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 20px 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .confirmation-modal .btn-confirm {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            color: white;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .confirmation-modal .btn-confirm:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .confirmation-modal .btn-cancel {
            background: #6c757d;
            border: 2px solid #6c757d;
            color: white;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .confirmation-modal .btn-cancel:hover {
            background: #5a6268;
            border-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .confirmation-modal .btn-confirm i,
        .confirmation-modal .btn-cancel i {
            margin-right: 8px;
        }
    </style>
</head>
<body class="customer-portal">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Notification Badge -->
    <?php if ($unread_notifications > 0): ?>
        <div class="notification-badge" id="notificationBadge" onclick="showNotifications()">
            <?php echo $unread_notifications; ?>
        </div>
    <?php endif; ?>

    <!-- Enhanced Page Header -->
    <section class="orders-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <h1 class="hero-title">My Orders</h1>
                        <p class="hero-subtitle">Track and manage your orders with real-time updates</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="orders-actions">
                        <a href="shop.php" class="action-btn primary">
                            <i class="fas fa-store"></i>
                            <span>Continue Shopping</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="orders-container">
        <div class="container">

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All Orders</button>
                <button class="filter-tab" data-filter="pending">Pending</button>
                <button class="filter-tab" data-filter="processing">Processing</button>
                <button class="filter-tab" data-filter="shipped">Shipped</button>
                <button class="filter-tab" data-filter="delivered">Delivered</button>
                <button class="filter-tab" data-filter="cancelled">Cancelled</button>
            </div>

            <!-- Orders Content -->
            <?php if ($db->num_rows($orders_result) > 0): ?>
                <div id="ordersContainer">
                    <?php while ($order = $db->fetch_assoc($orders_result)): ?>
                        <div class="order-card" data-status="<?php echo $order['status']; ?>" data-order-id="<?php echo $order['id']; ?>">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">
                                        <?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="h4 mb-1">LKR <?php echo number_format($order['total_amount'], 2); ?></div>
                                    <div><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?></div>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <!-- Status Tracker -->
                                <div class="status-tracker">
                                    <?php 
                                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                                    $current_status = $order['status'];
                                    $current_index = array_search($current_status, $statuses);
                                    
                                    foreach ($statuses as $index => $status): 
                                        $class = '';
                                        if ($index < $current_index || ($current_status == 'delivered' && $status == 'delivered')) {
                                            $class = 'completed';
                                        } elseif ($status == $current_status) {
                                            $class = 'active';
                                        }
                                        
                                        $icons = [
                                            'pending' => 'fa-clock',
                                            'processing' => 'fa-cog',
                                            'shipped' => 'fa-truck',
                                            'delivered' => 'fa-check'
                                        ];
                                    ?>
                                        <div class="status-step <?php echo $class; ?>">
                                            <i class="fas <?php echo $icons[$status]; ?>"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="status-labels">
                                    <?php foreach ($statuses as $index => $status): 
                                        $class = '';
                                        if ($index < $current_index || ($current_status == 'delivered' && $status == 'delivered')) {
                                            $class = 'completed';
                                        } elseif ($status == $current_status) {
                                            $class = 'active';
                                        }
                                    ?>
                                        <div class="status-label <?php echo $class; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Tracking Information -->
                                <?php if (!empty($order['tracking_number'])): ?>
                                    <div class="tracking-info">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><i class="fas fa-truck me-2"></i>Tracking Number:</strong>
                                                <span class="ms-2"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                            </div>
                                            <?php if (!empty($order['carrier'])): ?>
                                                <div>
                                                    <strong>Carrier:</strong> <?php echo htmlspecialchars($order['carrier']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($order['estimated_delivery'])): ?>
                                            <div class="mt-2">
                                                <strong><i class="fas fa-calendar-alt me-2"></i>Estimated Delivery:</strong>
                                                <?php echo date('M d, Y', strtotime($order['estimated_delivery'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Order Summary -->
                                <div class="order-summary">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6><i class="fas fa-box me-2"></i>Order Items</h6>
                                        <div class="d-flex gap-2">
                                            <span class="badge <?php echo $order['payment_status'] == 'paid' ? 'bg-success' : ($order['payment_status'] == 'pending' ? 'bg-warning' : 'bg-danger'); ?>">
                                                Payment: <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                            <span class="badge bg-primary">
                                                Status: <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="product-list">
                                        <?php 
                                        $products = explode(', ', $order['product_names'] ?? '');
                                        foreach (array_slice($products, 0, 3) as $product): 
                                            if (!empty($product)):
                                        ?>
                                            <div class="product-item">
                                                <span><?php echo htmlspecialchars($product); ?></span>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        
                                        if (count($products) > 3):
                                        ?>
                                            <div class="product-item">
                                                <small class="text-muted">... and <?php echo count($products) - 3; ?> more items</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <div class="d-flex justify-content-between mt-3">
                                            <span>Subtotal:</span>
                                            <span>LKR <?php echo number_format($order['total_amount'] + $order['discount_amount'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between text-success">
                                            <span>Discount:</span>
                                            <span>-LKR <?php echo number_format($order['discount_amount'], 2); ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span>LKR <?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-track">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                    
                                    <?php if ($order['status'] == 'delivered'): ?>
                                        <button class="btn btn-reorder" onclick="reorderItems(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-redo me-2"></i>Reorder
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="generate_invoice.php?order_id=<?php echo $order['id']; ?>" 
                                       class="btn btn-invoice" target="_blank">
                                        <i class="fas fa-file-invoice me-2"></i>Invoice
                                    </a>
                                    
                                    <?php if (in_array($order['status'], ['shipped', 'processing']) && !empty($order['tracking_number'])): ?>
                                        <button class="btn btn-track" onclick="trackOrder('<?php echo $order['tracking_number']; ?>', '<?php echo $order['carrier']; ?>')">
                                            <i class="fas fa-map-marker-alt me-2"></i>Track Package
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h5>No Orders Yet</h5>
                    <p>Start shopping to see your orders here</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const orderCards = document.querySelectorAll('.order-card');
                
                orderCards.forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 100);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });
        
        // Refresh orders functionality
        function refreshOrders() {
            const refreshBtn = document.querySelector('[onclick="refreshOrders()"]');
            const icon = refreshBtn.querySelector('i');
            
            // Add spinning animation
            icon.classList.add('fa-spin');
            refreshBtn.disabled = true;
            
            // Simulate refresh (in real implementation, this would make an AJAX call)
            setTimeout(() => {
                icon.classList.remove('fa-spin');
                refreshBtn.disabled = false;
                
                // Show success message
                showToast('Orders refreshed successfully!', 'success');
                
                // In real implementation, you would reload the order data here
                // location.reload(); // or update via AJAX
            }, 2000);
        }
        
        // Show notifications functionality
        function showNotifications() {
            // This would typically open a notifications modal or dropdown
            window.location.href = 'notifications.php';
        }
        
        // Reorder functionality
        function reorderItems(orderId) {
            showReorderConfirmation(orderId);
        }
        
        function showReorderConfirmation(orderId) {
            const modalHtml = `
                <div class="modal fade confirmation-modal" id="reorderModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-redo"></i>
                                    Reorder Items
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="modal-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="modal-message">
                                    Are you sure you want to add all items from this order to your cart?
                                </div>
                                <div class="modal-details">
                                    <i class="fas fa-info-circle me-2"></i>
                                    This will add all products from your previous order to your current cart.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-confirm" onclick="confirmReorder(${orderId})">
                                    <i class="fas fa-check"></i> Yes, Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('reorderModal'));
            modal.show();
            
            // Remove modal from DOM when closed
            document.getElementById('reorderModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }
        
        function confirmReorder(orderId) {
            // Close the modal
            const modalElement = document.getElementById('reorderModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();
            
            // Show loading state
            showToast('Adding items to cart...', 'success');
            
            fetch('ajax/reorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Items added to cart successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'cart.php';
                    }, 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            });
        }
        
        // Track order functionality
        function trackOrder(trackingNumber, carrier) {
            // This would typically open a tracking modal or redirect to carrier website
            let trackingUrl = '#';
            
            switch(carrier?.toLowerCase()) {
                case 'dhl':
                    trackingUrl = `https://www.dhl.com/en/express/tracking.html?AWB=${trackingNumber}`;
                    break;
                case 'fedex':
                    trackingUrl = `https://www.fedex.com/fedextrack/?trknbr=${trackingNumber}`;
                    break;
                case 'ups':
                    trackingUrl = `https://www.ups.com/track?tracknum=${trackingNumber}`;
                    break;
                default:
                    // Show tracking info in modal
                    showTrackingModal(trackingNumber, carrier);
                    return;
            }
            
            window.open(trackingUrl, '_blank');
        }
        
        function showTrackingModal(trackingNumber, carrier) {
            const modalHtml = `
                <div class="modal fade" id="trackingModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Package Tracking</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                                    <h6>Tracking Number: <strong>${trackingNumber}</strong></h6>
                                    ${carrier ? `<p>Carrier: <strong>${carrier}</strong></p>` : ''}
                                    <p class="text-muted">Your package is on its way! Contact customer service for more details.</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <a href="support.php" class="btn btn-primary">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
            modal.show();
            
            // Remove modal from DOM when closed
            document.getElementById('trackingModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }
        
        // Toast notification system
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            
            const toastHtml = `
                <div class="toast show" role="alert" style="margin-bottom: 10px;">
                    <div class="toast-header">
                        <i class="fas fa-${type === 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'} me-2"></i>
                        <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const toasts = toastContainer.querySelectorAll('.toast');
                if (toasts.length > 0) {
                    toasts[0].remove();
                }
            }, 5000);
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            document.body.appendChild(container);
            return container;
        }
        
        // Real-time order status updates (WebSocket or polling)
        function initRealTimeUpdates() {
            // This would typically use WebSockets or Server-Sent Events
            // For now, we'll use polling every 30 seconds
            setInterval(() => {
                checkForOrderUpdates();
            }, 30000); // 30 seconds
        }
        
        function checkForOrderUpdates() {
            fetch('ajax/check_order_updates.php')
                .then(response => response.json())
                .then(data => {
                    if (data.updates && data.updates.length > 0) {
                        data.updates.forEach(update => {
                            updateOrderCard(update.order_id, update.status, update.payment_status);
                            showToast(`Order ${update.order_number} status updated to: ${update.status}`, 'success');
                        });
                        
                        // Update notification badge
                        if (data.unread_notifications > 0) {
                            updateNotificationBadge(data.unread_notifications);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking for updates:', error);
                });
        }
        
        function updateOrderCard(orderId, newStatus, paymentStatus) {
            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderCard) {
                // Update status tracker
                updateStatusTracker(orderCard, newStatus);
                
                // Update badges
                const statusBadge = orderCard.querySelector('.badge.bg-primary');
                if (statusBadge) {
                    statusBadge.textContent = `Status: ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}`;
                }
                
                const paymentBadge = orderCard.querySelector('.badge:not(.bg-primary)');
                if (paymentBadge && paymentStatus) {
                    paymentBadge.className = `badge ${paymentStatus === 'paid' ? 'bg-success' : (paymentStatus === 'pending' ? 'bg-warning' : 'bg-danger')}`;
                    paymentBadge.textContent = `Payment: ${paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1)}`;
                }
            }
        }
        
        function updateStatusTracker(orderCard, newStatus) {
            const statuses = ['pending', 'processing', 'shipped', 'delivered'];
            const currentIndex = statuses.indexOf(newStatus);
            
            const statusSteps = orderCard.querySelectorAll('.status-step');
            const statusLabels = orderCard.querySelectorAll('.status-label');
            
            statusSteps.forEach((step, index) => {
                step.className = 'status-step';
                if (index < currentIndex || (newStatus === 'delivered' && index === currentIndex)) {
                    step.classList.add('completed');
                } else if (index === currentIndex) {
                    step.classList.add('active');
                }
            });
            
            statusLabels.forEach((label, index) => {
                label.className = 'status-label';
                if (index < currentIndex || (newStatus === 'delivered' && index === currentIndex)) {
                    label.classList.add('completed');
                } else if (index === currentIndex) {
                    label.classList.add('active');
                }
            });
        }
        
        function updateNotificationBadge(count) {
            let badge = document.getElementById('notificationBadge');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('div');
                    badge.id = 'notificationBadge';
                    badge.className = 'notification-badge';
                    badge.onclick = showNotifications;
                    document.body.appendChild(badge);
                }
                badge.textContent = count;
            } else if (badge) {
                badge.remove();
            }
        }
        
        // Initialize real-time updates when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initRealTimeUpdates();
        });
    </script>
</body>
</html> 