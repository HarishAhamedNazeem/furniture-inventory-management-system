<?php
  // Set secure cookie parameters before session starts
  if (session_status() === PHP_SESSION_NONE) {
      ini_set('session.cookie_httponly', 1);
      ini_set('session.cookie_secure', 1);
      ini_set('session.cookie_samesite', 'Strict');
  }

  $page_title = 'Admin Dashboard';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(1);
?>
<?php
// Basic counts
$c_categorie     = count_by_id('categories');
$c_product       = count_by_id('products');
$c_user          = count_by_id('users');
$c_supplier      = count_by_id('suppliers');
$recent_products = find_recent_product_added('5');

// Enhanced KPI calculations
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

// Check if physical_sales table exists
$check_sales_table_sql = "SHOW TABLES LIKE 'physical_sales'";
$sales_table_check = $db->query($check_sales_table_sql);

// Check if customers table exists
$check_customers_table_sql = "SHOW TABLES LIKE 'customers'";
$customers_table_check = $db->query($check_customers_table_sql);

// Check if orders table exists
$check_orders_table_sql = "SHOW TABLES LIKE 'orders'";
$orders_table_check = $db->query($check_orders_table_sql);

// Initialize KPI variables
$today_revenue = 0;
$today_orders = 0;
$monthly_revenue = 0;
$monthly_orders = 0;
$total_customers = 0;
$low_stock_products = 0;
$top_products = [];
$sales_by_category = [];
$recent_orders = [];
$inventory_value = 0;

// Calculate today's revenue and orders
if ($sales_table_check->num_rows > 0) {
    // Physical sales
    $today_sales_sql = "SELECT 
                        COALESCE(SUM(total_amount), 0) as revenue,
                        COUNT(*) as orders
                        FROM physical_sales 
                        WHERE DATE(created_at) = ? AND status = 'completed'";
    $stmt = $db->con->prepare($today_sales_sql);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $today_data = $result->fetch_assoc();
    $today_revenue += $today_data['revenue'];
    $today_orders += $today_data['orders'];
    
    // Monthly sales
    $monthly_sales_sql = "SELECT 
                          COALESCE(SUM(total_amount), 0) as revenue,
                          COUNT(*) as orders
                          FROM physical_sales 
                          WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'completed'";
    $stmt = $db->con->prepare($monthly_sales_sql);
    $stmt->bind_param('s', $this_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_data = $result->fetch_assoc();
    $monthly_revenue += $monthly_data['revenue'];
    $monthly_orders += $monthly_data['orders'];
}

// Add online orders if table exists
if ($orders_table_check->num_rows > 0) {
    $today_orders_sql = "SELECT 
                         COALESCE(SUM(total_amount), 0) as revenue,
                         COUNT(*) as orders
                         FROM orders 
                         WHERE DATE(created_at) = ? AND status != 'cancelled'";
    $stmt = $db->con->prepare($today_orders_sql);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $today_data = $result->fetch_assoc();
    $today_revenue += $today_data['revenue'];
    $today_orders += $today_data['orders'];
    
    $monthly_orders_sql = "SELECT 
                           COALESCE(SUM(total_amount), 0) as revenue,
                           COUNT(*) as orders
                           FROM orders 
                           WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'";
    $stmt = $db->con->prepare($monthly_orders_sql);
    $stmt->bind_param('s', $this_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_data = $result->fetch_assoc();
    $monthly_revenue += $monthly_data['revenue'];
    $monthly_orders += $monthly_data['orders'];
}

// Get total customers
if ($customers_table_check->num_rows > 0) {
    $customers_sql = "SELECT COUNT(*) as total FROM customers";
    $result = $db->query($customers_sql);
    $customers_data = $result->fetch_assoc();
    $total_customers = $customers_data['total'];
}

// Calculate low stock products (assuming threshold of 10)
$low_stock_sql = "SELECT COUNT(*) as low_stock FROM products WHERE CAST(quantity AS UNSIGNED) <= 10";
$result = $db->query($low_stock_sql);
$low_stock_data = $result->fetch_assoc();
$low_stock_products = $low_stock_data['low_stock'];

// Calculate inventory value
$inventory_sql = "SELECT COALESCE(SUM(CAST(quantity AS UNSIGNED) * buy_price), 0) as total_value FROM products WHERE buy_price IS NOT NULL";
$result = $db->query($inventory_sql);
$inventory_data = $result->fetch_assoc();
$inventory_value = $inventory_data['total_value'];

// Get top 5 products by quantity sold (if sales items table exists)
$check_sales_items_sql = "SHOW TABLES LIKE 'physical_sales_items'";
$sales_items_check = $db->query($check_sales_items_sql);

if ($sales_items_check->num_rows > 0) {
    $top_products_sql = "SELECT 
                        p.name,
                        SUM(psi.quantity) as total_sold,
                        p.sale_price
                        FROM physical_sales_items psi
                        JOIN products p ON psi.product_id = p.id
                        JOIN physical_sales ps ON psi.sale_id = ps.id
                        WHERE ps.status = 'completed'
                        GROUP BY p.id, p.name, p.sale_price
                        ORDER BY total_sold DESC
                        LIMIT 5";
    $result = $db->query($top_products_sql);
    $top_products = $result->fetch_all(MYSQLI_ASSOC);
}

// Get sales by category
$category_sales_sql = "SELECT 
                       c.name as category_name,
                       COUNT(p.id) as product_count,
                       COALESCE(SUM(CAST(p.quantity AS UNSIGNED) * p.sale_price), 0) as category_value
                       FROM categories c
                       LEFT JOIN products p ON c.id = p.categorie_id
                       GROUP BY c.id, c.name
                       ORDER BY category_value DESC";
$result = $db->query($category_sales_sql);
$sales_by_category = $result->fetch_all(MYSQLI_ASSOC);

// Get recent orders (last 5)
if ($orders_table_check->num_rows > 0) {
    $recent_orders_sql = "SELECT 
                          o.id,
                          o.total_amount,
                          o.status,
                          o.created_at,
                          c.name as customer_name
                          FROM orders o
                          LEFT JOIN customers c ON o.customer_id = c.id
                          ORDER BY o.created_at DESC
                          LIMIT 5";
    $result = $db->query($recent_orders_sql);
    $recent_orders = $result->fetch_all(MYSQLI_ASSOC);
}

// Additional KPI Calculations
$profit_margin = 0;
$average_order_value = 0;
$customer_acquisition_rate = 0;
$inventory_turnover = 0;
$supplier_performance = [];
$monthly_growth_rate = 0;
$payment_method_distribution = [];
$hourly_sales_data = [];
$weekly_sales_trend = [];
$product_profitability = [];

// Calculate Profit Margin
$profit_sql = "SELECT 
               COALESCE(SUM(CAST(quantity AS UNSIGNED) * (sale_price - COALESCE(buy_price, 0))), 0) as total_profit,
               COALESCE(SUM(CAST(quantity AS UNSIGNED) * sale_price), 0) as total_revenue
               FROM products WHERE buy_price IS NOT NULL AND buy_price > 0";
$result = $db->query($profit_sql);
$profit_data = $result->fetch_assoc();
if ($profit_data['total_revenue'] > 0) {
    $profit_margin = ($profit_data['total_profit'] / $profit_data['total_revenue']) * 100;
}

// Calculate Average Order Value
if ($today_orders > 0) {
    $average_order_value = $today_revenue / $today_orders;
}

// Calculate Customer Acquisition Rate (new customers this month)
if ($customers_table_check->num_rows > 0) {
    $new_customers_sql = "SELECT COUNT(*) as new_customers FROM customers WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
    $stmt = $db->con->prepare($new_customers_sql);
    $stmt->bind_param('s', $this_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_customers_data = $result->fetch_assoc();
    $customer_acquisition_rate = $new_customers_data['new_customers'];
}

// Calculate Inventory Turnover (simplified)
$inventory_turnover_sql = "SELECT 
                          COALESCE(SUM(CAST(quantity AS UNSIGNED) * buy_price), 0) as avg_inventory,
                          COALESCE(SUM(CAST(quantity AS UNSIGNED) * sale_price), 0) as cost_of_goods_sold
                          FROM products WHERE buy_price IS NOT NULL";
$result = $db->query($inventory_turnover_sql);
$turnover_data = $result->fetch_assoc();
if ($turnover_data['avg_inventory'] > 0) {
    $inventory_turnover = $turnover_data['cost_of_goods_sold'] / $turnover_data['avg_inventory'];
}

// Get Supplier Performance
$supplier_performance_sql = "SELECT 
                            s.name as supplier_name,
                            COUNT(p.id) as product_count,
                            COALESCE(SUM(CAST(p.quantity AS UNSIGNED) * p.buy_price), 0) as total_value,
                            COALESCE(AVG(p.buy_price), 0) as avg_cost
                            FROM suppliers s
                            LEFT JOIN products p ON s.id = p.supplier_id
                            GROUP BY s.id, s.name
                            ORDER BY total_value DESC
                            LIMIT 5";
$result = $db->query($supplier_performance_sql);
$supplier_performance = $result->fetch_all(MYSQLI_ASSOC);

// Calculate Monthly Growth Rate (comparing with last month)
$last_month_revenue = 0;
if ($sales_table_check->num_rows > 0) {
    $last_month_sales_sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM physical_sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'completed'";
    $stmt = $db->con->prepare($last_month_sales_sql);
    $stmt->bind_param('s', $last_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_month_data = $result->fetch_assoc();
    $last_month_revenue = $last_month_data['revenue'];
}

if ($orders_table_check->num_rows > 0) {
    $last_month_orders_sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'";
    $stmt = $db->con->prepare($last_month_orders_sql);
    $stmt->bind_param('s', $last_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_month_data = $result->fetch_assoc();
    $last_month_revenue += $last_month_data['revenue'];
}

if ($last_month_revenue > 0) {
    $monthly_growth_rate = (($monthly_revenue - $last_month_revenue) / $last_month_revenue) * 100;
}

// Get Payment Method Distribution
if ($sales_table_check->num_rows > 0) {
    $payment_methods_sql = "SELECT 
                           payment_method,
                           COUNT(*) as count,
                           SUM(total_amount) as total_amount
                           FROM physical_sales 
                           WHERE status = 'completed'
                           GROUP BY payment_method
                           ORDER BY total_amount DESC";
    $result = $db->query($payment_methods_sql);
    $payment_method_distribution = $result->fetch_all(MYSQLI_ASSOC);
}

// Get Hourly Sales Data (last 7 days)
if ($sales_table_check->num_rows > 0) {
    $hourly_sales_sql = "SELECT 
                        HOUR(created_at) as hour,
                        COUNT(*) as sales_count,
                        SUM(total_amount) as total_amount
                        FROM physical_sales 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'completed'
                        GROUP BY HOUR(created_at)
                        ORDER BY hour";
    $result = $db->query($hourly_sales_sql);
    $hourly_sales_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Get Weekly Sales Trend (last 8 weeks)
if ($sales_table_check->num_rows > 0) {
    $weekly_sales_sql = "SELECT 
                        WEEK(created_at) as week_number,
                        YEAR(created_at) as year,
                        COUNT(*) as sales_count,
                        SUM(total_amount) as total_amount
                        FROM physical_sales 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK) AND status = 'completed'
                        GROUP BY YEAR(created_at), WEEK(created_at)
                        ORDER BY year, week_number";
    $result = $db->query($weekly_sales_sql);
    $weekly_sales_trend = $result->fetch_all(MYSQLI_ASSOC);
}

// Get Product Profitability Analysis
$product_profitability_sql = "SELECT 
                             p.name,
                             p.sale_price,
                             p.buy_price,
                             CAST(p.quantity AS UNSIGNED) as quantity,
                             (p.sale_price - COALESCE(p.buy_price, 0)) as profit_per_unit,
                             (CAST(p.quantity AS UNSIGNED) * (p.sale_price - COALESCE(p.buy_price, 0))) as total_profit
                             FROM products p
                             WHERE p.buy_price IS NOT NULL AND p.buy_price > 0
                             ORDER BY total_profit DESC
                             LIMIT 10";
$result = $db->query($product_profitability_sql);
$product_profitability = $result->fetch_all(MYSQLI_ASSOC);

// Calculate ROI (Return on Investment)
$roi = 0;
if ($inventory_value > 0) {
    $total_investment = $inventory_value; // Total investment in inventory
    $total_profit = $profit_data['total_profit']; // Total profit from sales
    $roi = ($total_profit / $total_investment) * 100;
}

// Calculate Physical vs Online Sales Profits Comparison
$physical_sales_profit = 0;
$online_sales_profit = 0;
$physical_vs_online_data = [];

// Calculate Physical Sales Profit (last 30 days)
if ($sales_table_check->num_rows > 0) {
    $physical_profit_sql = "SELECT 
                           COALESCE(SUM(psi.quantity * (p.sale_price - COALESCE(p.buy_price, 0))), 0) as total_profit
                           FROM physical_sales ps
                           JOIN physical_sales_items psi ON ps.id = psi.sale_id
                           JOIN products p ON psi.product_id = p.id
                           WHERE ps.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                           AND ps.status = 'completed'
                           AND p.buy_price IS NOT NULL AND p.buy_price > 0";
    $result = $db->query($physical_profit_sql);
    $physical_data = $result->fetch_assoc();
    $physical_sales_profit = $physical_data['total_profit'];
}

// Calculate Online Sales Profit (last 30 days)
if ($orders_table_check->num_rows > 0) {
    $online_profit_sql = "SELECT 
                         COALESCE(SUM(oi.quantity * (oi.price - COALESCE(p.buy_price, 0))), 0) as total_profit
                         FROM orders o
                         JOIN order_items oi ON o.id = oi.order_id
                         JOIN products p ON oi.product_id = p.id
                         WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                         AND o.status IN ('delivered', 'completed')
                         AND p.buy_price IS NOT NULL AND p.buy_price > 0";
    $result = $db->query($online_profit_sql);
    $online_data = $result->fetch_assoc();
    $online_sales_profit = $online_data['total_profit'];
}

// Prepare data for the comparison chart
$physical_vs_online_data = [
    ['type' => 'Physical Sales', 'profit' => $physical_sales_profit],
    ['type' => 'Online Sales', 'profit' => $online_sales_profit]
];

// Debug information (commented out for production)
// error_log("Physical Sales Profit: " . $physical_sales_profit);
// error_log("Online Sales Profit: " . $online_sales_profit);
// error_log("Physical vs Online Data: " . json_encode($physical_vs_online_data));

?>
<?php include_once('layouts/header.php'); ?>

<!-- Enhanced Dashboard Styles -->
<style>
.dashboard-container {
  min-height: 100vh;
  padding: 20px;
}

.dashboard-header {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 15px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
}

.dashboard-title {
  color: #2d3748;
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 10px;
  text-align: center;
}

.dashboard-subtitle {
  color: #718096;
  font-size: 1.1rem;
  text-align: center;
  margin-bottom: 0;
}

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


.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 18px;
  margin-bottom: 25px;
}

.chart-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.chart-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 15px;
  text-align: center;
}

.chart-container {
  position: relative;
  height: 280px;
}

.recent-activity {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.activity-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 14px;
  color: white;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 3px;
  font-size: 0.9rem;
}

.activity-time {
  font-size: 0.8rem;
  color: #718096;
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 10px;
  }
  
  .kpi-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .charts-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .dashboard-title {
    font-size: 2rem;
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
  
  .chart-card {
    padding: 15px;
  }
  
  .chart-container {
    height: 250px;
  }
}
</style>

<div class="dashboard-container">
  <div class="dashboard-header">
    <h1 class="dashboard-title">Admin Dashboard</h1>
    <p class="dashboard-subtitle">Welcome back! Here's your business overview for <?php echo date('F j, Y'); ?></p>
  </div>

  <div class="row">
    <div class="col-md-12">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <!-- Enhanced KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card" style="border-left-color: #667eea;">
      <div class="kpi-value">LKR <?php echo number_format($today_revenue, 2); ?></div>
      <div class="kpi-label">Today's Revenue</div>
      <div class="kpi-change positive">+<?php echo $today_orders; ?> orders</div>
    </div>




    <div class="kpi-card" style="border-left-color: #fa709a;">
      <div class="kpi-value">LKR <?php echo number_format($inventory_value, 2); ?></div>
      <div class="kpi-label">Inventory Value</div>
      <div class="kpi-change positive">Total worth</div>
    </div>

    <div class="kpi-card" style="border-left-color: #a8edea;">
      <div class="kpi-value">LKR <?php echo number_format($monthly_revenue, 2); ?></div>
      <div class="kpi-label">Monthly Revenue</div>
      <div class="kpi-change positive"><?php echo $monthly_orders; ?> orders</div>
    </div>

    <!-- Additional KPI Cards -->
    <div class="kpi-card" style="border-left-color: #ff9a9e;">
      <div class="kpi-value"><?php echo number_format($profit_margin, 1); ?>%</div>
      <div class="kpi-label">Profit Margin</div>
      <div class="kpi-change <?php echo $profit_margin > 20 ? 'positive' : 'negative'; ?>">Margin</div>
    </div>



    <div class="kpi-card" style="border-left-color: #d299c2;">
      <div class="kpi-value"><?php echo number_format($inventory_turnover, 1); ?>x</div>
      <div class="kpi-label">Inventory Turnover</div>
      <div class="kpi-change <?php echo $inventory_turnover > 2 ? 'positive' : 'negative'; ?>">Rate</div>
    </div>

    <div class="kpi-card" style="border-left-color: #89f7fe;">
      <div class="kpi-value"><?php echo number_format($monthly_growth_rate, 1); ?>%</div>
      <div class="kpi-label">Growth Rate</div>
      <div class="kpi-change <?php echo $monthly_growth_rate > 0 ? 'positive' : 'negative'; ?>">vs last month</div>
    </div>

    <div class="kpi-card" style="border-left-color: #667eea;">
      <div class="kpi-value"><?php echo number_format($roi, 1); ?>%</div>
      <div class="kpi-label">ROI</div>
      <div class="kpi-change <?php echo $roi > 20 ? 'positive' : ($roi > 10 ? 'warning' : 'negative'); ?>">Return on Investment</div>
    </div>
  </div>

  
  <!-- Interactive Charts Section -->
  <div class="charts-grid">
    <!-- Sales by Category Chart -->
    <div class="chart-card">
      <h3 class="chart-title">Sales by Category</h3>
      <div class="chart-container">
        <canvas id="categoryChart"></canvas>
      </div>
    </div>


    <!-- Revenue Trend Chart -->
    <div class="chart-card">
      <h3 class="chart-title">Revenue Overview</h3>
      <div class="chart-container">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>




    <!-- Weekly Sales Trend Chart -->
    <div class="chart-card">
      <h3 class="chart-title">Weekly Sales Trend</h3>
      <div class="chart-container">
        <canvas id="weeklyTrendChart"></canvas>
      </div>
    </div>

    <!-- Supplier Performance Chart -->
    <div class="chart-card">
      <h3 class="chart-title">Supplier Performance</h3>
      <div class="chart-container">
        <canvas id="supplierChart"></canvas>
      </div>
    </div>

    <!-- Product Profitability Chart -->
    <div class="chart-card">
      <h3 class="chart-title">Product Profitability</h3>
      <div class="chart-container">
        <canvas id="profitabilityChart"></canvas>
      </div>
    </div>

    <!-- Physical vs Online Sales Comparison Chart -->
    <div class="chart-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 12px; padding: 20px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
      <h3 class="chart-title" style="font-size: 1.2rem; font-weight: 600; color: #2d3748; margin-bottom: 15px; text-align: center;">Physical vs Online Sales Profits (Last 30 Days)</h3>
      <div class="chart-container" style="position: relative; height: 280px;">
        <canvas id="physicalVsOnlineChart" style="max-width: 100%; height: 100%;"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>


<script>
// Chart.js configuration
Chart.defaults.font.family = "'Segoe UI', 'Roboto', 'Arial', sans-serif";
Chart.defaults.color = '#718096';

// Sales by Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryData = <?php echo json_encode($sales_by_category); ?>;
const categoryLabels = categoryData.map(item => item.category_name);
const categoryValues = categoryData.map(item => parseFloat(item.category_value));

const categoryChart = new Chart(categoryCtx, {
  type: 'doughnut',
  data: {
    labels: categoryLabels,
    datasets: [{
      data: categoryValues,
      backgroundColor: [
        '#667eea',
        '#764ba2',
        '#f093fb',
        '#f5576c',
        '#4facfe',
        '#00f2fe',
        '#43e97b',
        '#38f9d7'
      ],
      borderWidth: 0,
      hoverOffset: 10
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 20,
          usePointStyle: true
        }
      }
    }
  }
});

// Store chart reference for real-time updates
document.getElementById('categoryChart').chart = categoryChart;


// Revenue Chart (Sample data - you can enhance this with real data)
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueData = {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
  datasets: [{
    label: 'Revenue',
    data: [<?php echo $monthly_revenue * 0.8; ?>, <?php echo $monthly_revenue * 0.9; ?>, <?php echo $monthly_revenue; ?>, <?php echo $monthly_revenue * 1.1; ?>, <?php echo $monthly_revenue * 1.2; ?>, <?php echo $monthly_revenue * 1.3; ?>],
    borderColor: 'rgba(102, 126, 234, 1)',
    backgroundColor: 'rgba(102, 126, 234, 0.1)',
    borderWidth: 3,
    fill: true,
    tension: 0.4,
    pointBackgroundColor: 'rgba(102, 126, 234, 1)',
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 6
  }]
};

new Chart(revenueCtx, {
  type: 'line',
  data: revenueData,
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.1)'
        },
        ticks: {
          callback: function(value) {
            return 'LKR ' + value.toLocaleString();
          }
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    }
  }
});




// Weekly Sales Trend Chart
const weeklyTrendCtx = document.getElementById('weeklyTrendChart').getContext('2d');
const weeklyTrendData = <?php echo json_encode($weekly_sales_trend); ?>;
const weeklyLabels = weeklyTrendData.map(item => `Week ${item.week_number}`);
const weeklyValues = weeklyTrendData.map(item => parseFloat(item.total_amount));

const weeklyTrendChart = new Chart(weeklyTrendCtx, {
  type: 'bar',
  data: {
    labels: weeklyLabels,
    datasets: [{
      label: 'Weekly Revenue',
      data: weeklyValues,
      backgroundColor: 'rgba(102, 126, 234, 0.8)',
      borderColor: 'rgba(102, 126, 234, 1)',
      borderWidth: 1,
      borderRadius: 8,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.1)'
        },
        ticks: {
          callback: function(value) {
            return 'LKR ' + value.toLocaleString();
          }
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    }
  }
});

// Supplier Performance Chart
const supplierCtx = document.getElementById('supplierChart').getContext('2d');
const supplierData = <?php echo json_encode($supplier_performance); ?>;
const supplierLabels = supplierData.map(item => item.supplier_name.length > 15 ? item.supplier_name.substring(0, 15) + '...' : item.supplier_name);
const supplierValues = supplierData.map(item => parseFloat(item.total_value));

const supplierChart = new Chart(supplierCtx, {
  type: 'doughnut',
  data: {
    labels: supplierLabels,
    datasets: [{
      data: supplierValues,
      backgroundColor: [
        '#667eea',
        '#764ba2',
        '#f093fb',
        '#f5576c',
        '#4facfe'
      ],
      borderWidth: 0,
      hoverOffset: 10
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 20,
          usePointStyle: true
        }
      }
    }
  }
});

// Product Profitability Chart
const profitabilityCtx = document.getElementById('profitabilityChart').getContext('2d');
const profitabilityData = <?php echo json_encode($product_profitability); ?>;
const profitLabels = profitabilityData.map(item => item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name);
const profitValues = profitabilityData.map(item => parseFloat(item.total_profit));

const profitabilityChart = new Chart(profitabilityCtx, {
  type: 'bar',
  data: {
    labels: profitLabels,
    datasets: [{
      label: 'Total Profit',
      data: profitValues,
      backgroundColor: 'rgba(67, 233, 123, 0.8)',
      borderColor: 'rgba(67, 233, 123, 1)',
      borderWidth: 1,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y', // This makes the bar chart horizontal
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      x: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.1)'
        },
        ticks: {
          callback: function(value) {
            return 'LKR ' + value.toLocaleString();
          }
        }
      },
      y: {
        grid: {
          display: false
        }
      }
    }
  }
});

// Physical vs Online Sales Comparison Chart
try {
  console.log('Chart.js available:', typeof Chart !== 'undefined');
  console.log('Chart.js version:', Chart ? Chart.version : 'Not loaded');
  
  const physicalVsOnlineCtx = document.getElementById('physicalVsOnlineChart');
  console.log('Canvas element found:', !!physicalVsOnlineCtx);
  
  if (physicalVsOnlineCtx) {
    const physicalVsOnlineData = <?php echo json_encode($physical_vs_online_data); ?>;
    console.log('Physical vs Online Data:', physicalVsOnlineData);
    
    const comparisonLabels = physicalVsOnlineData.map(item => item.type);
    const comparisonValues = physicalVsOnlineData.map(item => parseFloat(item.profit));
    
    console.log('Chart Labels:', comparisonLabels);
    console.log('Chart Values:', comparisonValues);
    
    // Check if we have valid data
    if (comparisonValues.some(value => value > 0)) {
      const physicalVsOnlineChart = new Chart(physicalVsOnlineCtx, {
        type: 'doughnut',
        data: {
          labels: comparisonLabels,
          datasets: [{
            data: comparisonValues,
            backgroundColor: [
              'rgba(102, 126, 234, 0.8)', // Physical sales - blue
              'rgba(250, 112, 154, 0.8)'  // Online sales - pink
            ],
            borderColor: [
              'rgba(102, 126, 234, 1)',
              'rgba(250, 112, 154, 1)'
            ],
            borderWidth: 2,
            hoverOffset: 15
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true,
                font: {
                  size: 12
                }
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.parsed;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                  return `${label}: LKR ${value.toLocaleString()} (${percentage}%)`;
                }
              }
            }
          },
          cutout: '60%', // Makes it a donut chart
          animation: {
            animateRotate: true,
            animateScale: true
          }
        }
      });
      console.log('Physical vs Online Chart created successfully');
    } else {
      // Show a message when no data is available
      physicalVsOnlineCtx.parentElement.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;"><i class="glyphicon glyphicon-info-sign" style="font-size: 3rem; margin-bottom: 15px;"></i><p>No sales data available for comparison</p><p>Physical: LKR ' + comparisonValues[0] + '</p><p>Online: LKR ' + comparisonValues[1] + '</p></div>';
    }
  } else {
    console.error('Canvas element with ID "physicalVsOnlineChart" not found');
  }
} catch (error) {
  console.error('Error creating Physical vs Online Chart:', error);
}
</script>

<?php include_once('layouts/footer.php'); ?>
