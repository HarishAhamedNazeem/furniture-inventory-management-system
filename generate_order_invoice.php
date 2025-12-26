<?php
$page_title = 'Order Invoice';
require_once('includes/load.php');
require_once('includes/db.php');

// Checkin What level user has permission to view this page (adjust level as needed)
page_require_level(1);

// Check if order id is provided
if (!isset($_GET['id'])) {
    redirect('orders.php', false);
}

$order_id = (int)$_GET['id'];

// Fetch order details
$order_sql = "SELECT o.*, c.name as customer_name, c.email, c.phone, c.address as customer_address 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              WHERE o.id = ? LIMIT 1";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    redirect('orders.php', false);
}

// Fetch order items
$items_sql = "SELECT oi.*, p.name as product_name 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

// Get company information (you may want to store this in a settings table)
$company_name = "SWISS WOODWORKS"; // Replace with actual company name
$company_address = "300/A Dehipagoda, Muruthagahamula"; // Replace with actual company address
$company_phone = "+94 76 132 1604/+94 75 361 4324"; // Replace with actual company phone
$company_email = "swisswoodworks@gmail.com"; // Replace with actual company email
$company_logo = "libs/images/SW.png"; // Replace with actual logo path

// Generate invoice number (can be stored in orders table if needed)
$invoice_number = 'INV-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<style>
/* Add or adapt styles from main.css for the invoice layout */
/* Ensure these styles are suitable for printing */
.invoice-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 30px;
    border: 1px solid #eee;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
    font-size: 12px; /* Adjusted font size for better fit */
    line-height: 20px; /* Adjusted line height */
    font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
    color: #555;
    background: white;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px; /* Adjusted margin */
    border-bottom: 2px solid #eee;
    padding-bottom: 10px; /* Adjusted padding */
}

.company-info,
.invoice-info {
 flex: 1;
 padding: 0 10px;
}

.company-info {
 text-align: left;
}

.invoice-info {
 text-align: right;
}

.company-logo {
    max-width: 100px; /* Adjusted logo size */
    height: auto;
    margin-bottom: 10px; /* Adjusted margin */
}

.company-info h2 {
    color: #333;
    font-size: 20px; /* Adjusted font size */
    margin: 0 0 5px 0; /* Adjusted margin */
}

.company-info p,
.invoice-info p {
    margin: 2px 0; /* Adjusted margin */
    color: #666;
}

.customer-info {
    margin-bottom: 20px; /* Adjusted margin */
    padding: 15px; /* Adjusted padding */
    background: #f9f9f9;
    border: 1px solid #eee; /* Added border */
    border-radius: 5px;
}

.customer-info h4 {
    color: #333;
    margin: 0 0 10px 0;
    font-size: 16px; /* Adjusted font size */
}

.invoice-table {
    width: 100%;
    margin-bottom: 20px; /* Adjusted margin */
    border-collapse: collapse;
}

.invoice-table th,
.invoice-table td {
    padding: 8px; /* Adjusted padding */
    border: 1px solid #ddd;
    text-align: left; /* Default text alignment */
}

.invoice-table th {
    background: #f8f8f8;
    font-weight: bold;
    color: #333;
}

.invoice-table td.text-right {
 text-align: right;
}

.invoice-table tfoot td {
    background: #f8f8f8;
    font-weight: bold;
}

.invoice-footer {
    margin-top: 20px; /* Adjusted margin */
    padding-top: 10px; /* Adjusted padding */
    border-top: 2px solid #eee;
    text-align: center;
    color: #666;
    font-size: 11px; /* Adjusted font size */
}

.thank-you {
    font-size: 14px; /* Adjusted font size */
    margin-bottom: 5px; /* Adjusted margin */
}

.invoice-buttons {
    margin-top: 20px;
    text-align: center;
}

@media print {
    body * {
        visibility: hidden;
    }
    .invoice-container, .invoice-container * {
        visibility: visible;
    }
    .invoice-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        border: none;
        padding: 0;
        margin: 0;
    }
    .invoice-buttons {
        display: none;
    }
    /* Ensure text is black for printing */
    .invoice-container * {
        color: #000 !important;
    }
}

/* Customer portal specific styles */
.customer-header {
    background: rgb(0, 0, 0);
    color: white;
    padding: 20px 0;
    margin-bottom: 30px;
}

.customer-header h1 {
    margin: 0;
    font-size: 24px;
}

.btn-customer {
    background: rgb(0, 0, 0);
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    margin: 5px;
}

/* Clean invoice background */
body {
    background: #f5f5f5;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}

</style>

<div class="invoice-container">
    <div class="invoice-header">
        <div class="company-info">
            <?php if(file_exists($company_logo)): ?>
                <img src="<?php echo $company_logo; ?>" alt="Company Logo" class="company-logo">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($company_name); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($company_address)); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($company_phone); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($company_email); ?></p>
        </div>
        <div class="invoice-info">
            <h3>INVOICE</h3>
            <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice_number); ?></p>
            <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars(date('F d, Y', strtotime($order['created_at']))); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($order['status'])); ?></p>
        </div>
    </div>

    <div class="customer-info">
        <h4>Bill To:</h4>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
        <p><strong>Shipping Address:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
    </div>

    <table class="invoice-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($order_items && $order_items->num_rows > 0) : ?>
                <?php while ($item = $order_items->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td class="text-right">LKR <?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-right">LKR <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4" class="text-center">No items found for this order.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total Amount:</strong></td>
                                        <td class="text-right"><strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="invoice-footer">
        <p class="thank-you">Thank you for your order!</p>
        <p class="computer-generated">This is a computer-generated invoice.</p>
    </div>

    <div class="invoice-buttons">
        <button onclick="window.print()" class="btn-customer">
            <i class="fas fa-print"></i> Print Invoice
        </button>
    </div>
</div>

</body>
</html> 