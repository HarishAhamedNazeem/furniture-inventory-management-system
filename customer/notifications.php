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

// Mark notification as read if requested
if (isset($_GET['mark_read']) && isset($_GET['notification_id'])) {
    $notification_id = (int)$_GET['notification_id'];
    $mark_read_sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
                     WHERE id = " . $notification_id . " AND customer_id = " . $customer_id;
    $db->query($mark_read_sql);
    
    header('Location: notifications.php');
    exit();
}

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $mark_all_sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
                    WHERE customer_id = " . $customer_id . " AND is_read = 0";
    $db->query($mark_all_sql);
    
    header('Location: notifications.php');
    exit();
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "n.customer_id = " . $customer_id;
switch ($filter) {
    case 'unread':
        $where_clause .= " AND n.is_read = 0";
        break;
    case 'read':
        $where_clause .= " AND n.is_read = 1";
        break;
    case 'order_status':
        $where_clause .= " AND n.type = 'order_status'";
        break;
    case 'order_rejection':
        $where_clause .= " AND n.type = 'order_rejection'";
        break;
    case 'payment_status':
        $where_clause .= " AND n.type = 'payment_status'";
        break;
}

// Get notifications
$notifications_sql = "SELECT n.*, o.order_number 
                     FROM notifications n 
                     LEFT JOIN orders o ON n.order_id = o.id 
                     WHERE " . $where_clause . " 
                     ORDER BY n.created_at DESC";
$notifications_result = $db->query($notifications_sql);

// Get counts for filter tabs
$counts = [
    'all' => 0,
    'unread' => 0,
    'read' => 0,
    'order_status' => 0,
    'order_rejection' => 0,
    'payment_status' => 0
];

$count_sql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) as unread,
              SUM(CASE WHEN n.is_read = 1 THEN 1 ELSE 0 END) as `read`,
              SUM(CASE WHEN n.type = 'order_status' THEN 1 ELSE 0 END) as order_status,
              SUM(CASE WHEN n.type = 'order_rejection' THEN 1 ELSE 0 END) as order_rejection,
              SUM(CASE WHEN n.type = 'payment_status' THEN 1 ELSE 0 END) as payment_status
              FROM notifications n
              WHERE n.customer_id = " . $customer_id;
$count_result = $db->query($count_sql);
$count_data = $db->fetch_assoc($count_result);

$counts['all'] = $count_data['total'];
$counts['unread'] = $count_data['unread'];
$counts['read'] = $count_data['read'];
$counts['order_status'] = $count_data['order_status'];
$counts['order_rejection'] = $count_data['order_rejection'];
$counts['payment_status'] = $count_data['payment_status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
    <style>
        /* Notifications Hero Section - Modern Dashboard Style */
        .notifications-hero {
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
        
        .notifications-hero::before {
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
        
        .notifications-actions {
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
        
        .notifications-container {
            background: #b7bdbb;
            min-height: 100vh;
            padding-top: 2rem;
        }
        
        .notification-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: var(--spacing-md);
            overflow: hidden;
            transition: all var(--transition-normal);
            border: 2px solid var(--border-light);
            border-left: 5px solid var(--secondary-color);
        }
        
        .notification-card.unread {
            border-left-color: var(--primary-color);
            background: var(--bg-ash);
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: var(--border-dark);
        }
        
        .notification-header {
            padding: 20px 25px 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .notification-icon.order-status {
            background: var(--primary-color);
            color: white;
        }
        
        .notification-icon.payment-status {
            background: var(--secondary-color);
            color: white;
        }
        
        .notification-icon.general {
            background: var(--accent-color);
            color: white;
        }
        
        .notification-icon.order-rejection {
            background: linear-gradient(45deg, #DC143C, #B22222);
            color: white;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .notification-message {
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #888;
        }
        
        .notification-time {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-tabs {
            background: white;
            border-radius: 15px;
            padding: 15px;
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
            position: relative;
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
        
        .filter-tab .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
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
        
        .mark-read-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .mark-read-btn:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }
        
        .notification-badge {
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body class="customer-portal">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Enhanced Page Header -->
    <section class="notifications-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <h1 class="hero-title">Notifications</h1>
                        <p class="hero-subtitle">Stay updated with your order status and important messages</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="notifications-actions">
                        <?php if ($counts['unread'] > 0): ?>
                            <a href="?mark_all_read=1" class="action-btn primary">
                                <i class="fas fa-check-double"></i>
                                <span>Mark All Read</span>
                            </a>
                        <?php endif; ?>
                        <a href="orders.php" class="action-btn primary">
                            <i class="fas fa-list"></i>
                            <span>My Orders</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="notifications-container">
        <div class="container">

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    All Notifications
                    <?php if ($counts['all'] > 0): ?>
                        <span class="badge"><?php echo $counts['all']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter == 'unread' ? 'active' : ''; ?>">
                    Unread
                    <?php if ($counts['unread'] > 0): ?>
                        <span class="badge"><?php echo $counts['unread']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?filter=read" class="filter-tab <?php echo $filter == 'read' ? 'active' : ''; ?>">
                    Read
                    <?php if ($counts['read'] > 0): ?>
                        <span class="badge"><?php echo $counts['read']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?filter=order_status" class="filter-tab <?php echo $filter == 'order_status' ? 'active' : ''; ?>">
                    Order Updates
                    <?php if ($counts['order_status'] > 0): ?>
                        <span class="badge"><?php echo $counts['order_status']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?filter=order_rejection" class="filter-tab <?php echo $filter == 'order_rejection' ? 'active' : ''; ?>">
                    Order Rejections
                    <?php if ($counts['order_rejection'] > 0): ?>
                        <span class="badge"><?php echo $counts['order_rejection']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?filter=payment_status" class="filter-tab <?php echo $filter == 'payment_status' ? 'active' : ''; ?>">
                    Payment Updates
                    <?php if ($counts['payment_status'] > 0): ?>
                        <span class="badge"><?php echo $counts['payment_status']; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Notifications Content -->
            <?php if ($db->num_rows($notifications_result) > 0): ?>
                <div id="notificationsContainer">
                    <?php while ($notification = $db->fetch_assoc($notifications_result)): ?>
                        <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-header">
                                <div class="d-flex">
                                    <div class="notification-icon <?php echo $notification['type']; ?>">
                                        <?php 
                                        $icons = [
                                            'order_status' => 'fa-shopping-bag',
                                            'order_rejection' => 'fa-times-circle',
                                            'payment_status' => 'fa-credit-card',
                                            'general' => 'fa-info-circle',
                                            'promotion' => 'fa-tag'
                                        ];
                                        $icon = $icons[$notification['type']] ?? 'fa-bell';
                                        ?>
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="notification-badge">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                        <div class="notification-meta">
                                            <div class="notification-time">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M d, Y \a\t h:i A', strtotime($notification['created_at'])); ?>
                                            </div>
                                            <?php if (!empty($notification['order_number'])): ?>
                                                <div>
                                                    <a href="view_order.php?id=<?php echo $notification['order_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View Order <?php echo htmlspecialchars($notification['order_number']); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!$notification['is_read']): ?>
                                        <a href="?mark_read=1&notification_id=<?php echo $notification['id']; ?>" 
                                           class="mark-read-btn" title="Mark as read">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h5>No Notifications</h5>
                    <p>You don't have any notifications yet. When you place orders or there are updates, they'll appear here.</p>
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
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            // In a real implementation, you'd use AJAX to check for new notifications
            // For now, we'll just add a subtle visual indicator
            console.log('Checking for new notifications...');
        }, 30000);
        
        // Mark notification as read when clicked
        document.querySelectorAll('.notification-card.unread').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on action buttons
                if (e.target.closest('.notification-actions') || e.target.closest('a')) {
                    return;
                }
                
                // Find the mark as read link and trigger it
                const markReadLink = this.querySelector('.mark-read-btn');
                if (markReadLink) {
                    window.location.href = markReadLink.href;
                }
            });
        });
    </script>
</body>
</html>
