<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('../fpdf/fpdf.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Get order details with customer information
$order_sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, 
              c.phone as customer_phone, c.address as customer_address
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              WHERE o.id = " . $order_id . " AND o.customer_id = " . $customer_id;
$order_result = $db->query($order_sql);

if ($db->num_rows($order_result) == 0) {
    header('Location: orders.php');
    exit();
}

$order = $db->fetch_assoc($order_result);

// Check if payment is completed
if ($order['payment_status'] !== 'paid') {
    header('Location: orders.php?error=invoice_not_available');
    exit();
}

// Get order items
$items_sql = "SELECT oi.*, p.name as product_name 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = " . $order_id;
$items_result = $db->query($items_sql);

// Get or create invoice
$invoice_sql = "SELECT * FROM invoices WHERE order_id = " . $order_id;
$invoice_result = $db->query($invoice_sql);

if ($db->num_rows($invoice_result) == 0) {
    // Create invoice if it doesn't exist
    $invoice_number = 'INV-' . date('Y') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
    $create_invoice_sql = "INSERT INTO invoices (order_id, invoice_number, subtotal, discount_amount, 
                          total_amount, invoice_date, status, created_at, updated_at) 
                          VALUES (" . $order_id . ", '" . $invoice_number . "', " . $order['total_amount'] . ", 
                          " . ($order['discount_amount'] ?? 0) . ", " . $order['total_amount'] . ", 
                          NOW(), 'sent', NOW(), NOW())";
    $db->query($create_invoice_sql);
    
    // Get the newly created invoice
    $invoice_result = $db->query($invoice_sql);
}

$invoice = $db->fetch_assoc($invoice_result);

// Create PDF
class InvoicePDF extends FPDF {
    function Header() {
        // Company Logo/Header
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(26, 26, 26); // Dark color
        $this->Cell(0, 15, 'SWISSWOOD WORKS', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Premium Furniture & Crafts', 0, 1, 'C');
        $this->Cell(0, 5, 'Email: info@swisswoodworks.com | Phone: +94 123 456 789', 0, 1, 'C');
        
        $this->Ln(10);
        
        // Invoice title
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Thank you for your business! - Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Create PDF instance
$pdf = new InvoicePDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Invoice details section
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(166, 85, 23);
$pdf->Cell(95, 8, 'Invoice Details', 0, 0);
$pdf->Cell(95, 8, 'Bill To', 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Left column - Invoice details
$pdf->Cell(95, 6, 'Invoice Number: ' . $invoice['invoice_number'], 0, 0);
$pdf->Cell(95, 6, 'Customer: ' . $order['customer_name'], 0, 1);

$pdf->Cell(95, 6, 'Order Number: ' . ($order['order_number'] ?? '#' . $order['id']), 0, 0);
$pdf->Cell(95, 6, 'Email: ' . $order['customer_email'], 0, 1);

$pdf->Cell(95, 6, 'Invoice Date: ' . date('M d, Y', strtotime($invoice['invoice_date'])), 0, 0);
if (!empty($order['customer_phone'])) {
    $pdf->Cell(95, 6, 'Phone: ' . $order['customer_phone'], 0, 1);
} else {
    $pdf->Cell(95, 6, '', 0, 1);
}

$pdf->Cell(95, 6, 'Order Date: ' . date('M d, Y', strtotime($order['created_at'])), 0, 0);
$pdf->Cell(95, 6, '', 0, 1);

$pdf->Cell(95, 6, 'Payment Status: ' . ucfirst($order['payment_status']), 0, 0);
$pdf->Cell(95, 6, '', 0, 1);

// Shipping address
if (!empty($order['shipping_address'])) {
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, 'Shipping Address:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 5, $order['shipping_address']);
}

$pdf->Ln(10);

// Items table header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(166, 85, 23);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(100, 8, 'Product', 1, 0, 'L', true);
$pdf->Cell(20, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Total', 1, 1, 'R', true);

// Items table content
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$subtotal = 0;

while ($item = $db->fetch_assoc($items_result)) {
    $item_total = $item['quantity'] * $item['price'];
    $subtotal += $item_total;
    
    $pdf->Cell(100, 8, $item['product_name'], 1, 0, 'L');
    $pdf->Cell(20, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(30, 8, 'LKR ' . number_format($item['price'], 2), 1, 0, 'R');
    $pdf->Cell(30, 8, 'LKR ' . number_format($item_total, 2), 1, 1, 'R');
}

// Totals section
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);

// Subtotal
$pdf->Cell(150, 8, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(30, 8, 'LKR ' . number_format($subtotal, 2), 1, 1, 'R');

// Discount (if any)
if ($order['discount_amount'] > 0) {
    $pdf->SetTextColor(0, 128, 0); // Green for discount
    $pdf->Cell(150, 8, 'Discount:', 0, 0, 'R');
    $pdf->Cell(30, 8, '-LKR ' . number_format($order['discount_amount'], 2), 1, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);
}


// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(166, 85, 23);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(150, 10, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(30, 10, 'LKR ' . number_format($order['total_amount'], 2), 1, 1, 'R', true);

// Payment information
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'Payment Information:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Payment Method: ' . ucfirst(str_replace('_', ' ', $order['payment_method'])), 0, 1);
$pdf->Cell(0, 6, 'Payment Status: ' . ucfirst($order['payment_status']), 0, 1);

// Terms and conditions
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'Terms & Conditions:', 0, 1);
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(0, 4, "1. This invoice is computer generated and does not require a signature.\n2. Payment is due within 30 days of invoice date.\n3. All sales are final unless otherwise specified.\n4. For any queries, please contact our customer service team.");

// Mark invoice as downloaded (update status if needed)
$update_invoice_sql = "UPDATE invoices SET updated_at = NOW() WHERE id = " . $invoice['id'];
$db->query($update_invoice_sql);

// Output PDF
$filename = 'Invoice_' . $invoice['invoice_number'] . '.pdf';
$pdf->Output('D', $filename); // 'D' forces download
exit();
?>
