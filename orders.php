<?php

// Turn off output buffering for AJAX requests
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if (ob_get_length()) {
        ob_end_clean();
    }
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('includes/load.php');
require_once('includes/db.php');

// Fetch all products for product filter
$product_list = [];
$product_query = $conn->query("SELECT id, name FROM products ORDER BY name ASC");
if ($product_query && $product_query->num_rows > 0) {
    while ($row = $product_query->fetch_assoc()) {
        $product_list[] = $row;
    }
}

// Handle AJAX status update
if(isset($_POST['update_status'])) {
    header('Content-Type: application/json');
    
    try {
        $order_id = (int)$_POST['order_id'];
        $new_status = $conn->real_escape_string($_POST['status']);
        
        // Validate status
        $valid_statuses = ['pending', 'processing', 'delivered', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception('Invalid status value');
        }
        
        // Check if order exists
        $check_sql = "SELECT id FROM orders WHERE id = $order_id";
        $check_result = $conn->query($check_sql);
        if (!$check_result || $check_result->num_rows === 0) {
            throw new Exception('Order not found');
        }
        
        // Update the status
        $sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit(); // Ensure script exits after AJAX handling
}

// Only load header, content, and footer for non-AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {

require_once('layouts/header.php');

// Get search query and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_status_filter = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$product_filter = isset($_GET['product']) ? (int)$_GET['product'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$amount_min = isset($_GET['amount_min']) ? (float)$_GET['amount_min'] : 0;
$amount_max = isset($_GET['amount_max']) ? (float)$_GET['amount_max'] : 0;

// Build the SQL query with filters
$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
        GROUP_CONCAT(p.name SEPARATOR ', ') as products,
        GROUP_CONCAT(oi.quantity SEPARATOR ', ') as quantities,
        GROUP_CONCAT(oi.price SEPARATOR ', ') as prices
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (c.name LIKE '%$search%' OR o.id LIKE '%$search%' OR p.name LIKE '%$search%')";
}

if ($product_filter > 0) {
    $sql .= " AND p.id = $product_filter";
}

if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $sql .= " AND o.status = '$status_filter'";
}

if (!empty($payment_status_filter)) {
    $payment_status_filter = $conn->real_escape_string($payment_status_filter);
    $sql .= " AND o.payment_status = '$payment_status_filter'";
}

if (!empty($date_from)) {
    $date_from = $conn->real_escape_string($date_from);
    $sql .= " AND DATE(o.created_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $date_to = $conn->real_escape_string($date_to);
    $sql .= " AND DATE(o.created_at) <= '$date_to'";
}

if ($amount_max > 0) {
    $sql .= " AND o.total_amount <= $amount_max;";
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
$result = $conn->query($sql);

?>

<!-- Order Statistics Cards -->
<style>
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 15px;
  margin-bottom: 25px;
}

.kpi-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 18px;
  min-height: 120px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-left: 6px solid #667eea;
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

.kpi-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 6px;
  line-height: 1.2;
  order: 0;
}

.kpi-label {
  font-size: 1.4rem;
  color: #2d3748;
  font-weight: 1000;
  margin-bottom: 10px;
  line-height: 1.3;
  order: -1;
}

.kpi-change {
  font-size: 1.2rem;
  font-weight: 500;
  margin-top: auto;
  order: 1;
  color: #000000;
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .kpi-card {
    padding: 15px;
    min-height: 100px;
  }
  
  .kpi-value {
    font-size: 1.6rem;
  }
  
  .kpi-label {
    font-size: 1rem;
  }
  
  .kpi-change {
    font-size: 0.9rem;
  }
}
</style>

<div class="kpi-grid">
    <div class="kpi-card" style="border-left-color: #667eea;">
        <div class="kpi-label">Total Orders</div>
        <div class="kpi-value">
            <?php 
            $total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
            echo $total_orders;
            ?>
        </div>
        <div class="kpi-change">All orders</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #fa709a;">
        <div class="kpi-label">Pending Orders</div>
        <div class="kpi-value">
            <?php 
            $pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
            echo $pending_orders;
            ?>
        </div>
        <div class="kpi-change">Awaiting processing</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #43e97b;">
        <div class="kpi-label">Completed Orders</div>
        <div class="kpi-value">
            <?php 
            $completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'")->fetch_assoc()['count'];
            echo $completed_orders;
            ?>
        </div>
        <div class="kpi-change">Successfully delivered</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #e74c3c;">
        <div class="kpi-label">Rejected Orders</div>
        <div class="kpi-value">
            <?php 
            $rejected_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'];
            echo $rejected_orders;
            ?>
        </div>
        <div class="kpi-change">Cancelled orders</div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>
                    <i class="fa fa-list-alt"></i>
                    <span>Customer Orders Management</span>
                </strong>
                <div class="pull-right">
                    <span class="badge">
                        <?php echo $result ? $result->num_rows : 0; ?> Orders
                    </span>
                </div>

            </div>
            <div class="panel-body">
                <!-- Enhanced Search and Filter Form -->
                <div class="panel panel-default" style="margin-bottom: 30px;">
                    <div class="panel-heading">
                        <h5 style="margin: 0;"></i>Search & Filter Orders</h5>
                    </div>
                    <div class="panel-body">
                        <form method="GET" class="form-horizontal">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="search">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Customer, Order ID, Product..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="status">Order Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="">All Status (<?php echo $total_orders; ?>)</option>
                                            <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending (<?php echo $pending_orders; ?>)</option>
                                            <option value="processing" <?php echo ($status_filter === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo ($status_filter === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo ($status_filter === 'delivered') ? 'selected' : ''; ?>>Delivered (<?php echo $completed_orders; ?>)</option>
                                            <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="payment_status">Payment Status</label>
                                        <select class="form-control" id="payment_status" name="payment_status">
                                            <option value="">All Payment</option>
                                            <option value="pending" <?php echo ($payment_status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo ($payment_status_filter === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                            <option value="failed" <?php echo ($payment_status_filter === 'failed') ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="product">Product</label>
                                        <select class="form-control" id="product" name="product">
                                            <option value="0">All Products</option>
                                            <?php foreach($product_list as $prod): ?>
                                                <option value="<?php echo (int)$prod['id']; ?>" <?php echo ($product_filter == $prod['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prod['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="date_from">From Date</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="date_to">To Date</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="amount_max">Max Amount</label>
                                        <input type="number" class="form-control" id="amount_max" name="amount_max" value="<?php echo htmlspecialchars($amount_max); ?>" placeholder="Max Amount" step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fa fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">&nbsp;</label>
                                        <a href="orders.php" class="btn btn-default btn-block">
                                            <i class="fa fa-refresh"></i> Clear Filters
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <!-- Empty space for alignment -->
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Enhanced Orders Table -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h5 style="margin: 0;"><i class="fa fa-table"></i> Orders List</h5>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th></i>#</th>
                                <th></i>Customer</th>
                                <th>Products</th>
                                <th>Total Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0) : ?>
                            <?php while ($order = $result->fetch_assoc()) : ?>
                                <tr class="order-row" data-order-id="<?php echo $order['id']; ?>" style="transition: all 0.3s ease;">
                                    <td style="vertical-align: middle;">
                                        <div class="order-number">
                                            <strong style="color: #2c3e50;"><?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?></strong>
                                            <br><small class="text-muted"><i class="fa fa-tag"></i> ID: <?php echo $order['id']; ?></small>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <div class="customer-info">
                                            <strong style="color: #34495e;"><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                            <br><small class="text-muted"><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            <?php if (!empty($order['customer_phone'])): ?>
                                                <br><small class="text-muted"><i class="fa fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <div class="product-list" style="max-width: 200px;">
                                            <?php 
                                            $products = explode(', ', $order['products'] ?? '');
                                            $quantities = explode(', ', $order['quantities'] ?? '');
                                            $prices = explode(', ', $order['prices'] ?? '');
                                            
                                            for ($i = 0; $i < count($products) && $i < 3; $i++): 
                                                if (!empty($products[$i])): ?>
                                                    <div class="product-item" style="padding: 3px 0; border-bottom: 1px solid #eee;">
                                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                                            <div>
                                                                <strong style="color: #2c3e50;"><?php echo htmlspecialchars($products[$i]); ?></strong>
                                                                <br><small class="text-muted">Qty: <?php echo htmlspecialchars($quantities[$i] ?? '1'); ?></small>
                                                            </div>
                                                            <?php if (isset($prices[$i])): ?>
                                                                <span class="text-success" style="font-weight: bold;">LKR <?php echo number_format($prices[$i], 2); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif;
                                            endfor;
                                            
                                            if (count($products) > 3): ?>
                                                <div style="padding: 5px 0; text-align: center;">
                                                    <small class="text-muted"><i class="fa fa-plus-circle"></i> <?php echo count($products) - 3; ?> more items</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle; text-align: right;">
                                        <div class="amount-info">
                                            <strong style="color: #27ae60; font-size: 16px;">LKR <?php echo number_format($order['total_amount'], 2); ?></strong>
                                            <?php if ($order['discount_amount'] > 0): ?>
                                                <br><small class="text-success"><i class="fa fa-tag"></i> Discount: -LKR <?php echo number_format($order['discount_amount'], 2); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle; text-align: center;">
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['payment_status']); ?>" style="padding: 6px 12px; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 11px;">
                                            <i class="fa fa-<?php echo $order['payment_status'] === 'paid' ? 'check-circle' : ($order['payment_status'] === 'failed' ? 'times-circle' : 'clock-o'); ?>"></i>
                                            <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td style="vertical-align: middle; text-align: center;">
                                        <span class="status-badge status-<?php echo $order['status']; ?>" style="padding: 6px 12px; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 11px;">
                                            <i class="fa fa-<?php echo $order['status'] === 'pending' ? 'clock-o' : ($order['status'] === 'processing' ? 'cog' : ($order['status'] === 'delivered' ? 'check-circle' : ($order['status'] === 'cancelled' ? 'times-circle' : 'truck'))); ?>"></i>
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td style="vertical-align: middle; text-align: center;">
                                        <div class="date-info">
                                            <strong style="color: #2c3e50;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></strong>
                                            <br><small class="text-muted"><i class="fa fa-clock-o"></i> <?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <div class="btn-group-vertical" style="width: 100%;">
                                            <a href="view_order.php?id=<?php echo (int)$order['id']; ?>" 
                                               class="btn btn-info btn-xs" title="View Order Details" style="margin-bottom: 2px;">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                            
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-success btn-xs accept-order" 
                                                        data-order-id="<?php echo (int)$order['id']; ?>"
                                                        data-order-number="<?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>"
                                                        title="Accept Order" style="margin-bottom: 2px;">
                                                    <i class="fa fa-check"></i> Accept
                                                </button>
                                                <button type="button" class="btn btn-danger btn-xs reject-order" 
                                                        data-order-id="<?php echo (int)$order['id']; ?>"
                                                        data-order-number="<?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>"
                                                        title="Reject Order" style="margin-bottom: 2px;">
                                                    <i class="fa fa-times"></i> Reject
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!(($order['status'] === 'delivered' || $order['status'] === 'shipped') && $order['payment_status'] === 'paid') && $order['status'] !== 'cancelled'): ?>
                                            <button type="button" class="btn btn-warning btn-xs edit-status" 
                                                    data-order-id="<?php echo (int)$order['id']; ?>"
                                                    data-order-number="<?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>"
                                                    data-current-status="<?php echo $order['status']; ?>"
                                                    data-current-payment-status="<?php echo htmlspecialchars($order['payment_status']); ?>"
                                                    title="Edit Status" style="margin-bottom: 2px;">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['payment_status'] === 'paid'): ?>
                                                <a href="generate_order_invoice.php?id=<?php echo (int)$order['id']; ?>" 
                                                   class="btn btn-primary btn-xs" title="Generate Invoice" target="_blank" style="margin-bottom: 2px;">
                                                    <i class="fa fa-file-pdf-o"></i> Invoice
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px;">
                                    <div style="color: #7f8c8d;">
                                        <i class="fa fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px;"></i>
                                        <h4>No orders found</h4>
                                        <p>Try adjusting your search criteria or check back later for new orders.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Order Edit Modal -->
<div class="modal fade" id="editStatusModal" tabindex="-1" role="dialog" aria-labelledby="editStatusModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editStatusModalLabel">
                    <i class="fa fa-edit"></i> Edit Order Details
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="border-left: 4px solid #007bff;">
                            <h5><i class="fa fa-info-circle"></i> Order Management</h5>
                            <p>Update order status, payment information, and add internal notes for order <strong id="editOrderNumber"></strong>.</p>
                        </div>
                    </div>
                </div>
                
                <form id="editStatusesForm">
                    <input type="hidden" id="orderId" name="order_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status"><i class="fa fa-tasks"></i> Order Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_status"><i class="fa fa-credit-card"></i> Payment Status</label>
                                <select class="form-control" id="payment_status" name="payment_status">
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="trackingNumber"><i class="fa fa-truck"></i> Tracking Number (Optional)</label>
                                <input type="text" class="form-control" id="trackingNumber" name="tracking_number" 
                                       placeholder="Enter tracking number if shipped">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="carrier"><i class="fa fa-shipping-fast"></i> Carrier (Optional)</label>
                                <select class="form-control" id="carrier" name="carrier">
                                    <option value="">Select Carrier</option>
                                    <option value="DHL">DHL</option>
                                    <option value="Koombiyo">Koombiyo</option>
                                    <option value="Domex">Domex</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="editNotes"><i class="fa fa-sticky-note"></i> Internal Notes</label>
                                <textarea class="form-control" id="editNotes" name="notes" rows="3" 
                                         placeholder="Add internal notes about this order update..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="customerNotification"><i class="fa fa-envelope"></i> Customer Notification Message (Optional)</label>
                                <textarea class="form-control" id="customerNotification" name="customer_message" rows="2" 
                                         placeholder="Add a personalized message to include in the customer notification..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="sendCustomerEmail" name="send_email" checked>
                                        <i class="fa fa-envelope"></i> Send email notification to customer
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveStatuses">
                    <i class="fa fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Order Acceptance Modal -->
<div class="modal fade" id="acceptOrderModal" tabindex="-1" role="dialog" aria-labelledby="acceptOrderModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="acceptOrderModalLabel">
                    <i class="fa fa-check-circle"></i> Accept Order
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-success" style="border-left: 4px solid #28a745;">
                            <h5><i class="fa fa-info-circle"></i> Order Acceptance Confirmation</h5>
                            <p>You are about to accept order <strong id="acceptOrderNumber"></strong>. This will:</p>
                            <ul>
                                <li>Change order status to <span class="badge badge-info">Processing</span></li>
                                <li>Send confirmation email to the customer</li>
                                <li>Create internal notification for the team</li>
                                <li>Start the order fulfillment process</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <form id="acceptOrderForm">
                    <input type="hidden" id="acceptOrderId" name="order_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="acceptNotes"><i class="fa fa-sticky-note"></i> Internal Notes (Optional)</label>
                                <textarea class="form-control" id="acceptNotes" name="notes" rows="3" 
                                         placeholder="Add any internal notes about this order acceptance..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estimatedDelivery"><i class="fa fa-calendar"></i> Estimated Delivery Date</label>
                                <input type="date" class="form-control" id="estimatedDelivery" name="estimated_delivery">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="customerMessage"><i class="fa fa-envelope"></i> Customer Message (Optional)</label>
                                <textarea class="form-control" id="customerMessage" name="customer_message" rows="2" 
                                         placeholder="Add a personalized message to include in the customer confirmation email..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="sendEmailNotification" name="send_email" checked>
                                        <i class="fa fa-envelope"></i> Send confirmation email to customer
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmAcceptOrder">
                    <i class="fa fa-check"></i> Accept Order
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.status-badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    display: inline-block;
    min-width: 100px;
    text-align: center;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.status-processing {
    background-color: #cce5ff;
    color: #004085;
    border: 1px solid #b8daff;
}

.status-delivered {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.btn-group {
    display: flex;
    gap: 5px;
}

#status {
    padding: 8px;
    font-size: 16px;
}

#status option {
    padding: 8px;
}

/* Specific styles for payment statuses if needed, using existing status colors for now */
.status-paid {
    background-color: #d4edda; /* Greenish */
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-failed {
    background-color: #f8d7da; /* Reddish */
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Enhanced order management styles */
.order-row {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.order-row:hover {
    background-color: #f8f9fa;
    border-left-color: #3498db;
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Enhanced status badges */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-pending {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    border: none;
}

.status-processing {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
}

.status-shipped {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    color: white;
    border: none;
}

.status-delivered {
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white;
    border: none;
}

.status-cancelled {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
}

.status-paid {
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white;
    border: none;
}

.status-failed {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
}

/* Enhanced panel styling */
.panel {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.panel-heading {
    border-radius: 8px 8px 0 0;
    border: none;
}

/* Enhanced button styling */
.btn-xs {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-xs:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Enhanced table styling */
.table {
    border-radius: 8px;
    overflow: hidden;
}

.table th {
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Enhanced form styling */
.form-control {
    border-radius: 6px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

/* Modern KPI cards are now handled by the inline styles above */

.product-list {
    max-width: 200px;
}

.product-item {
    margin-bottom: 5px;
    padding: 3px 0;
    border-bottom: 1px solid #eee;
}

.product-item:last-child {
    border-bottom: none;
}

.btn-group-vertical .btn {
    margin-bottom: 2px;
    font-size: 11px;
}

.accept-order {
    background-color: #5cb85c;
    border-color: #4cae4c;
}

.accept-order:hover {
    background-color: #449d44;
    border-color: #398439;
}

.reject-order {
    background-color: #d9534f;
    border-color: #d43f3a;
}

.reject-order:hover {
    background-color: #c9302c;
    border-color: #ac2925;
}

.status-processing {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.status-shipped {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

/* Loading states */
.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading:after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: button-loading-spinner 1s ease infinite;
}

@keyframes button-loading-spinner {
    from {
        transform: rotate(0turn);
    }
    to {
        transform: rotate(1turn);
    }
}

</style>

<script>
        // Optimized Bootstrap and jQuery loading check
        (function() {
            function waitForDependencies() {
                if (typeof jQuery !== 'undefined') {
                    console.log('jQuery is loaded successfully.');
                    
                    // Check if Bootstrap is also loaded
                    if (typeof $.fn.modal !== 'undefined') {
                        console.log('Bootstrap is loaded successfully.');
                        console.log('Bootstrap modal is available');
                        
                        // Execute main code when both jQuery and Bootstrap are ready
                        $(document).ready(function() {
                            console.log('jQuery document ready executed.');
                            
                            // Add modal event listeners for debugging
                            $('#editStatusModal').on('show.bs.modal', function () {
                                console.log('Edit modal is about to show');
                            });
                            
                            $('#editStatusModal').on('shown.bs.modal', function () {
                                console.log('Edit modal is now shown');
                            });
                            
                            $('#editStatusModal').on('hide.bs.modal', function () {
                                console.log('Edit modal is about to hide');
                            });
                            
                            $('#editStatusModal').on('hidden.bs.modal', function () {
                                console.log('Edit modal is now hidden');
                            });

                            // Prevent form submission
                            $('#editStatusesForm').on('submit', function(e) {
                                e.preventDefault();
                                console.log('Form submission prevented');
                                return false;
                            });

                // Handle edit statuses button click (Enhanced handler)
                $('.edit-status').on('click', function() {
                    var orderId = $(this).data('order-id');
                    var currentStatus = $(this).data('current-status');
                    var currentPaymentStatus = $(this).data('current-payment-status');
                    var orderNumber = $(this).data('order-number');
                    
                    // Populate basic fields
                    $('#editStatusModal #orderId').val(orderId);
                    $('#editStatusModal #status').val(currentStatus);
                    $('#editStatusModal #payment_status').val(currentPaymentStatus);
                    $('#editStatusModal #editOrderNumber').text(orderNumber);
                    
                    // Clear additional fields
                    $('#editStatusModal #trackingNumber').val('');
                    $('#editStatusModal #carrier').val('');
                    $('#editStatusModal #editNotes').val('');
                    $('#editStatusModal #customerNotification').val('');
                    $('#editStatusModal #sendCustomerEmail').prop('checked', true);
                    
                    // Show modal with debug
                    console.log('About to show edit modal for order:', orderId);
                    $('#editStatusModal').modal('show');
                    console.log('Modal show command executed');
                });

                // Handle save statuses button click (Enhanced handler)
                $('#saveStatuses').on('click', function() {
                    var orderId = $('#editStatusModal #orderId').val();
                    var newStatus = $('#editStatusModal #status').val();
                    var newPaymentStatus = $('#editStatusModal #payment_status').val();
                    var trackingNumber = $('#editStatusModal #trackingNumber').val();
                    var carrier = $('#editStatusModal #carrier').val();
                    var notes = $('#editStatusModal #editNotes').val();
                    var customerMessage = $('#editStatusModal #customerNotification').val();
                    var sendEmail = $('#editStatusModal #sendCustomerEmail').is(':checked');
                    
                    if (!orderId || !newStatus || !newPaymentStatus) {
                        alert('Error: Missing order ID or status/payment status');
                        return;
                    }

                    // Disable the save button to prevent double submission
                    var saveButton = $(this);
                    saveButton.prop('disabled', true);
                    saveButton.html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                    var formData = new FormData();
                    formData.append('update_statuses', true);
                    formData.append('order_id', orderId);
                    formData.append('status', newStatus);
                    formData.append('payment_status', newPaymentStatus);
                    formData.append('tracking_number', trackingNumber);
                    formData.append('carrier', carrier);
                    formData.append('notes', notes);
                    formData.append('customer_message', customerMessage);
                    formData.append('send_email', sendEmail);

                    // Send the request to the new dedicated file
                    fetch('update_statuses.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok, status: ' + response.status);
                        }
                        
                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            return response.text().then(text => {
                                console.error('Non-JSON response received:', text);
                                console.error('Content-Type header:', contentType);
                                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                            });
                        }
                        
                        return response.text().then(text => {
                            console.log('Raw response text:', text);
                            
                            // Clean the text - remove any potential whitespace or extra content
                            const cleanText = text.trim();
                            
                            try {
                                const result = JSON.parse(cleanText);
                                console.log('Parsed JSON result:', result);
                                return result;
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                console.error('Raw text that failed to parse:', cleanText);
                                
                                // Try to extract JSON from the response if it's mixed with other content
                                const jsonMatch = cleanText.match(/\{.*\}/s);
                                if (jsonMatch) {
                                    try {
                                        const extractedResult = JSON.parse(jsonMatch[0]);
                                        console.log('Extracted JSON result:', extractedResult);
                                        return extractedResult;
                                    } catch (e2) {
                                        console.error('Failed to parse extracted JSON:', e2);
                                    }
                                }
                                
                                throw new Error('Invalid JSON response: ' + e.message);
                            }
                        });
                    })
                    .then(result => {
                        console.log('AJAX success callback executed.');
                        // Check if jQuery is loaded inside the callback
                        if (typeof jQuery !== 'undefined') {
                            console.log('jQuery is loaded inside callback.');
                        } else {
                            console.error('jQuery is NOT loaded inside callback.');
                        }
                        if (typeof $.fn.modal !== 'undefined') {
                            console.log('Bootstrap modal plugin is loaded inside callback.');
                        } else {
                            console.error('Bootstrap modal plugin is NOT loaded inside callback.');
                        }

                        console.log('Processing result:', result);
                        
                        if(result && result.success) {
                // Update the order status badge (6th column)
                var orderStatusBadge = $('.edit-status[data-order-id="' + orderId + '"]').closest('tr').find('td:nth-child(6) .status-badge');
                
                orderStatusBadge.removeClass('status-pending status-processing status-delivered status-cancelled')
                           .addClass('status-' + newStatus)
                           .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

                // Update the payment status badge (5th column)
                var paymentStatusBadge = $('.edit-status[data-order-id="' + orderId + '"]').closest('tr').find('td:nth-child(5) .status-badge');
                
                paymentStatusBadge.removeClass('status-pending status-paid status-failed')
                                  .addClass('status-' + newPaymentStatus)
                                  .text(newPaymentStatus.charAt(0).toUpperCase() + newPaymentStatus.slice(1));
                
                // Show success message
                var message = $('<div>')
                        .addClass('alert alert-success status-message') // Added Bootstrap alert classes and custom class
                        .css({
                            'position': 'fixed',
                            'top': '20px', // Positioned at top right
                            'right': '20px',
                            'padding': '15px 25px', // Increased padding
                            'border-radius': '5px', // Slightly more rounded corners
                            'color': 'white',
                            'background-color': getStatusColor(newPaymentStatus), // Use payment status color for background
                            'z-index': '9999',
                            'box-shadow': '0 4px 8px rgba(0,0,0,0.2)', // Added subtle shadow
                            'opacity': '1', // Start fully visible
                            'transition': 'opacity 0.5s ease-in-out' // Added transition for fading
                        })
                        .text(result.message || 'Statuses updated successfully!'); // Updated success message
                    
                $('body').append(message);
                
                // Remove the message after 3 seconds with fade out
                setTimeout(function() {
                    message.css('opacity', '0'); // Start fade out
                    setTimeout(function() {
                        message.remove(); // Remove after fade out
                    }, 500); // Match transition duration
                }, 3000);
                
                // Hide edit button if order is cancelled
                if (newStatus === 'cancelled') {
                    $('.edit-status[data-order-id="' + orderId + '"]').hide();
                }
                
                // Use jQuery to hide the Bootstrap modal
                $('#editStatusModal').modal('hide');

            } else {
                // If result exists but success is false, show the error
                if (result && result.error) {
                    throw new Error(result.error);
                } else {
                    throw new Error('Unknown error occurred');
                }
            }
        })
        .catch(error => {
            console.error('AJAX Error Details:', error);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            
            // Check if this might be a successful request with parsing issues
            if (error.message.includes('JSON') || error.message.includes('parse')) {
                console.log('This appears to be a JSON parsing error. The request might have succeeded on the server.');
                
                // Show a different message and suggest refreshing
                var errorMessage = 'Order status update completed, but there was a display issue.\n\n';
                errorMessage += 'The order has been updated successfully on the server.\n';
                errorMessage += 'Please refresh the page to see the updated status.';
                
                alert(errorMessage);
                
                // Optionally auto-refresh after a short delay
                setTimeout(function() {
                    if (confirm('Would you like to refresh the page now to see the updated order status?')) {
                        window.location.reload();
                    }
                }, 2000);
                
                return; // Don't show the generic error
            }
            
            // Show more detailed error message for other errors
            var errorMessage = 'Error updating statuses: ' + error.message;
            if (error.message.includes('Network response was not ok')) {
                errorMessage += '\n\nThis could be due to:\n- Server error (check server logs)\n- Network connectivity issues\n- Session timeout (try refreshing the page)';
            }
            
            alert(errorMessage);
        })
        .finally(() => {
            // Re-enable the save button
            saveButton.prop('disabled', false);
            saveButton.text('Save changes');
        });
    });

    // Handle accept order button click
    $('.accept-order').on('click', function() {
        var orderId = $(this).data('order-id');
        var orderNumber = $(this).data('order-number');
        var button = $(this);
        
        // Show enhanced acceptance modal instead of simple confirm
        showAcceptOrderModal(orderId, orderNumber, button);
    });

    // Handle reject order button click
    $('.reject-order').on('click', function() {
        var orderId = $(this).data('order-id');
        var orderNumber = $(this).data('order-number');
        var button = $(this);
        
        var reason = prompt('Please provide a reason for rejecting order ' + orderNumber + ':');
        if (reason !== null && reason.trim() !== '') {
            button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: 'update_statuses.php',
                method: 'POST',
                data: {
                    update_statuses: true,
                    order_id: orderId,
                    status: 'cancelled',
                    payment_status: 'failed',
                    notes: 'Order rejected: ' + reason
                },
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Update the status badges
                        var row = button.closest('tr');
                        row.find('td:nth-child(5) .status-badge')
                           .removeClass('status-pending')
                           .addClass('status-failed')
                           .text('Failed');
                        row.find('td:nth-child(6) .status-badge')
                           .removeClass('status-pending')
                           .addClass('status-cancelled')
                           .text('Cancelled');
                        
                        // Remove accept/reject buttons and hide edit button
                        button.closest('.btn-group-vertical').find('.accept-order, .reject-order').remove();
                        button.closest('.btn-group-vertical').find('.edit-status').hide();
                        
                        // Show success message
                        showMessage('Order ' + orderNumber + ' has been rejected.', 'success');
                    } else {
                        showMessage('Error rejecting order: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reject order AJAX error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    var errorMessage = 'Error rejecting order. ';
                    if (xhr.status === 0) {
                        errorMessage += 'Network error - please check your connection.';
                    } else if (xhr.status === 500) {
                        errorMessage += 'Server error - please try again later.';
                    } else if (xhr.status === 403) {
                        errorMessage += 'Access denied - please refresh the page and try again.';
                    } else {
                        errorMessage += 'Please try again.';
                    }
                    
                    showMessage(errorMessage, 'error');
                },
                complete: function() {
                    button.removeClass('loading').prop('disabled', false);
                }
            });
        }
    });

                // Enhanced Order Acceptance Functions
                function showAcceptOrderModal(orderId, orderNumber, button) {
                    // Set order details in modal
                    $('#acceptOrderId').val(orderId);
                    $('#acceptOrderNumber').text(orderNumber);
                    
                    // Set default estimated delivery date (7 days from now)
                    var defaultDate = new Date();
                    defaultDate.setDate(defaultDate.getDate() + 7);
                    $('#estimatedDelivery').val(defaultDate.toISOString().split('T')[0]);
                    
                    // Clear previous form data
                    $('#acceptNotes').val('');
                    $('#customerMessage').val('');
                    $('#sendEmailNotification').prop('checked', true);
                    
                    // Show modal
                    $('#acceptOrderModal').modal('show');
                }

                // Handle confirm accept order button
                $('#confirmAcceptOrder').on('click', function() {
    var orderId = $('#acceptOrderId').val();
    var orderNumber = $('#acceptOrderNumber').text();
    var notes = $('#acceptNotes').val();
    var estimatedDelivery = $('#estimatedDelivery').val();
    var customerMessage = $('#customerMessage').val();
    var sendEmail = $('#sendEmailNotification').is(':checked');
    var button = $(this);
    
    // Disable button and show loading
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    
    $.ajax({
        url: 'update_statuses.php',
        method: 'POST',
        data: {
            update_statuses: true,
            order_id: orderId,
            status: 'processing',
            payment_status: 'pending',
            notes: 'Order accepted: ' + notes,
            estimated_delivery: estimatedDelivery,
            customer_message: customerMessage,
            send_email: sendEmail
        },
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (response.success) {
                // Close modal
                $('#acceptOrderModal').modal('hide');
                
                // Update the status badges
                var row = $('.accept-order[data-order-id="' + orderId + '"]').closest('tr');
                row.find('td:nth-child(6) .status-badge')
                   .removeClass('status-pending')
                   .addClass('status-processing')
                   .text('Processing');
                
                // Remove accept/reject buttons
                row.find('.accept-order, .reject-order').remove();
                
                // Show success message
                showMessage('Order ' + orderNumber + ' has been accepted and is now being processed.', 'success');
            } else {
                showMessage('Error accepting order: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Accept order AJAX error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            
            var errorMessage = 'Error accepting order. ';
            if (xhr.status === 0) {
                errorMessage += 'Network error - please check your connection.';
            } else if (xhr.status === 500) {
                errorMessage += 'Server error - please try again later.';
            } else if (xhr.status === 403) {
                errorMessage += 'Access denied - please refresh the page and try again.';
            } else {
                errorMessage += 'Please try again.';
            }
            
            showMessage(errorMessage, 'error');
        },
        complete: function() {
            button.prop('disabled', false).html('<i class="fa fa-check"></i> Accept Order');
        }
    });
});

                function showMessage(message, type) {
                    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                    var icon = type === 'success' ? 'glyphicon-ok' : 'glyphicon-exclamation-sign';
                    
                    var messageHtml = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
                                      '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                                      '<span aria-hidden="true">&times;</span></button>' +
                                      '<span class="glyphicon ' + icon + '"></span> ' + message +
                                      '</div>';
                    
                    $('body').append(messageHtml);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(function() {
                        $('.alert').fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                }

                function getStatusColor(status) {
                    var colors = {
                        'pending': '#856404',
                        'processing': '#004085',
                        'delivered': '#155724',
                        'cancelled': '#721c24'
                    };
                    return colors[status] || '#333';
                }
                
                        }); // End of $(document).ready
                    } else {
                        // Bootstrap not loaded yet, wait and try again
                        console.log('Bootstrap not loaded yet, retrying...');
                        setTimeout(waitForDependencies, 100);
                    }
                } else {
                    // jQuery not loaded yet, wait and try again
                    console.log('jQuery not loaded yet, retrying...');
                    setTimeout(waitForDependencies, 100);
                }
            }
            
            // Start waiting for jQuery and Bootstrap
            waitForDependencies();
})(); // End of IIFE
</script>

<?php if($user['user_level'] === '1'): ?>
  <!-- admin menu -->
<?php include_once('layouts/admin_menu.php');?>
<?php // ... other menu levels ?>
<?php endif;?>

<?php require_once('layouts/footer.php'); ?>

<?php } // End of non-AJAX check ?> 
