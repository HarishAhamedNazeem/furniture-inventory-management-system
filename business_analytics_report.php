<?php
$page_title = 'Business Analytics Report';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(2);

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Default to first day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Default to today
$sales_type = isset($_GET['sales_type']) ? $_GET['sales_type'] : 'all'; // all, physical, online
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'sales'; // sales, financial

// Build date conditions
$date_condition = "DATE(created_at) BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$param_types = 'ss';

// Check if physical_sales table exists, create if not
$check_sales_table_sql = "SHOW TABLES LIKE 'physical_sales'";
$sales_table_check = $db->query($check_sales_table_sql);
if ($sales_table_check->num_rows == 0) {
    // Create physical_sales table
    $create_sales_table_sql = "CREATE TABLE IF NOT EXISTS `physical_sales` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `sale_number` varchar(50) NOT NULL,
        `cashier_id` int(11) unsigned NOT NULL,
        `customer_id` int(11) unsigned DEFAULT NULL,
        `customer_name` varchar(100) DEFAULT NULL,
        `customer_phone` varchar(20) DEFAULT NULL,
        `customer_email` varchar(100) DEFAULT NULL,
        `subtotal` decimal(25,2) NOT NULL DEFAULT 0.00,
        `discount_amount` decimal(25,2) NOT NULL DEFAULT 0.00,
        `tax_amount` decimal(25,2) NOT NULL DEFAULT 0.00,
        `total_amount` decimal(25,2) NOT NULL,
        `payment_method` varchar(50) NOT NULL,
        `cash_amount` decimal(25,2) DEFAULT 0.00,
        `card_amount` decimal(25,2) DEFAULT 0.00,
        `bank_transfer_amount` decimal(25,2) DEFAULT 0.00,
        `change_given` decimal(25,2) DEFAULT 0.00,
        `status` enum('completed','refunded','partially_refunded') NOT NULL DEFAULT 'completed',
        `notes` text,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `sale_number` (`sale_number`),
        KEY `cashier_id` (`cashier_id`),
        KEY `customer_id` (`customer_id`),
        CONSTRAINT `physical_sales_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `physical_sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    if (!$db->query($create_sales_table_sql)) {
        die("Error creating physical_sales table: " . $db->con->error);
    }
}

// Check if physical_sales_items table exists, create if not
$check_table_sql = "SHOW TABLES LIKE 'physical_sales_items'";
$table_check = $db->query($check_table_sql);
if ($table_check->num_rows == 0) {
    // Create physical_sales_items table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `physical_sales_items` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `sale_id` int(11) unsigned NOT NULL,
        `product_id` int(11) unsigned NOT NULL,
        `quantity` int(11) NOT NULL,
        `unit_price` decimal(25,2) NOT NULL,
        `discount_amount` decimal(25,2) DEFAULT 0.00,
        `total_price` decimal(25,2) NOT NULL,
        `promotion_id` int(11) unsigned DEFAULT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `sale_id` (`sale_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `physical_sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `physical_sales` (`id`) ON DELETE CASCADE,
        CONSTRAINT `physical_sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    if (!$db->query($create_table_sql)) {
        die("Error creating physical_sales_items table: " . $db->con->error);
    }
}

// ===== SALES PERFORMANCE DATA =====
// Get sales data based on type
if ($sales_type === 'physical') {
    // Physical sales only
    $sales_sql = "SELECT 
                    DATE(ps.created_at) as sale_date,
                    COUNT(*) as total_sales,
                    SUM(ps.total_amount) as total_revenue,
                    AVG(ps.total_amount) as avg_sale_amount
                  FROM physical_sales ps 
                  WHERE {$date_condition} AND ps.status = 'completed'
                  GROUP BY DATE(ps.created_at)
                  ORDER BY sale_date DESC";
    
    $stmt = $db->con->prepare($sales_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    
} elseif ($sales_type === 'online') {
    // Online orders only
    $sales_sql = "SELECT 
                    DATE(o.created_at) as sale_date,
                    COUNT(*) as total_sales,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as avg_sale_amount
                  FROM orders o 
                  WHERE {$date_condition} AND o.status != 'cancelled'
                  GROUP BY DATE(o.created_at)
                  ORDER BY sale_date DESC";
    
    $stmt = $db->con->prepare($sales_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    
} else {
    // Combined sales (both physical and online)
    $sales_sql = "SELECT 
                    sale_date,
                    SUM(total_sales) as total_sales,
                    SUM(total_revenue) as total_revenue,
                    AVG(avg_sale_amount) as avg_sale_amount
                  FROM (
                    SELECT 
                      DATE(ps.created_at) as sale_date,
                      COUNT(*) as total_sales,
                      SUM(ps.total_amount) as total_revenue,
                      AVG(ps.total_amount) as avg_sale_amount
                    FROM physical_sales ps 
                    WHERE {$date_condition} AND ps.status = 'completed'
                    GROUP BY DATE(ps.created_at)
                    
                    UNION ALL
                    
                    SELECT 
                      DATE(o.created_at) as sale_date,
                      COUNT(*) as total_sales,
                      SUM(o.total_amount) as total_revenue,
                      AVG(o.total_amount) as avg_sale_amount
                    FROM orders o 
                    WHERE {$date_condition} AND o.status != 'cancelled'
                    GROUP BY DATE(o.created_at)
                  ) combined_sales
                  GROUP BY sale_date
                  ORDER BY sale_date DESC";
    
    // For combined sales, we need to duplicate the parameters since the date_condition appears twice
    $combined_params = array_merge($params, $params);
    $combined_param_types = $param_types . $param_types;
    
    $stmt = $db->con->prepare($sales_sql);
    $stmt->bind_param($combined_param_types, ...$combined_params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
}

// Get summary statistics based on sales type
if ($sales_type === 'physical') {
    // Physical sales only
    $summary_sql = "SELECT 
                      COUNT(*) as total_transactions,
                      SUM(total_amount) as total_revenue,
                      AVG(total_amount) as avg_transaction,
                      MIN(total_amount) as min_transaction,
                      MAX(total_amount) as max_transaction
                    FROM physical_sales 
                    WHERE {$date_condition} AND status = 'completed'";
    
    $stmt = $db->con->prepare($summary_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
} elseif ($sales_type === 'online') {
    // Online orders only
    $summary_sql = "SELECT 
                      COUNT(*) as total_transactions,
                      SUM(total_amount) as total_revenue,
                      AVG(total_amount) as avg_transaction,
                      MIN(total_amount) as min_transaction,
                      MAX(total_amount) as max_transaction
                    FROM orders 
                    WHERE {$date_condition} AND status != 'cancelled'";
    
    $stmt = $db->con->prepare($summary_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
} else {
    // Combined sales (both physical and online)
    $summary_sql = "SELECT 
                      COUNT(*) as total_transactions,
                      SUM(total_amount) as total_revenue,
                      AVG(total_amount) as avg_transaction,
                      MIN(total_amount) as min_transaction,
                      MAX(total_amount) as max_transaction
                    FROM (
                      SELECT total_amount FROM physical_sales 
                      WHERE {$date_condition} AND status = 'completed'
                      UNION ALL
                      SELECT total_amount FROM orders 
                      WHERE {$date_condition} AND status != 'cancelled'
                    ) all_sales";

    $stmt = $db->con->prepare($summary_sql);
    // For combined sales, we need to duplicate the parameters since the date_condition appears twice
    $summary_params = array_merge($params, $params);
    $summary_param_types = $param_types . $param_types;
    $stmt->bind_param($summary_param_types, ...$summary_params);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
}

// Get top products based on sales type
if ($sales_type === 'physical') {
    // Physical sales products only
    $top_products_sql = "SELECT 
                           p.name as product_name,
                           c.name as category_name,
                           SUM(psi.quantity) as total_quantity_sold,
                           SUM(psi.total_price) as total_revenue,
                           COUNT(DISTINCT psi.sale_id) as times_sold,
                           AVG(psi.unit_price) as avg_unit_price
                         FROM physical_sales_items psi
                         JOIN products p ON psi.product_id = p.id
                         JOIN categories c ON p.categorie_id = c.id
                         JOIN physical_sales ps ON psi.sale_id = ps.id
                         WHERE DATE(ps.created_at) BETWEEN ? AND ? AND ps.status = 'completed'
                         GROUP BY p.id, p.name, c.name
                         ORDER BY total_revenue DESC
                         LIMIT 15";

    $stmt = $db->con->prepare($top_products_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $top_products_result = $stmt->get_result();
    
} elseif ($sales_type === 'online') {
    // Online orders products only
    $top_products_sql = "SELECT 
                           p.name as product_name,
                           c.name as category_name,
                           SUM(oi.quantity) as total_quantity_sold,
                           SUM(oi.subtotal) as total_revenue,
                           COUNT(DISTINCT oi.order_id) as times_sold,
                           AVG(oi.price) as avg_unit_price
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.id
                         JOIN categories c ON p.categorie_id = c.id
                         JOIN orders o ON oi.order_id = o.id
                         WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
                         GROUP BY p.id, p.name, c.name
                         ORDER BY total_revenue DESC
                         LIMIT 15";

    $stmt = $db->con->prepare($top_products_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $top_products_result = $stmt->get_result();
    
} else {
    // Combined products (both physical and online)
    $top_products_sql = "SELECT 
                           product_name,
                           category_name,
                           SUM(total_quantity_sold) as total_quantity_sold,
                           SUM(total_revenue) as total_revenue,
                           SUM(times_sold) as times_sold,
                           AVG(avg_unit_price) as avg_unit_price
                         FROM (
                           SELECT 
                             p.name as product_name,
                             c.name as category_name,
                             SUM(psi.quantity) as total_quantity_sold,
                             SUM(psi.total_price) as total_revenue,
                             COUNT(DISTINCT psi.sale_id) as times_sold,
                             AVG(psi.unit_price) as avg_unit_price
                           FROM physical_sales_items psi
                           JOIN products p ON psi.product_id = p.id
                           JOIN categories c ON p.categorie_id = c.id
                           JOIN physical_sales ps ON psi.sale_id = ps.id
                           WHERE DATE(ps.created_at) BETWEEN ? AND ? AND ps.status = 'completed'
                           GROUP BY p.id, p.name, c.name
                           
                           UNION ALL
                           
                           SELECT 
                             p.name as product_name,
                             c.name as category_name,
                             SUM(oi.quantity) as total_quantity_sold,
                             SUM(oi.subtotal) as total_revenue,
                             COUNT(DISTINCT oi.order_id) as times_sold,
                             AVG(oi.price) as avg_unit_price
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           JOIN categories c ON p.categorie_id = c.id
                           JOIN orders o ON oi.order_id = o.id
                           WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
                           GROUP BY p.id, p.name, c.name
                         ) combined_products
                         GROUP BY product_name, category_name
                         ORDER BY total_revenue DESC
                         LIMIT 15";

    $stmt = $db->con->prepare($top_products_sql);
    // For combined products, we need to duplicate the parameters since the date_condition appears twice
    $combined_params = array_merge($params, $params);
    $combined_param_types = $param_types . $param_types;
    $stmt->bind_param($combined_param_types, ...$combined_params);
    $stmt->execute();
    $top_products_result = $stmt->get_result();
}

// Get payment method breakdown
$payment_methods_sql = "SELECT 
                          payment_method,
                          COUNT(*) as transaction_count,
                          SUM(total_amount) as total_amount
                        FROM (
                          SELECT payment_method, total_amount FROM physical_sales 
                          WHERE {$date_condition} AND status = 'completed'
                          UNION ALL
                          SELECT payment_method, total_amount FROM orders 
                          WHERE {$date_condition} AND status != 'cancelled'
                        ) all_payments
                        GROUP BY payment_method
                        ORDER BY total_amount DESC";

$stmt = $db->con->prepare($payment_methods_sql);
// For payment methods, we need to duplicate the parameters since the date_condition appears twice
$payment_params = array_merge($params, $params);
$payment_param_types = $param_types . $param_types;
$stmt->bind_param($payment_param_types, ...$payment_params);
$stmt->execute();
$payment_methods_result = $stmt->get_result();

// ===== FINANCIAL ANALYSIS DATA =====
// Calculate Revenue based on sales type filter
if ($sales_type === 'physical') {
    $revenue_sql = "SELECT 
                      SUM(total_amount) as total_revenue,
                      COUNT(*) as total_transactions
                    FROM physical_sales 
                    WHERE {$date_condition} AND status = 'completed'";
    $stmt = $db->con->prepare($revenue_sql);
    $stmt->bind_param($param_types, ...$params);
} elseif ($sales_type === 'online') {
    $revenue_sql = "SELECT 
                      SUM(total_amount) as total_revenue,
                      COUNT(*) as total_transactions
                    FROM orders 
                    WHERE {$date_condition} AND status != 'cancelled'";
    $stmt = $db->con->prepare($revenue_sql);
    $stmt->bind_param($param_types, ...$params);
} else { // all sales
$revenue_sql = "SELECT 
                  SUM(total_amount) as total_revenue,
                  COUNT(*) as total_transactions
                FROM (
                  SELECT total_amount FROM physical_sales 
                  WHERE {$date_condition} AND status = 'completed'
                  UNION ALL
                  SELECT total_amount FROM orders 
                  WHERE {$date_condition} AND status != 'cancelled'
                ) all_sales";
$stmt = $db->con->prepare($revenue_sql);
// For revenue, we need to duplicate the parameters since the date_condition appears twice
$revenue_params = array_merge($params, $params);
$revenue_param_types = $param_types . $param_types;
$stmt->bind_param($revenue_param_types, ...$revenue_params);
}

$stmt->execute();
$revenue_data = $stmt->get_result()->fetch_assoc();

// Calculate Cost of Goods Sold (COGS) based on sales type filter
if ($sales_type === 'physical') {
    $cogs_sql = "SELECT 
                   SUM(psi.quantity * p.buy_price) as total_cogs,
                   SUM(psi.quantity) as total_items_sold,
                   AVG(p.buy_price) as avg_cost_price
                 FROM physical_sales_items psi
                 JOIN products p ON psi.product_id = p.id
                 JOIN physical_sales ps ON psi.sale_id = ps.id
                 WHERE DATE(ps.created_at) BETWEEN ? AND ? AND ps.status = 'completed'";
    $stmt = $db->con->prepare($cogs_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $cogs_data = $stmt->get_result()->fetch_assoc();
    $total_cogs = $cogs_data['total_cogs'] ?: 0;
    $total_items_sold = $cogs_data['total_items_sold'] ?: 0;
} elseif ($sales_type === 'online') {
    $online_cogs_sql = "SELECT 
                          SUM(oi.quantity * p.buy_price) as online_cogs,
                          SUM(oi.quantity) as online_items_sold
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        JOIN orders o ON oi.order_id = o.id
                        WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'";
    $stmt = $db->con->prepare($online_cogs_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $online_cogs_data = $stmt->get_result()->fetch_assoc();
    $total_cogs = $online_cogs_data['online_cogs'] ?: 0;
    $total_items_sold = $online_cogs_data['online_items_sold'] ?: 0;
} else { // all sales
    // Calculate COGS from physical sales items
$cogs_sql = "SELECT 
               SUM(psi.quantity * p.buy_price) as total_cogs,
               SUM(psi.quantity) as total_items_sold,
               AVG(p.buy_price) as avg_cost_price
             FROM physical_sales_items psi
             JOIN products p ON psi.product_id = p.id
             JOIN physical_sales ps ON psi.sale_id = ps.id
             WHERE DATE(ps.created_at) BETWEEN ? AND ? AND ps.status = 'completed'";
$stmt = $db->con->prepare($cogs_sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$cogs_data = $stmt->get_result()->fetch_assoc();

// Calculate online order COGS
$online_cogs_sql = "SELECT 
                      SUM(oi.quantity * p.buy_price) as online_cogs,
                      SUM(oi.quantity) as online_items_sold
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'";
$stmt = $db->con->prepare($online_cogs_sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$online_cogs_data = $stmt->get_result()->fetch_assoc();

// Combine COGS
$total_cogs = ($cogs_data['total_cogs'] ?: 0) + ($online_cogs_data['online_cogs'] ?: 0);
$total_items_sold = ($cogs_data['total_items_sold'] ?: 0) + ($online_cogs_data['online_items_sold'] ?: 0);
}

// Get discounts based on sales type filter
if ($sales_type === 'physical') {
    $discounts_sql = "SELECT 
                        SUM(discount_amount) as total_discounts
                      FROM physical_sales 
                      WHERE {$date_condition} AND status = 'completed'";
    $stmt = $db->con->prepare($discounts_sql);
    $stmt->bind_param($param_types, ...$params);
} elseif ($sales_type === 'online') {
    // Online orders don't have discount_amount field, so we'll set it to 0
    $total_discounts = 0;
} else { // all sales
$discounts_sql = "SELECT 
                  SUM(discount_amount) as total_discounts
                FROM physical_sales 
                WHERE {$date_condition} AND status = 'completed'";
$stmt = $db->con->prepare($discounts_sql);
$stmt->bind_param($param_types, ...$params);
}

if ($sales_type !== 'online') {
$stmt->execute();
$discounts_data = $stmt->get_result()->fetch_assoc();
    $total_discounts = $discounts_data['total_discounts'] ?: 0;
}

// Calculate key financial metrics
$total_revenue = $revenue_data['total_revenue'] ?: 0;
$gross_profit = $total_revenue - $total_cogs;
$gross_profit_margin = $total_revenue > 0 ? ($gross_profit / $total_revenue) * 100 : 0;
$net_revenue = $total_revenue - $total_discounts;

// Calculate additional income statement components
// For this system, we'll assume minimal operating expenses
// In a real system, you'd have more detailed expense tracking
$operating_expenses = 0; // This would come from an expenses table in a real system
$ebitda = $gross_profit - $operating_expenses; // Earnings Before Interest, Taxes, Depreciation, Amortization
$interest_expense = 0; // This would come from loan/financing data
$tax_expense = 0; // This would be calculated based on tax rates
$net_income = $ebitda - $interest_expense - $tax_expense;

// Handle Export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type === 'csv') {
        // CSV Export
        $filename = 'business_analytics_report_' . $sales_type . '_' . $date_from . '_to_' . $date_to . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, array('Business Analytics Report - Sales, Revenue & Financial Analysis'));
        fputcsv($output, array('Report Type: ' . ucfirst($sales_type) . ' Sales'));
        fputcsv($output, array('Date Range: ' . $date_from . ' to ' . $date_to));
        fputcsv($output, array('Generated: ' . date('Y-m-d H:i:s')));
        fputcsv($output, array(''));
        
        // Sales Performance Summary
        fputcsv($output, array('SALES PERFORMANCE SUMMARY'));
        fputcsv($output, array('Total Transactions', $summary['total_transactions']));
        fputcsv($output, array('Total Revenue', 'LKR ' . number_format($summary['total_revenue'], 2)));
        fputcsv($output, array('Average Transaction', 'LKR ' . number_format($summary['avg_transaction'], 2)));
        fputcsv($output, array(''));
        
        // Daily Sales Data
        fputcsv($output, array('DAILY SALES & REVENUE REPORT'));
        fputcsv($output, array('Date', 'Total Sales', 'Average Sale Amount', 'Total Revenue', 'Cost of Goods Sold', 'Gross Profit', 'Profit Margin', 'Items Sold'));
        
        // Reset the result pointer to iterate again
        $sales_result->data_seek(0);
        while ($row = $sales_result->fetch_assoc()) {
            // Calculate daily COGS and profit for this specific date
            $daily_date = $row['sale_date'];
            
            // Get daily COGS based on sales type
            if ($sales_type === 'physical') {
                $daily_cogs_sql = "SELECT 
                                   SUM(psi.quantity * p.buy_price) as daily_cogs,
                                   SUM(psi.quantity) as daily_items_sold
                                 FROM physical_sales_items psi
                                 JOIN products p ON psi.product_id = p.id
                                 JOIN physical_sales ps ON psi.sale_id = ps.id
                                 WHERE DATE(ps.created_at) = ? AND ps.status = 'completed'";
                $stmt = $db->con->prepare($daily_cogs_sql);
                $stmt->bind_param('s', $daily_date);
            } elseif ($sales_type === 'online') {
                $daily_cogs_sql = "SELECT 
                                   SUM(oi.quantity * p.buy_price) as daily_cogs,
                                   SUM(oi.quantity) as daily_items_sold
                                 FROM order_items oi
                                 JOIN products p ON oi.product_id = p.id
                                 JOIN orders o ON oi.order_id = o.id
                                 WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'";
                $stmt = $db->con->prepare($daily_cogs_sql);
                $stmt->bind_param('s', $daily_date);
            } else {
                // Combined COGS
                $daily_cogs_sql = "SELECT 
                                   SUM(quantity * buy_price) as daily_cogs,
                                   SUM(quantity) as daily_items_sold
                                 FROM (
                                   SELECT psi.quantity, p.buy_price
                                   FROM physical_sales_items psi
                                   JOIN products p ON psi.product_id = p.id
                                   JOIN physical_sales ps ON psi.sale_id = ps.id
                                   WHERE DATE(ps.created_at) = ? AND ps.status = 'completed'
                                   UNION ALL
                                   SELECT oi.quantity, p.buy_price
                                   FROM order_items oi
                                   JOIN products p ON oi.product_id = p.id
                                   JOIN orders o ON oi.order_id = o.id
                                   WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'
                                 ) combined_daily";
                $stmt = $db->con->prepare($daily_cogs_sql);
                if ($sales_type === 'all') {
                    $stmt->bind_param('ss', $daily_date, $daily_date);
                } else {
                    $stmt->bind_param('s', $daily_date);
                }
            }
            
            $stmt->execute();
            $daily_cogs_data = $stmt->get_result()->fetch_assoc();
            $daily_cogs = $daily_cogs_data['daily_cogs'] ?: 0;
            $daily_items_sold = $daily_cogs_data['daily_items_sold'] ?: 0;
            $daily_gross_profit = $row['total_revenue'] - $daily_cogs;
            $daily_profit_margin = $row['total_revenue'] > 0 ? ($daily_gross_profit / $row['total_revenue']) * 100 : 0;
            
            fputcsv($output, array(
                date('M d, Y', strtotime($row['sale_date'])),
                $row['total_sales'],
                'LKR ' . number_format($row['avg_sale_amount'], 2),
                'LKR ' . number_format($row['total_revenue'], 2),
                'LKR ' . number_format($daily_cogs, 2),
                'LKR ' . number_format($daily_gross_profit, 2),
                number_format($daily_profit_margin, 2) . '%',
                $daily_items_sold
            ));
        }
        
        fputcsv($output, array(''));
        
        fclose($output);
        exit;
        
    } elseif ($export_type === 'pdf') {
        // PDF Export
        require_once('fpdf/fpdf.php');
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, 'Business Analytics Report - Sales, Revenue & Financial Analysis', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Report Details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Report Type: ' . ucfirst($sales_type) . ' Sales', 0, 1);
        $pdf->Cell(0, 8, 'Date Range: ' . $date_from . ' to ' . $date_to, 0, 1);
        $pdf->Cell(0, 8, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Ln(10);
        
        // Sales Performance Summary
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, 'SALES PERFORMANCE SUMMARY', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Total Transactions: ' . number_format($summary['total_transactions']), 0, 1);
        $pdf->Cell(0, 6, 'Total Revenue: LKR ' . number_format($summary['total_revenue'], 2), 0, 1);
        $pdf->Cell(0, 6, 'Average Transaction: LKR ' . number_format($summary['avg_transaction'], 2), 0, 1);
        $pdf->Ln(10);
        
        
        // Daily Sales & Revenue Table
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'DAILY SALES & REVENUE REPORT', 0, 1);
        $pdf->SetFont('Arial', 'B', 8);
        
        // Table Headers
        $pdf->Cell(30, 6, 'Date', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Sales', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Revenue', 1, 0, 'C');
        $pdf->Cell(25, 6, 'COGS', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Profit', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Margin', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Items', 1, 1, 'C');
        
        // Table Data
        $pdf->SetFont('Arial', '', 8);
        $sales_result->data_seek(0);
        while ($row = $sales_result->fetch_assoc()) {
            // Calculate daily COGS and profit for this specific date
            $daily_date = $row['sale_date'];
            
            // Get daily COGS based on sales type
            if ($sales_type === 'physical') {
                $daily_cogs_sql = "SELECT 
                                   SUM(psi.quantity * p.buy_price) as daily_cogs,
                                   SUM(psi.quantity) as daily_items_sold
                                 FROM physical_sales_items psi
                                 JOIN products p ON psi.product_id = p.id
                                 JOIN physical_sales ps ON psi.sale_id = ps.id
                                 WHERE DATE(ps.created_at) = ? AND ps.status = 'completed'";
                $stmt = $db->con->prepare($daily_cogs_sql);
                $stmt->bind_param('s', $daily_date);
            } elseif ($sales_type === 'online') {
                $daily_cogs_sql = "SELECT 
                                   SUM(oi.quantity * p.buy_price) as daily_cogs,
                                   SUM(oi.quantity) as daily_items_sold
                                 FROM order_items oi
                                 JOIN products p ON oi.product_id = p.id
                                 JOIN orders o ON oi.order_id = o.id
                                 WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'";
                $stmt = $db->con->prepare($daily_cogs_sql);
                $stmt->bind_param('s', $daily_date);
            } else {
                // Combined COGS
                $daily_cogs_sql = "SELECT 
                                   SUM(quantity * buy_price) as daily_cogs,
                                   SUM(quantity) as daily_items_sold
                                 FROM (
                                   SELECT psi.quantity, p.buy_price
                                   FROM physical_sales_items psi
                                   JOIN products p ON psi.product_id = p.id
                                   JOIN physical_sales ps ON psi.sale_id = ps.id
                                   WHERE DATE(ps.created_at) = ? AND ps.status = 'completed'
                                   UNION ALL
                                   SELECT oi.quantity, p.buy_price
                                   FROM order_items oi
                                   JOIN products p ON oi.product_id = p.id
                                   JOIN orders o ON oi.order_id = o.id
                                   WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'
                                 ) combined_daily";
                $stmt = $db->con->prepare($daily_cogs_sql);
                if ($sales_type === 'all') {
                    $stmt->bind_param('ss', $daily_date, $daily_date);
                } else {
                    $stmt->bind_param('s', $daily_date);
                }
            }
            
            $stmt->execute();
            $daily_cogs_data = $stmt->get_result()->fetch_assoc();
            $daily_cogs = $daily_cogs_data['daily_cogs'] ?: 0;
            $daily_items_sold = $daily_cogs_data['daily_items_sold'] ?: 0;
            $daily_gross_profit = $row['total_revenue'] - $daily_cogs;
            $daily_profit_margin = $row['total_revenue'] > 0 ? ($daily_gross_profit / $row['total_revenue']) * 100 : 0;
            
            $pdf->Cell(30, 6, date('M d', strtotime($row['sale_date'])), 1, 0);
            $pdf->Cell(20, 6, number_format($row['total_sales']), 1, 0, 'C');
            $pdf->Cell(25, 6, 'LKR ' . number_format($row['total_revenue'], 0), 1, 0, 'R');
            $pdf->Cell(25, 6, 'LKR ' . number_format($daily_cogs, 0), 1, 0, 'R');
            $pdf->Cell(25, 6, 'LKR ' . number_format($daily_gross_profit, 0), 1, 0, 'R');
            $pdf->Cell(20, 6, number_format($daily_profit_margin, 1) . '%', 1, 0, 'C');
            $pdf->Cell(20, 6, number_format($daily_items_sold), 1, 1, 'C');
        }
        
        
        $filename = 'business_analytics_report_' . $sales_type . '_' . $date_from . '_to_' . $date_to . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }
}
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
                    <span>Business Analytics Filters</span>
                </strong>
            </div>
            <div class="panel-body">
                <form method="GET" class="form-horizontal">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><strong>Start Date</strong></label>
                                <input type="date" class="form-control" name="date_from" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><strong>End Date</strong></label>
                                <input type="date" class="form-control" name="date_to" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><strong>Sales Category</strong></label>
                                <select class="form-control" name="sales_type" style="font-weight: bold;">
                                    <option value="all" <?php echo $sales_type === 'all' ? 'selected' : ''; ?>>All Sales (Physical + Online)</option>
                                    <option value="physical" <?php echo $sales_type === 'physical' ? 'selected' : ''; ?>>Physical Sales Only</option>
                                    <option value="online" <?php echo $sales_type === 'online' ? 'selected' : ''; ?>>Online Orders Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="glyphicon glyphicon-search"></i> Filter
                                    </button>
                                    <a href="business_analytics_report.php" class="btn btn-default btn-sm">
                                        <i class="glyphicon glyphicon-refresh"></i> Reset
                                    </a>
                                    <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">
                                        <i class="glyphicon glyphicon-download-alt"></i> CSV
                                    </a>
                                    <a href="?export=pdf&<?php echo http_build_query($_GET); ?>" class="btn btn-danger btn-sm">
                                        <i class="glyphicon glyphicon-file"></i> PDF
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

<!-- Comprehensive Sales, Revenue & Financial Report -->
        
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

        <!-- Comprehensive KPI Cards -->
        <div class="kpi-grid">
            <!-- Sales Metrics -->
            <div class="kpi-card" style="border-left-color: #667eea;">
                <div class="kpi-label">Total Transactions</div>
                <div class="kpi-value"><?php echo number_format($summary['total_transactions']); ?></div>
                <div class="kpi-change"><?php echo ucfirst($sales_type); ?> sales</div>
            </div>
            
            <div class="kpi-card" style="border-left-color: #43e97b;">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value">LKR <?php echo number_format($summary['total_revenue'], 0); ?></div>
                <div class="kpi-change">Gross income</div>
            </div>
            
            <div class="kpi-card" style="border-left-color: #fa709a;">
                <div class="kpi-label">Avg Transaction</div>
                <div class="kpi-value">LKR <?php echo number_format($summary['avg_transaction'], 0); ?></div>
                <div class="kpi-change">Per transaction</div>
            </div>
            
            <!-- Financial Metrics -->
            <div class="kpi-card" style="border-left-color: #f39c12;">
                <div class="kpi-label">Cost of Goods Sold</div>
                <div class="kpi-value">LKR <?php echo number_format($total_cogs, 0); ?></div>
                <div class="kpi-change">Total COGS</div>
            </div>
            
            <div class="kpi-card" style="border-left-color: #e74c3c;">
                <div class="kpi-label">Gross Profit</div>
                <div class="kpi-value">LKR <?php echo number_format($gross_profit, 0); ?></div>
                <div class="kpi-change">Net profit</div>
            </div>
            
            <div class="kpi-card" style="border-left-color: #9b59b6;">
                <div class="kpi-label">Profit Margin</div>
                <div class="kpi-value"><?php echo number_format($gross_profit_margin, 1); ?>%</div>
                <div class="kpi-change">Profit percentage</div>
            </div>
            
            <!-- Additional Metrics -->
            <div class="kpi-card" style="border-left-color: #3498db;">
                <div class="kpi-label">Net Revenue</div>
                <div class="kpi-value">LKR <?php echo number_format($net_revenue, 0); ?></div>
                <div class="kpi-change">After discounts</div>
            </div>
            
            <div class="kpi-card" style="border-left-color: #2ecc71;">
                <div class="kpi-label">Total Discounts</div>
                <div class="kpi-value">LKR <?php echo number_format($total_discounts, 0); ?></div>
                <div class="kpi-change">Given discounts</div>
            </div>
            
            <div class="kpi-card" style="border-left-color: #e67e22;">
                <div class="kpi-label">Items Sold</div>
                <div class="kpi-value"><?php echo number_format($total_items_sold); ?></div>
                <div class="kpi-change">Units sold</div>
            </div>
        </div>

        <!-- Comprehensive Sales & Revenue Report Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <strong>
                            <span class="glyphicon glyphicon-list-alt"></span>
                            <span>Sales & Revenue Report</span>
                        </strong>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info" style="margin-bottom: 20px;">
                            <strong>Comprehensive Business Report:</strong> This unified report combines sales performance, revenue analysis, and financial metrics including COGS, profit margins, and business performance indicators for the selected period and sales category.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Total Sales</th>
                                        <th class="text-center">Average Sale Amount</th>
                                        <th class="text-center">Total Revenue</th>
                                        <th class="text-center">Cost of Goods Sold</th>
                                        <th class="text-center">Gross Profit</th>
                                        <th class="text-center">Profit Margin</th>
                                        <th class="text-center">Items Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Reset the result pointer to iterate again
                                    $sales_result->data_seek(0);
                                    while ($row = $sales_result->fetch_assoc()): 
                                        // Calculate daily COGS and profit for this specific date
                                        $daily_date = $row['sale_date'];
                                        
                                        // Get daily COGS based on sales type
                                        if ($sales_type === 'physical') {
                                            $daily_cogs_sql = "SELECT 
                                                               SUM(psi.quantity * p.buy_price) as daily_cogs,
                                                               SUM(psi.quantity) as daily_items_sold
                                                             FROM physical_sales_items psi
                                                             JOIN products p ON psi.product_id = p.id
                                                             JOIN physical_sales ps ON psi.sale_id = ps.id
                                                             WHERE DATE(ps.created_at) = ? AND ps.status = 'completed'";
                                            $stmt = $db->con->prepare($daily_cogs_sql);
                                            $stmt->bind_param('s', $daily_date);
                                        } elseif ($sales_type === 'online') {
                                            $daily_cogs_sql = "SELECT 
                                                               SUM(oi.quantity * p.buy_price) as daily_cogs,
                                                               SUM(oi.quantity) as daily_items_sold
                                                             FROM order_items oi
                                                             JOIN products p ON oi.product_id = p.id
                                                             JOIN orders o ON oi.order_id = o.id
                                                             WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'";
                                            $stmt = $db->con->prepare($daily_cogs_sql);
                                            $stmt->bind_param('s', $daily_date);
                                        } else {
                                            // Combined COGS
                                            $daily_cogs_sql = "SELECT 
                                                               SUM(quantity * buy_price) as daily_cogs,
                                                               SUM(quantity) as daily_items_sold
                                                             FROM (
                                                               SELECT psi.quantity, p.buy_price
                                                               FROM physical_sales_items psi
                                                               JOIN products p ON psi.product_id = p.id
                                                               JOIN physical_sales ps ON psi.sale_id = ps.id
                                                               WHERE DATE(ps.created_at) = ? AND ps.status = 'completed'
                                                               UNION ALL
                                                               SELECT oi.quantity, p.buy_price
                                                               FROM order_items oi
                                                               JOIN products p ON oi.product_id = p.id
                                                               JOIN orders o ON oi.order_id = o.id
                                                               WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'
                                                             ) combined_daily";
                                            $stmt = $db->con->prepare($daily_cogs_sql);
                                            if ($sales_type === 'all') {
                                                $stmt->bind_param('ss', $daily_date, $daily_date);
                                            } else {
                                                $stmt->bind_param('s', $daily_date);
                                            }
                                        }
                                        
                                        $stmt->execute();
                                        $daily_cogs_data = $stmt->get_result()->fetch_assoc();
                                        $daily_cogs = $daily_cogs_data['daily_cogs'] ?: 0;
                                        $daily_items_sold = $daily_cogs_data['daily_items_sold'] ?: 0;
                                        $daily_gross_profit = $row['total_revenue'] - $daily_cogs;
                                        $daily_profit_margin = $row['total_revenue'] > 0 ? ($daily_gross_profit / $row['total_revenue']) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td class="text-center">
                                                <strong><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo date('l', strtotime($row['sale_date'])); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary badge-lg"><?php echo number_format($row['total_sales']); ?></span>
                                            </td>
                                            <td class="text-right">
                                                <strong class="text-info">LKR <?php echo number_format($row['avg_sale_amount'], 0); ?></strong>
                                            </td>
                                            <td class="text-right">
                                                <strong class="text-success">LKR <?php echo number_format($row['total_revenue'], 0); ?></strong>
                                            </td>
                                            <td class="text-right">
                                                <strong class="text-warning">LKR <?php echo number_format($daily_cogs, 0); ?></strong>
                                            </td>
                                            <td class="text-right">
                                                <strong class="<?php echo $daily_gross_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    LKR <?php echo number_format($daily_gross_profit, 0); ?>
                                                </strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $daily_profit_margin >= 20 ? 'badge-success' : ($daily_profit_margin >= 10 ? 'badge-warning' : 'badge-danger'); ?>">
                                                    <?php echo number_format($daily_profit_margin, 1); ?>%
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-info"><?php echo number_format($daily_items_sold); ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr>
                                        <th class="text-center">
                                            <strong>TOTAL SUMMARY</strong>
                                        </th>
                                        <th class="text-center">
                                            <span class="badge badge-primary badge-lg"><?php echo number_format($summary['total_transactions']); ?></span>
                                        </th>
                                        <th class="text-right">
                                            <strong class="text-info">LKR <?php echo number_format($summary['avg_transaction'], 0); ?></strong>
                                        </th>
                                        <th class="text-right">
                                            <strong class="text-success">LKR <?php echo number_format($summary['total_revenue'], 0); ?></strong>
                                        </th>
                                        <th class="text-right">
                                            <strong class="text-warning">LKR <?php echo number_format($total_cogs, 0); ?></strong>
                                        </th>
                                        <th class="text-right">
                                            <strong class="<?php echo $gross_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                LKR <?php echo number_format($gross_profit, 0); ?>
                                            </strong>
                                        </th>
                                        <th class="text-center">
                                            <span class="badge <?php echo $gross_profit_margin >= 20 ? 'badge-success' : ($gross_profit_margin >= 10 ? 'badge-warning' : 'badge-danger'); ?>">
                                                <?php echo number_format($gross_profit_margin, 1); ?>%
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="badge badge-info"><?php echo number_format($total_items_sold); ?></span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>



<?php include_once('layouts/footer.php'); ?>
