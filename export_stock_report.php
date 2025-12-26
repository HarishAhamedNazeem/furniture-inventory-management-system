<?php
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(2);

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : 'all';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
$supplier_filter = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Build query conditions (same logic as stock_report.php)
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
                  SUM(CASE WHEN CAST(p.quantity AS UNSIGNED) > 10 THEN 1 ELSE 0 END) as in_stock_count
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

if ($format === 'csv') {
    // CSV Export
    $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, array(
        'Product ID',
        'Product Name',
        'Category',
        'Supplier',
        'Current Stock',
        'Buy Price',
        'Sale Price',
        'Inventory Value',
        'Potential Revenue',
        'Profit Margin (%)',
        'Stock Status',
        'Date Added'
    ));
    
    // CSV Data
    while ($product = $stock_result->fetch_assoc()) {
        fputcsv($output, array(
            $product['id'],
            $product['product_name'],
            $product['category_name'],
            $product['supplier_name'] ?: 'N/A',
            $product['quantity'],
            $product['buy_price'],
            $product['sale_price'],
            $product['inventory_value'],
            $product['potential_revenue'],
            number_format($product['profit_margin'], 1),
            $product['stock_status'],
            date('Y-m-d', strtotime($product['date_added']))
        ));
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'pdf') {
    // PDF Export
    require_once('fpdf/fpdf.php');
    
    $pdf = new FPDF('L', 'mm', 'A4'); // Landscape orientation
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Stock Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Summary section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Summary Statistics', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(60, 6, 'Total Products:', 0, 0);
    $pdf->Cell(30, 6, number_format($summary['total_products']), 0, 1);
    
    $pdf->Cell(60, 6, 'Total Items in Stock:', 0, 0);
    $pdf->Cell(30, 6, number_format($summary['total_items']), 0, 1);
    
    $pdf->Cell(60, 6, 'Inventory Value:', 0, 0);
    $pdf->Cell(30, 6, 'LKR ' . number_format($summary['total_inventory_value'], 2), 0, 1);
    
    $pdf->Cell(60, 6, 'Out of Stock:', 0, 0);
    $pdf->Cell(30, 6, number_format($summary['out_of_stock_count']), 0, 1);
    
    $pdf->Cell(60, 6, 'Low Stock:', 0, 0);
    $pdf->Cell(30, 6, number_format($summary['low_stock_count']), 0, 1);
    
    $pdf->Cell(60, 6, 'In Stock:', 0, 0);
    $pdf->Cell(30, 6, number_format($summary['in_stock_count']), 0, 1);
    
    $pdf->Ln(10);
    
    // Table headers
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(15, 8, 'ID', 1, 0, 'C');
    $pdf->Cell(40, 8, 'Product Name', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Category', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Supplier', 1, 0, 'C');
    $pdf->Cell(15, 8, 'Stock', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Buy Price', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Sale Price', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Inv. Value', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Margin %', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Status', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Date Added', 1, 1, 'C');
    
    // Table data
    $pdf->SetFont('Arial', '', 7);
    while ($product = $stock_result->fetch_assoc()) {
        $pdf->Cell(15, 6, $product['id'], 1, 0, 'C');
        $pdf->Cell(40, 6, substr($product['product_name'], 0, 20), 1, 0, 'L');
        $pdf->Cell(25, 6, substr($product['category_name'], 0, 15), 1, 0, 'L');
        $pdf->Cell(25, 6, substr($product['supplier_name'] ?: 'N/A', 0, 15), 1, 0, 'L');
        $pdf->Cell(15, 6, $product['quantity'], 1, 0, 'C');
        $pdf->Cell(20, 6, number_format($product['buy_price'], 2), 1, 0, 'R');
        $pdf->Cell(20, 6, number_format($product['sale_price'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($product['inventory_value'], 0), 1, 0, 'R');
        $pdf->Cell(20, 6, number_format($product['profit_margin'], 1) . '%', 1, 0, 'R');
        $pdf->Cell(20, 6, $product['stock_status'], 1, 0, 'C');
        $pdf->Cell(20, 6, date('M d, Y', strtotime($product['date_added'])), 1, 1, 'C');
    }
    
    $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}
?>
