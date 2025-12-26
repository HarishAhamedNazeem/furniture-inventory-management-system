<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once('../includes/load.php');
page_require_level(1);


$today = date('Y-m-d');
$this_month = date('Y-m');


$check_sales_table_sql = "SHOW TABLES LIKE 'physical_sales'";
$sales_table_check = $db->query($check_sales_table_sql);

$check_customers_table_sql = "SHOW TABLES LIKE 'customers'";
$customers_table_check = $db->query($check_customers_table_sql);

$check_orders_table_sql = "SHOW TABLES LIKE 'orders'";
$orders_table_check = $db->query($check_orders_table_sql);


$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

try {
    // Today's Revenue and Orders
    $today_revenue = 0;
    $today_orders = 0;
    
    if ($sales_table_check->num_rows > 0) {
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
    }
    
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
    }
    
    // Monthly Revenue and Orders
    $monthly_revenue = 0;
    $monthly_orders = 0;
    
    if ($sales_table_check->num_rows > 0) {
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
    
    if ($orders_table_check->num_rows > 0) {
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
    
    // Total Customers
    $total_customers = 0;
    if ($customers_table_check->num_rows > 0) {
        $customers_sql = "SELECT COUNT(*) as total FROM customers";
        $result = $db->query($customers_sql);
        $customers_data = $result->fetch_assoc();
        $total_customers = $customers_data['total'];
    }
    
    // Low Stock Products
    $low_stock_sql = "SELECT COUNT(*) as low_stock FROM products WHERE CAST(quantity AS UNSIGNED) <= 10";
    $result = $db->query($low_stock_sql);
    $low_stock_data = $result->fetch_assoc();
    $low_stock_products = $low_stock_data['low_stock'];
    
    // Inventory Value
    $inventory_sql = "SELECT COALESCE(SUM(CAST(quantity AS UNSIGNED) * buy_price), 0) as total_value FROM products WHERE buy_price IS NOT NULL";
    $result = $db->query($inventory_sql);
    $inventory_data = $result->fetch_assoc();
    $inventory_value = $inventory_data['total_value'];
    
    // Total Products
    $c_product = count_by_id('products');
    
    // Sales by Category
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
    
    // Top Products
    $top_products = [];
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
    
    // Recent Orders
    $recent_orders = [];
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
    $monthly_growth_rate = 0;
    $payment_method_distribution = [];
    $hourly_sales_data = [];
    $weekly_sales_trend = [];
    $supplier_performance = [];
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

    // Calculate Customer Acquisition Rate
    if ($customers_table_check->num_rows > 0) {
        $new_customers_sql = "SELECT COUNT(*) as new_customers FROM customers WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
        $stmt = $db->con->prepare($new_customers_sql);
        $stmt->bind_param('s', $this_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_customers_data = $result->fetch_assoc();
        $customer_acquisition_rate = $new_customers_data['new_customers'];
    }

    // Calculate Inventory Turnover
    $inventory_turnover_sql = "SELECT 
                              COALESCE(SUM(CAST(quantity AS UNSIGNED) * buy_price), 0) as avg_inventory,
                              COALESCE(SUM(CAST(quantity AS UNSIGNED) * sale_price), 0) as cost_of_goods_sold
                              FROM products WHERE buy_price IS NOT NULL";
    $result = $db->query($inventory_turnover_sql);
    $turnover_data = $result->fetch_assoc();
    if ($turnover_data['avg_inventory'] > 0) {
        $inventory_turnover = $turnover_data['cost_of_goods_sold'] / $turnover_data['avg_inventory'];
    }

    // Calculate Monthly Growth Rate
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

    // Get Hourly Sales Data
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

    // Get Weekly Sales Trend
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

    // Get Product Profitability
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

    // Compile response data
    $response['data'] = [
        'today_revenue' => $today_revenue,
        'today_orders' => $today_orders,
        'monthly_revenue' => $monthly_revenue,
        'monthly_orders' => $monthly_orders,
        'total_customers' => $total_customers,
        'low_stock_products' => $low_stock_products,
        'inventory_value' => $inventory_value,
        'total_products' => $c_product['total'],
        'sales_by_category' => $sales_by_category,
        'top_products' => $top_products,
        'recent_orders' => $recent_orders,
        'profit_margin' => $profit_margin,
        'average_order_value' => $average_order_value,
        'customer_acquisition_rate' => $customer_acquisition_rate,
        'inventory_turnover' => $inventory_turnover,
        'monthly_growth_rate' => $monthly_growth_rate,
        'payment_method_distribution' => $payment_method_distribution,
        'hourly_sales_data' => $hourly_sales_data,
        'weekly_sales_trend' => $weekly_sales_trend,
        'supplier_performance' => $supplier_performance,
        'product_profitability' => $product_profitability
    ];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
