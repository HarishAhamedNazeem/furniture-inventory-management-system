<?php
$page_title = 'Stock Report';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(2);

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : 'all'; // all, low_stock, out_of_stock, in_stock
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name'; // name, quantity, value, category
$supplier_filter = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;

// Build query conditions
$where_conditions = [];
$params = [];
$param_types = '';

if ($category_filter > 0) {
    $where_conditions[] = "p.categorie_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

if ($supplier_filter > 0) {
    $where_conditions[] = "p.supplier_id = ?";
    $params[] = $supplier_filter;
    $param_types .= 'i';
}

// Stock status filter
switch ($stock_status) {
    case 'low_stock':
        $where_conditions[] = "CAST(p.quantity AS UNSIGNED) <= 10 AND CAST(p.quantity AS UNSIGNED) > 0";
        break;
    case 'out_of_stock':
        $where_conditions[] = "CAST(p.quantity AS UNSIGNED) = 0";
        break;
    case 'in_stock':
        $where_conditions[] = "CAST(p.quantity AS UNSIGNED) > 10";
        break;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Sort order
$order_clause = "";
switch ($sort_by) {
    case 'quantity':
        $order_clause = "ORDER BY CAST(p.quantity AS UNSIGNED) DESC";
        break;
    case 'value':
        $order_clause = "ORDER BY (CAST(p.quantity AS UNSIGNED) * p.buy_price) DESC";
        break;
    case 'category':
        $order_clause = "ORDER BY c.name ASC, p.name ASC";
        break;
    default:
        $order_clause = "ORDER BY p.name ASC";
        break;
}

// Get stock data
$stock_sql = "SELECT 
                p.id,
                p.name as product_name,
                p.quantity,
                p.buy_price,
                p.sale_price,
                p.images,
                p.primary_image_index,
                p.date as date_added,
                c.name as category_name,
                s.name as supplier_name,
                (CAST(p.quantity AS UNSIGNED) * p.buy_price) as inventory_value,
                (CAST(p.quantity AS UNSIGNED) * p.sale_price) as potential_revenue,
                ((p.sale_price - p.buy_price) / p.sale_price * 100) as profit_margin,
                CASE 
                    WHEN CAST(p.quantity AS UNSIGNED) = 0 THEN 'Out of Stock'
                    WHEN CAST(p.quantity AS UNSIGNED) <= 10 THEN 'Low Stock'
                    ELSE 'In Stock'
                END as stock_status
              FROM products p
              LEFT JOIN categories c ON p.categorie_id = c.id
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              {$where_clause}
              {$order_clause}";

if (!empty($params)) {
    $stmt = $db->con->prepare($stock_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $stock_result = $stmt->get_result();
} else {
    $stock_result = $db->query($stock_sql);
}

// Get summary statistics
$summary_sql = "SELECT 
                  COUNT(*) as total_products,
                  SUM(CAST(p.quantity AS UNSIGNED)) as total_items,
                  SUM(CAST(p.quantity AS UNSIGNED) * p.buy_price) as total_inventory_value,
                  SUM(CAST(p.quantity AS UNSIGNED) * p.sale_price) as total_potential_revenue,
                  SUM(CASE WHEN CAST(p.quantity AS UNSIGNED) = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                  SUM(CASE WHEN CAST(p.quantity AS UNSIGNED) <= 10 AND CAST(p.quantity AS UNSIGNED) > 0 THEN 1 ELSE 0 END) as low_stock_count,
                  SUM(CASE WHEN CAST(p.quantity AS UNSIGNED) > 10 THEN 1 ELSE 0 END) as in_stock_count,
                  AVG(CAST(p.quantity AS UNSIGNED)) as avg_stock_level
                FROM products p
                LEFT JOIN categories c ON p.categorie_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                {$where_clause}";

if (!empty($params)) {
    $stmt = $db->con->prepare($summary_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
} else {
    $summary = $db->query($summary_sql)->fetch_assoc();
}


// Get categories and suppliers for filters
$categories = find_all('categories');
$suppliers = find_all('suppliers');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<!-- Filter Form -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>
                    <span class="glyphicon glyphicon-filter"></span>
                    <span>Stock Report Filters</span>
                </strong>
            </div>
            <div class="panel-body">
                <form method="GET" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Category</label>
                                <select class="form-control" name="category">
                                    <option value="0">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Supplier</label>
                                <select class="form-control" name="supplier">
                                    <option value="0">All Suppliers</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>" 
                                                <?php echo $supplier_filter == $supplier['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Stock Status</label>
                                <select class="form-control" name="stock_status">
                                    <option value="all" <?php echo $stock_status === 'all' ? 'selected' : ''; ?>>All Stock</option>
                                    <option value="in_stock" <?php echo $stock_status === 'in_stock' ? 'selected' : ''; ?>>In Stock (>10)</option>
                                    <option value="low_stock" <?php echo $stock_status === 'low_stock' ? 'selected' : ''; ?>>Low Stock (≤10)</option>
                                    <option value="out_of_stock" <?php echo $stock_status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock (0)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Sort By</label>
                                <select class="form-control" name="sort_by">
                                    <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Product Name</option>
                                    <option value="quantity" <?php echo $sort_by === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
                                    <option value="value" <?php echo $sort_by === 'value' ? 'selected' : ''; ?>>Inventory Value</option>
                                    <option value="category" <?php echo $sort_by === 'category' ? 'selected' : ''; ?>>Category</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="glyphicon glyphicon-search"></i> Filter
                                    </button>
                                    <a href="stock_report.php" class="btn btn-default">Reset</a>
                                    <a href="export_stock_report.php?format=csv<?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $supplier_filter > 0 ? '&supplier=' . $supplier_filter : ''; ?><?php echo $stock_status !== 'all' ? '&stock_status=' . $stock_status : ''; ?><?php echo $sort_by !== 'name' ? '&sort_by=' . $sort_by : ''; ?>" class="btn btn-success">
                                        <i class="glyphicon glyphicon-download"></i> Export CSV
                                    </a>
                                    <a href="export_stock_report.php?format=pdf<?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $supplier_filter > 0 ? '&supplier=' . $supplier_filter : ''; ?><?php echo $stock_status !== 'all' ? '&stock_status=' . $stock_status : ''; ?><?php echo $sort_by !== 'name' ? '&sort_by=' . $sort_by : ''; ?>" class="btn btn-danger">
                                        <i class="glyphicon glyphicon-file"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced KPI Cards -->
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
        <div class="kpi-label">Total Products</div>
        <div class="kpi-value"><?php echo number_format($summary['total_products']); ?></div>
        <div class="kpi-change">Product count</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #43e97b;">
        <div class="kpi-label">Total Items in Stock</div>
        <div class="kpi-value"><?php echo number_format($summary['total_items']); ?></div>
        <div class="kpi-change">Units available</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #fa709a;">
        <div class="kpi-label">Inventory Value</div>
        <div class="kpi-value">LKR <?php echo number_format($summary['total_inventory_value'], 0); ?></div>
        <div class="kpi-change">Total worth</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #a8edea;">
        <div class="kpi-label">Stock Alerts</div>
        <div class="kpi-value"><?php echo number_format($summary['low_stock_count'] + $summary['out_of_stock_count']); ?></div>
        <div class="kpi-change">Need attention</div>
    </div>
</div>

<!-- Stock Status Breakdown -->
<div class="kpi-grid">
    <div class="kpi-card" style="border-left-color: #43e97b;">
        <div class="kpi-label">In Stock Products</div>
        <div class="kpi-value"><?php echo number_format($summary['in_stock_count']); ?></div>
        <div class="kpi-change">Well stocked</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #fa709a;">
        <div class="kpi-label">Low Stock Products</div>
        <div class="kpi-value"><?php echo number_format($summary['low_stock_count']); ?></div>
        <div class="kpi-change">Need reorder</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #ff6b6b;">
        <div class="kpi-label">Out of Stock Products</div>
        <div class="kpi-value"><?php echo number_format($summary['out_of_stock_count']); ?></div>
        <div class="kpi-change">Critical</div>
    </div>
</div>

<!-- Stock Details Table -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <strong>
                    <span class="glyphicon glyphicon-list"></span>
                    <span>Stock Details</span>
                </strong>
                <div class="pull-right">
                    <span class="badge">Total: <?php echo number_format($summary['total_products']); ?> products</span>
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Current Stock</th>
                            <th>Buy Price</th>
                            <th>Sale Price</th>
                            <th>Inventory Value</th>
                            <th>Potential Revenue</th>
                            <th>Profit Margin</th>
                            <th>Status</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $stock_result->fetch_assoc()): ?>
                            <tr class="<?php 
                                if ($product['stock_status'] == 'Out of Stock') echo 'danger';
                                elseif ($product['stock_status'] == 'Low Stock') echo 'warning';
                                else echo '';
                            ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <?php 
                                    $has_images = false;
                                    if (!empty($product['images'])) {
                                        $images = json_decode($product['images'], true);
                                        $has_images = is_array($images) && count($images) > 0;
                                    }
                                    if ($has_images): ?>
                                        <br><small class="text-muted">Has <?php echo count($images); ?> image(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['supplier_name'] ?: 'N/A'); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php 
                                        if ($product['quantity'] == 0) echo 'badge-danger';
                                        elseif ($product['quantity'] <= 10) echo 'badge-warning';
                                        else echo 'badge-success';
                                    ?>">
                                        <?php echo number_format($product['quantity']); ?>
                                    </span>
                                </td>
                                <td class="text-right">LKR <?php echo number_format($product['buy_price'], 2); ?></td>
                                <td class="text-right">LKR <?php echo number_format($product['sale_price'], 2); ?></td>
                                <td class="text-right">LKR <?php echo number_format($product['inventory_value'], 2); ?></td>
                                <td class="text-right">LKR <?php echo number_format($product['potential_revenue'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($product['profit_margin'], 1); ?>%</td>
                                <td>
                                    <span class="label <?php 
                                        if ($product['stock_status'] == 'Out of Stock') echo 'label-danger';
                                        elseif ($product['stock_status'] == 'Low Stock') echo 'label-warning';
                                        else echo 'label-success';
                                    ?>">
                                        <?php echo $product['stock_status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($product['date_added'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<?php include_once('layouts/footer.php'); ?>
