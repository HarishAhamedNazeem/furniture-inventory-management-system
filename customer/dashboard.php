<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('../includes/product_image_functions.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get customer information
$customer_sql = "SELECT * FROM customers WHERE id = " . $customer_id;
$customer_result = $db->query($customer_sql);
$customer = $db->fetch_assoc($customer_result);

// Get order statistics
$total_orders_sql = "SELECT COUNT(*) as total FROM orders WHERE customer_id = " . $customer_id;
$total_orders_result = $db->query($total_orders_sql);
$total_orders = $db->fetch_assoc($total_orders_result)['total'];

$pending_orders_sql = "SELECT COUNT(*) as pending FROM orders WHERE customer_id = " . $customer_id . " AND status IN ('pending', 'processing')";
$pending_orders_result = $db->query($pending_orders_sql);
$pending_orders = $db->fetch_assoc($pending_orders_result)['pending'];

$total_spent_sql = "SELECT SUM(total_amount) as total FROM orders WHERE customer_id = " . $customer_id . " AND payment_status = 'paid'";
$total_spent_result = $db->query($total_spent_sql);
$total_spent = $db->fetch_assoc($total_spent_result)['total'] ?: 0;

$cart_items_sql = "SELECT COUNT(*) as items FROM cart WHERE customer_id = " . $customer_id;
$cart_items_result = $db->query($cart_items_sql);
$cart_items = $db->fetch_assoc($cart_items_result)['items'];

// Get recent orders
$recent_orders_sql = "SELECT o.*, COUNT(oi.id) as item_count 
                      FROM orders o 
                      LEFT JOIN order_items oi ON o.id = oi.order_id 
                      WHERE o.customer_id = " . $customer_id . " 
                      GROUP BY o.id 
                      ORDER BY o.created_at DESC 
                      LIMIT 5";
$recent_orders_result = $db->query($recent_orders_sql);

// Get recent products (for recommendations)
$recent_products_sql = "SELECT p.*, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.categorie_id = c.id 
                        WHERE p.quantity > 0 
                        ORDER BY p.date DESC 
                        LIMIT 6";
$recent_products_result = $db->query($recent_products_sql);

// Enhanced Customer KPI Calculations
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));
$this_year = date('Y');

// Initialize KPI variables
$customer_kpis = [];

// 1. Today's Orders & Spending
$today_orders_sql = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
                      FROM orders 
                      WHERE customer_id = ? AND DATE(created_at) = ? AND status != 'cancelled'";
$stmt = $db->con->prepare($today_orders_sql);
$stmt->bind_param('is', $customer_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$today_data = $result->fetch_assoc();
$customer_kpis['today_orders'] = $today_data['orders'];
$customer_kpis['today_spent'] = $today_data['revenue'];

// 2. Monthly Orders & Spending
$monthly_orders_sql = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
                        FROM orders 
                        WHERE customer_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'";
$stmt = $db->con->prepare($monthly_orders_sql);
$stmt->bind_param('is', $customer_id, $this_month);
$stmt->execute();
$result = $stmt->get_result();
$monthly_data = $result->fetch_assoc();
$customer_kpis['monthly_orders'] = $monthly_data['orders'];
$customer_kpis['monthly_spent'] = $monthly_data['revenue'];

// 3. Yearly Orders & Spending
$yearly_orders_sql = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
                       FROM orders 
                       WHERE customer_id = ? AND YEAR(created_at) = ? AND status != 'cancelled'";
$stmt = $db->con->prepare($yearly_orders_sql);
$stmt->bind_param('is', $customer_id, $this_year);
$stmt->execute();
$result = $stmt->get_result();
$yearly_data = $result->fetch_assoc();
$customer_kpis['yearly_orders'] = $yearly_data['orders'];
$customer_kpis['yearly_spent'] = $yearly_data['revenue'];

// 4. Average Order Value
$avg_order_value = 0;
if ($total_orders > 0) {
    $avg_order_value = $total_spent / $total_orders;
}
$customer_kpis['avg_order_value'] = $avg_order_value;

// 5. Customer Lifetime Value (CLV)
$customer_kpis['lifetime_value'] = $total_spent;

// 6. Order Frequency (orders per month)
$customer_registration_date = $customer['created_at'];
$months_since_registration = max(1, (strtotime($today) - strtotime($customer_registration_date)) / (30 * 24 * 60 * 60));
$customer_kpis['order_frequency'] = $total_orders / $months_since_registration;

// 7. Last Order Date
$last_order_sql = "SELECT MAX(created_at) as last_order_date FROM orders WHERE customer_id = ? AND status != 'cancelled'";
$stmt = $db->con->prepare($last_order_sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$last_order_data = $result->fetch_assoc();
$customer_kpis['last_order_date'] = $last_order_data['last_order_date'];

// 8. Days Since Last Order
$days_since_last_order = 0;
if ($customer_kpis['last_order_date']) {
    $days_since_last_order = (strtotime($today) - strtotime($customer_kpis['last_order_date'])) / (24 * 60 * 60);
}
$customer_kpis['days_since_last_order'] = $days_since_last_order;

// 9. Customer Loyalty Score (based on frequency, recency, and monetary value)
$loyalty_score = 0;
if ($total_orders > 0) {
    $frequency_score = min(100, ($customer_kpis['order_frequency'] * 10)); // Max 100
    $recency_score = max(0, 100 - ($days_since_last_order * 2)); // Decreases by 2 points per day
    $monetary_score = min(100, ($total_spent / 10000) * 100); // Max 100 for LKR 10,000+ spent
    
    $loyalty_score = ($frequency_score + $recency_score + $monetary_score) / 3;
}
$customer_kpis['loyalty_score'] = $loyalty_score;

// 10. Wishlist Items Count
$wishlist_count_sql = "SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?";
$stmt = $db->con->prepare($wishlist_count_sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist_data = $result->fetch_assoc();
$customer_kpis['wishlist_items'] = $wishlist_data['count'];

// 11. Cart Value
$cart_value_sql = "SELECT COALESCE(SUM(c.quantity * p.sale_price), 0) as cart_value 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.customer_id = ?";
$stmt = $db->con->prepare($cart_value_sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_data = $result->fetch_assoc();
$customer_kpis['cart_value'] = $cart_data['cart_value'];

// 12. Favorite Categories (most ordered)
$favorite_categories_sql = "SELECT c.name as category_name, COUNT(oi.id) as order_count, SUM(oi.subtotal) as total_spent
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.id
                            JOIN categories c ON p.categorie_id = c.id
                            JOIN orders o ON oi.order_id = o.id
                            WHERE o.customer_id = ? AND o.status != 'cancelled'
                            GROUP BY c.id, c.name
                            ORDER BY order_count DESC
                            LIMIT 3";
$stmt = $db->con->prepare($favorite_categories_sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer_kpis['favorite_categories'] = $result->fetch_all(MYSQLI_ASSOC);

// 13. Monthly Growth Rate (comparing with last month)
$last_month_spent_sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue 
                         FROM orders 
                         WHERE customer_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'";
$stmt = $db->con->prepare($last_month_spent_sql);
$stmt->bind_param('is', $customer_id, $last_month);
$stmt->execute();
$result = $stmt->get_result();
$last_month_data = $result->fetch_assoc();
$last_month_spent = $last_month_data['revenue'];

$monthly_growth_rate = 0;
if ($last_month_spent > 0) {
    $monthly_growth_rate = (($customer_kpis['monthly_spent'] - $last_month_spent) / $last_month_spent) * 100;
}
$customer_kpis['monthly_growth_rate'] = $monthly_growth_rate;

// 14. Order Status Distribution
$order_status_sql = "SELECT status, COUNT(*) as count FROM orders WHERE customer_id = ? GROUP BY status";
$stmt = $db->con->prepare($order_status_sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer_kpis['order_status_distribution'] = $result->fetch_all(MYSQLI_ASSOC);

// 15. Payment Method Preferences
$payment_methods_sql = "SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total_amount 
                        FROM orders 
                        WHERE customer_id = ? AND status != 'cancelled'
                        GROUP BY payment_method
                        ORDER BY total_amount DESC";
$stmt = $db->con->prepare($payment_methods_sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer_kpis['payment_preferences'] = $result->fetch_all(MYSQLI_ASSOC);

// 16. Recent Activity Timeline (last 10 activities)
$recent_activity_sql = "SELECT 'order' as type, id, total_amount, status, created_at, NULL as product_name
                        FROM orders 
                        WHERE customer_id = ?
                        UNION ALL
                        SELECT 'wishlist' as type, w.id, NULL as total_amount, NULL as status, w.created_at, p.name as product_name
                        FROM wishlist w
                        JOIN products p ON w.product_id = p.id
                        WHERE w.customer_id = ?
                        ORDER BY created_at DESC
                        LIMIT 10";
$stmt = $db->con->prepare($recent_activity_sql);
$stmt->bind_param('ii', $customer_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
// Get active promotions for newsfeed
$current_time = date('Y-m-d H:i:s');
$active_promotions_sql = "SELECT * FROM promotions 
                          WHERE is_active = 1 
                          AND start_date <= ? 
                          AND end_date >= ? 
                          ORDER BY created_at DESC 
                          LIMIT 5";
$stmt = $db->con->prepare($active_promotions_sql);
$stmt->bind_param('ss', $current_time, $current_time);
$stmt->execute();
$result = $stmt->get_result();
$active_promotions = $result->fetch_all(MYSQLI_ASSOC);
?>
<!-- Remove duplicate HTML structure since header.php already includes it -->
    
    <!-- Enhanced KPI Styles -->
    <style>
    .kpi-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 12px;
        padding: 18px;
        min-height: 120px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid #8B0000 !important;
        border-left: 6px solid #8B0000 !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .stat-icon-wrapper {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .stat-icon {
        color: white;
        font-size: 20px;
    }

    .stat-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 6px;
        line-height: 1.2;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #718096;
        font-weight: 500;
        margin-bottom: 10px;
        line-height: 1.3;
    }

    .stat-trend {
        font-size: 0.8rem;
        font-weight: 500;
        margin-top: auto;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .chart-container {
        position: relative;
        height: 200px;
    }

    /* Promotions Newsfeed Styles */
    .promotions-carousel {
        overflow-x: auto;
        padding: 10px 0;
    }

    .promotions-track {
        display: flex;
        gap: 20px;
        padding: 10px 0;
        min-width: max-content;
    }

    .promotion-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 12px;
        padding: 12px;
        min-width: 200px;
        max-width: 220px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid #8B0000 !important;
        position: relative;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .promotion-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .promotion-badge {
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-block;
        margin-bottom: 15px;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }

    .promotion-content {
        margin-bottom: 15px;
    }

    .promotion-name {
        color: #2d3748;
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .promotion-description {
        color: #718096;
        font-size: 0.9rem;
        margin-bottom: 15px;
        line-height: 1.4;
    }

    .promotion-meta {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 15px;
    }

    .valid-until, .min-order {
        color: #718096;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .promotion-action {
        text-align: center;
    }

    .promotion-action .btn {
        background: #000000;
        border: none;
        color: white;
        font-weight: 600;
        padding: 8px 20px;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .promotion-action .btn:hover {
        background: #333333;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    /* Hide scrollbar for promotions carousel */
    .promotions-carousel::-webkit-scrollbar {
        display: none;
    }

    .promotions-carousel {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    @media (max-width: 768px) {
        .promotion-card {
            min-width: 180px;
            padding: 10px;
        }
        
        .promotions-track {
            gap: 15px;
        }
    }

    /* Modern Card Styles with Dark Maroon Border */
    .modern-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid #8B0000 !important;
        overflow: hidden;
    }

    .card-header-modern {
        background: rgba(255, 255, 255, 0.1);
        border-bottom: 1px solid #8B0000;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-body-modern {
        padding: 20px;
    }

    /* Color variables for consistency */
    :root {
        --primary-color: #667eea;
        --success-color: #48bb78;
        --warning-color: #ed8936;
        --danger-color: #f56565;
        --info-color: #4299e1;
    }

    @media (max-width: 768px) {
        .kpi-card {
            padding: 15px;
            min-height: 100px;
        }
        
        .stat-number {
            font-size: 1.6rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
        }
        
        .stat-trend {
            font-size: 0.7rem;
        }
        
        .stat-icon-wrapper {
            width: 40px;
            height: 40px;
            top: 12px;
            right: 12px;
        }
        
        .stat-icon {
            font-size: 16px;
        }
    }
    </style>

    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Enhanced Dashboard Header -->
    <section class="dashboard-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="welcome-content">
                        <div class="welcome-badge">
                            <i class="fas fa-sun me-2"></i>Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening'); ?>!
                        </div>
                        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($customer['name']); ?>!</h1>
                        <p class="welcome-subtitle">Here's your account overview and latest updates</p>

                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dashboard-actions">
                        <a href="shop.php" class="action-btn primary">
                            <i class="fas fa-store"></i>
                            <span>Continue Shopping</span>
                        </a>
                        <a href="cart.php" class="action-btn secondary">
                            <i class="fas fa-shopping-cart"></i>
                            <span>View Cart</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Enhanced Customer KPI Cards -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Total Orders -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="kpi-card modern">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                            <div class="stat-trend">
                                <i class="fas fa-coins"></i>
                                <span style="color: var(--success-color);">LKR <?php echo number_format($total_spent, 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Value -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="kpi-card modern">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $cart_items; ?></div>
                            <div class="stat-label">Cart Items</div>
                            <div class="stat-trend">
                                <i class="fas fa-coins"></i>
                                <span style="color: var(--primary-color);">LKR <?php echo number_format($customer_kpis['cart_value'], 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wishlist -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="kpi-card modern">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $customer_kpis['wishlist_items']; ?></div>
                            <div class="stat-label">Wishlist Items</div>
                            <div class="stat-trend">
                                <i class="fas fa-heart"></i>
                                <span style="color: var(--danger-color);">Saved for later</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Main Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-7 mb-4">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-shopping-bag"></i>Recent Orders
                                </h5>
                                <p class="card-subtitle">Your latest purchases and their status</p>
                            </div>
                            <div class="header-actions">
                                <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i>View All
                                </a>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <?php if ($db->num_rows($recent_orders_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = $db->fetch_assoc($recent_orders_result)): ?>
                                                <tr>
                                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                    <td><?php echo $order['item_count']; ?> items</td>
                                                    <td><strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state-modern">
                                    <div class="empty-icon">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <h6>No Orders Yet</h6>
                                    <p>Start shopping to see your orders here</p>
                                    <a href="shop.php" class="btn btn-primary">
                                        <i class="fas fa-shopping-cart"></i>Start Shopping
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Promotions Section -->
                    <?php if (!empty($active_promotions)): ?>
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-fire"></i> Hot Deals & Promotions
                                </h5>
                                <p class="card-subtitle">Don't miss out on these amazing offers!</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="promotions-carousel">
                                <div class="promotions-track">
                                    <?php foreach ($active_promotions as $promotion): ?>
                                        <div class="promotion-card">
                                            <div class="promotion-badge">
                                                <i class="fas fa-tag"></i>
                                                <?php echo $promotion['discount_type'] == 'percentage' ? $promotion['discount_value'] . '% OFF' : 'LKR ' . $promotion['discount_value'] . ' OFF'; ?>
                                            </div>
                                            <div class="promotion-content">
                                                <h5 class="promotion-name"><?php echo htmlspecialchars($promotion['name']); ?></h5>
                                                <p class="promotion-description"><?php echo htmlspecialchars($promotion['description']); ?></p>
                                                <div class="promotion-meta">
                                                    <span class="valid-until">
                                                        <i class="fas fa-clock"></i>
                                                        Valid until <?php echo date('M d, Y', strtotime($promotion['end_date'])); ?>
                                                    </span>
                                                    <?php if ($promotion['min_order_amount'] > 0): ?>
                                                        <span class="min-order">
                                                            <i class="fas fa-shopping-cart"></i>
                                                            Min order: LKR <?php echo number_format($promotion['min_order_amount'], 0); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="promotion-action">
                                                <a href="shop.php" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-shopping-bag"></i> Shop Now
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-5 mb-4">
                    <!-- Quick Actions -->
                    <div class="modern-card mb-4">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-bolt"></i>Quick Actions
                                </h5>
                                <p class="card-subtitle">Frequently used features</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="shop.php" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3">
                                        <i class="fas fa-store mb-2" style="font-size: 1.5rem;"></i>
                                        <span class="fw-semibold">Shop</span>
                                        <small class="text-muted">Browse catalog</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="cart.php" class="btn btn-outline-info w-100 d-flex flex-column align-items-center p-3">
                                        <i class="fas fa-shopping-cart mb-2" style="font-size: 1.5rem;"></i>
                                        <span class="fw-semibold">Cart</span>
                                        <small class="text-muted"><?php echo $cart_items; ?> items</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="profile.php" class="btn btn-outline-warning w-100 d-flex flex-column align-items-center p-3">
                                        <i class="fas fa-user mb-2" style="font-size: 1.5rem;"></i>
                                        <span class="fw-semibold">Profile</span>
                                        <small class="text-muted">Manage account</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="support.php" class="btn btn-outline-success w-100 d-flex flex-column align-items-center p-3">
                                        <i class="fas fa-headset mb-2" style="font-size: 1.5rem;"></i>
                                        <span class="fw-semibold">Support</span>
                                        <small class="text-muted">24/7 help</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Products -->
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <h5 class="card-title">
                                    <i class="fas fa-star"></i>Featured Products
                                </h5>
                                <p class="card-subtitle">Handpicked for you</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <?php if ($db->num_rows($recent_products_result) > 0): ?>
                                <div class="row g-3">
                                    <?php while ($product = $db->fetch_assoc($recent_products_result)): ?>
                                        <div class="col-12">
                                            <div class="d-flex align-items-center p-3 border rounded">
                                                <div class="flex-shrink-0">
                                                    <img src="<?php
                                                        // Get primary image from new system
                                                        $primary_image = get_primary_product_image($product['id']);
                                                        if ($primary_image) {
                                                            $filename = $primary_image['image_filename'];
                                                            $image_path = "../uploads/products/" . $filename;
                                                            if (!file_exists($image_path)) {
                                                                $image_path = "../uploads/products/default.jpg";
                                                            }
                                                        } else {
                                                            $image_path = "../uploads/products/default.jpg";
                                                        }
                                                        echo $image_path;
                                                    ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <p class="mb-1 text-primary fw-bold">LKR <?php echo number_format($product['sale_price'], 2); ?></p>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="shop.php" class="btn btn-outline-primary btn-sm">
                                        View All Products
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="empty-state-modern">
                                    <div class="empty-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <h6>No Products Available</h6>
                                    <p>Check back later for new products</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
// Chart.js configuration
Chart.defaults.font.family = "'Segoe UI', 'Roboto', 'Arial', sans-serif";
Chart.defaults.color = '#718096';

// Add animation to KPI cards
document.addEventListener('DOMContentLoaded', function() {
    const kpiCards = document.querySelectorAll('.kpi-card');
    
    kpiCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Add hover effects to KPI cards
document.querySelectorAll('.kpi-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
        this.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.15)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
        this.style.boxShadow = '0 6px 20px rgba(0, 0, 0, 0.08)';
    });
});

// Auto-scroll promotions carousel
document.addEventListener('DOMContentLoaded', function() {
    const promotionsTrack = document.querySelector('.promotions-track');
    if (promotionsTrack && promotionsTrack.children.length > 1) {
        let scrollPosition = 0;
        const scrollSpeed = 1;
        const cardWidth = 220; // Approximate card width + gap
        
        function autoScroll() {
            scrollPosition += scrollSpeed;
            if (scrollPosition >= cardWidth) {
                scrollPosition = 0;
            }
            promotionsTrack.style.transform = `translateX(-${scrollPosition}px)`;
        }
        
        // Only auto-scroll if there are multiple promotions
        if (promotionsTrack.children.length > 1) {
            setInterval(autoScroll, 50);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?> 