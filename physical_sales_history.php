<?php
$page_title = 'Physical Sales History';
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
    $where_conditions[] = "(ps.sale_number LIKE ? OR ps.customer_name LIKE ? OR u.name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $param_types .= 'sss';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(ps.created_at) >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(ps.created_at) <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "ps.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get sales data
$sql = "SELECT ps.*, u.name as cashier_name,
               (SELECT COUNT(*) FROM physical_sales_items WHERE sale_id = ps.id) as item_count
        FROM physical_sales ps 
        JOIN users u ON ps.cashier_id = u.id 
        {$where_clause}
        ORDER BY ps.created_at DESC 
        LIMIT 100";

if (!empty($params)) {
    $stmt = $db->con->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
} else {
    $sales_result = $db->query($sql);
}

// Get sales statistics
$stats_sql = "SELECT 
                COUNT(*) as total_sales,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_sale_amount,
                COUNT(DISTINCT DATE(created_at)) as active_days
              FROM physical_sales 
              WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
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
        <div class="kpi-change">Total sales count</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #43e97b;">
        <div class="kpi-label">Revenue (30 days)</div>
        <div class="kpi-value">LKR <?php echo number_format($stats['total_revenue'], 0); ?></div>
        <div class="kpi-change">Total revenue earned</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #fa709a;">
        <div class="kpi-label">Avg Sale Amount</div>
        <div class="kpi-value">LKR <?php echo number_format($stats['avg_sale_amount'], 0); ?></div>
        <div class="kpi-change">Average per sale</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #a8edea;">
        <div class="kpi-label">Active Days</div>
        <div class="kpi-value"><?php echo $stats['active_days']; ?></div>
        <div class="kpi-change">Days with sales</div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <strong>
                    <span class="glyphicon glyphicon-list"></span>
                    <span>Physical Sales History</span>
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
                                       placeholder="Sale number, customer, cashier..." 
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
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    <option value="partially_refunded" <?php echo $status_filter === 'partially_refunded' ? 'selected' : ''; ?>>Partially Refunded</option>
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
                                    <a href="physical_sales_history.php" class="btn btn-default">Clear</a>
                                    <a href="export_physical_sales.php?type=csv<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-success">
                                        <i class="glyphicon glyphicon-download"></i> CSV
                                    </a>
                                    <a href="export_physical_sales.php?type=pdf<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-danger" target="_blank">
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
                            <th>Sale Number</th>
                            <th>Customer</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
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
                                    <strong><?php echo htmlspecialchars($sale['sale_number']); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($sale['customer_name'])): ?>
                                        <?php echo htmlspecialchars($sale['customer_name']); ?>
                                        <?php if (!empty($sale['customer_phone'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Walk-in Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                                <td class="text-center"><?php echo $sale['item_count']; ?></td>
                                <td class="text-right">
                                    <strong>LKR <?php echo number_format($sale['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="label label-info">
                                        <?php 
                                        // Handle payment method display with smart fallback
                                        $payment_method = $sale['payment_method'];
                                        
                                        // If payment_method is 0 or invalid, determine from payment amounts
                                        if ($payment_method === '0' || $payment_method === 0 || empty($payment_method)) {
                                            $cash_amt = $sale['cash_amount'] ?? 0;
                                            $card_amt = $sale['card_amount'] ?? 0;
                                            $bank_amt = $sale['bank_transfer_amount'] ?? 0;
                                            
                                            // Count how many payment methods were used
                                            $methods_count = 0;
                                            if ($cash_amt > 0) $methods_count++;
                                            if ($card_amt > 0) $methods_count++;
                                            if ($bank_amt > 0) $methods_count++;
                                            
                                            if ($methods_count > 1) {
                                                echo 'Mixed Payment';
                                            } elseif ($cash_amt > 0) {
                                                echo 'Cash';
                                            } elseif ($card_amt > 0) {
                                                echo 'Card';
                                            } elseif ($bank_amt > 0) {
                                                echo 'Bank Transfer';
                                            } else {
                                                echo 'Cash'; // Default fallback
                                            }
                                        } else {
                                            echo ucwords(str_replace('_', ' ', $payment_method));
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($sale['status']) {
                                        case 'completed':
                                            $status_class = 'label-success';
                                            break;
                                        case 'refunded':
                                            $status_class = 'label-danger';
                                            break;
                                        case 'partially_refunded':
                                            $status_class = 'label-warning';
                                            break;
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
                                        <a href="generate_walkin_sales_invoice.php?id=<?php echo $sale['id']; ?>" 
                                           class="btn btn-info btn-sm" target="_blank" title="View Invoice">
                                            <span class="glyphicon glyphicon-file"></span>
                                        </a>
                                        <a href="physical_sales_details.php?id=<?php echo $sale['id']; ?>" 
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
