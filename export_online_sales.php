<?php
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
        ORDER BY o.created_at DESC";

if (!empty($params)) {
    $stmt = $db->con->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
} else {
    $sales_result = $db->query($sql);
}

if ($_GET['type'] == 'csv') {
    $filename = 'online_sales_history_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Order Number', 'Customer Name', 'Customer Email', 'Customer Phone', 'Items Count', 'Total Amount', 'Payment Status', 'Order Status', 'Created Date']);
    
    if ($sales_result && $sales_result->num_rows > 0) {
        while ($sale = $db->fetch_assoc($sales_result)) {
            fputcsv($output, [
                $sale['order_number'] ?? '#' . $sale['id'],
                $sale['customer_name'] ?? 'Guest Customer',
                $sale['customer_email'] ?? 'N/A',
                $sale['customer_phone'] ?? 'N/A',
                $sale['item_count'],
                $sale['total_amount'],
                $sale['payment_status'],
                $sale['status'],
                date('Y-m-d H:i:s', strtotime($sale['created_at']))
            ]);
        }
    }
    fclose($output);
    exit;
} elseif ($_GET['type'] == 'pdf') {
    require('fpdf/fpdf.php');
    $pdf = new FPDF('L'); // Landscape orientation for better table display
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    
    $pdf->Cell(0,10,'Online Sales History Report',0,1,'C');
    $pdf->Ln(5);
    
    // Add filter info if applicable
    $pdf->SetFont('Arial','',10);
    $filter_text = '';
    if (!empty($search)) {
        $filter_text .= 'Search: ' . $search . ' | ';
    }
    if (!empty($date_from)) {
        $filter_text .= 'From: ' . $date_from . ' | ';
    }
    if (!empty($date_to)) {
        $filter_text .= 'To: ' . $date_to . ' | ';
    }
    if (!empty($status_filter)) {
        $filter_text .= 'Status: ' . $status_filter . ' | ';
    }
    if (!empty($filter_text)) {
        $filter_text = rtrim($filter_text, ' | ');
        $pdf->Cell(0,8,$filter_text,0,1);
        $pdf->Ln(2);
    }
    
    $pdf->SetFont('Arial','',8);
    
    // Table headers
    $pdf->Cell(25,10,'Order #',1);
    $pdf->Cell(40,10,'Customer',1);
    $pdf->Cell(50,10,'Email',1);
    $pdf->Cell(25,10,'Phone',1);
    $pdf->Cell(15,10,'Items',1);
    $pdf->Cell(25,10,'Total',1);
    $pdf->Cell(20,10,'Payment',1);
    $pdf->Cell(20,10,'Status',1);
    $pdf->Cell(30,10,'Date',1);
    $pdf->Ln();
    
    // Table data
    if ($sales_result && $sales_result->num_rows > 0) {
        while ($sale = $db->fetch_assoc($sales_result)) {
            $pdf->Cell(25,10,substr($sale['order_number'] ?? '#' . $sale['id'], 0, 12),1);
            $pdf->Cell(40,10,substr($sale['customer_name'] ?? 'Guest', 0, 18),1);
            $pdf->Cell(50,10,substr($sale['customer_email'] ?? 'N/A', 0, 22),1);
            $pdf->Cell(25,10,substr($sale['customer_phone'] ?? 'N/A', 0, 12),1);
            $pdf->Cell(15,10,$sale['item_count'],1);
            $pdf->Cell(25,10,'LKR ' . number_format($sale['total_amount'], 0),1);
            $pdf->Cell(20,10,substr($sale['payment_status'], 0, 8),1);
            $pdf->Cell(20,10,substr($sale['status'], 0, 8),1);
            $pdf->Cell(30,10,date('M d, Y', strtotime($sale['created_at'])),1);
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(0,10,'No sales records found.',1,1,'C');
    }
    
    $filename = 'online_sales_history_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}
?>
