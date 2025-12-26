<?php
$page_title = 'Online Sales History';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(1);

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query conditions
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $param_types .= 'sss';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get sales data - only completed/delivered orders for history
$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        {$where_clause}
        AND o.status IN ('delivered', 'completed')
        ORDER BY o.created_at DESC 
        LIMIT 100";

if (!empty($params)) {
    $stmt = $db->con->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
} else {
    $sales_result = $db->query($sql);
}

// Get sales statistics for last 30 days
$stats_sql = "SELECT 
                COUNT(*) as total_sales,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_sale_amount,
                COUNT(DISTINCT DATE(created_at)) as active_days
              FROM orders 
              WHERE status IN ('delivered', 'completed')
              AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stats_result = $db->query($stats_sql);
$stats = $db->fetch_assoc($stats_result);
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<!-- Statistics Cards -->
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
        <div class="kpi-label">Sales (30 days)</div>
        <div class="kpi-value"><?php echo $stats['total_sales']; ?></div>
        <div class="kpi-change">Total orders count</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #43e97b;">
        <div class="kpi-label">Revenue (30 days)</div>
        <div class="kpi-value">LKR <?php echo number_format($stats['total_revenue'], 0); ?></div>
        <div class="kpi-change">Total revenue earned</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #fa709a;">
        <div class="kpi-label">Avg Sale Amount</div>
        <div class="kpi-value">LKR <?php echo number_format($stats['avg_sale_amount'], 0); ?></div>
        <div class="kpi-change">Average per order</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #a8edea;">
        <div class="kpi-label">Active Days</div>
        <div class="kpi-value"><?php echo $stats['active_days']; ?></div>
        <div class="kpi-change">Days with orders</div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <strong>
                    <span class="glyphicon glyphicon-list"></span>
                    <span>Online Sales History</span>
                </strong>
            </div>
            <div class="panel-body">
                <!-- Search and Filter Form -->
                <form method="GET" class="form-horizontal" style="margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Order number, customer, email..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" class="form-control" name="date_from" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" class="form-control" name="date_to" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="">All Status</option>
                                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="glyphicon glyphicon-search"></i> Search
                                    </button>
                                    <a href="online_sales_history.php" class="btn btn-default">Clear</a>
                                    <a href="export_online_sales.php?type=csv<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-success">
                                        <i class="glyphicon glyphicon-download"></i> CSV
                                    </a>
                                    <a href="export_online_sales.php?type=pdf<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-danger" target="_blank">
                                        <i class="glyphicon glyphicon-file"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Sales Table -->
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; ?>
                        <?php while ($sale = $db->fetch_assoc($sales_result)): ?>
                            <tr>
                                <td class="text-center"><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sale['order_number'] ?? '#' . $sale['id']); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($sale['customer_name'])): ?>
                                        <?php echo htmlspecialchars($sale['customer_name']); ?>
                                        <?php if (!empty($sale['customer_phone'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Guest Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $sale['item_count']; ?></td>
                                <td class="text-right">
                                    <strong>LKR <?php echo number_format($sale['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $payment_class = '';
                                    switch($sale['payment_status']) {
                                        case 'paid':
                                            $payment_class = 'label-success';
                                            break;
                                        case 'pending':
                                            $payment_class = 'label-warning';
                                            break;
                                        case 'failed':
                                            $payment_class = 'label-danger';
                                            break;
                                        default:
                                            $payment_class = 'label-default';
                                    }
                                    ?>
                                    <span class="label <?php echo $payment_class; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $sale['payment_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($sale['status']) {
                                        case 'delivered':
                                            $status_class = 'label-success';
                                            break;
                                        case 'completed':
                                            $status_class = 'label-info';
                                            break;
                                        default:
                                            $status_class = 'label-default';
                                    }
                                    ?>
                                    <span class="label <?php echo $status_class; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $sale['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('M d, Y', strtotime($sale['created_at'])); ?><br>
                                        <?php echo date('H:i', strtotime($sale['created_at'])); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="generate_order_invoice.php?id=<?php echo $sale['id']; ?>" 
                                           class="btn btn-info btn-sm" target="_blank" title="View Invoice">
                                            <span class="glyphicon glyphicon-file"></span>
                                        </a>
                                        <a href="view_order.php?id=<?php echo $sale['id']; ?>" 
                                           class="btn btn-primary btn-sm" title="View Details">
                                            <span class="glyphicon glyphicon-eye-open"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
