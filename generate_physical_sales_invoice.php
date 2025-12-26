<?php
$page_title = 'Physical Sales Invoice';
require_once('includes/load.php');
require_once('includes/db.php');

// Checkin What level user has permission to view this page
page_require_level(1);

// Check if sale id is provided
if (!isset($_GET['id'])) {
    redirect('physical_sales_history.php', false);
}

$sale_id = (int)$_GET['id'];

// Fetch sale details
$sale_sql = "SELECT ps.*, u.name as cashier_name 
             FROM physical_sales ps 
             JOIN users u ON ps.cashier_id = u.id 
             WHERE ps.id = ? LIMIT 1";
$sale_stmt = $db->con->prepare($sale_sql);
$sale_stmt->bind_param("i", $sale_id);
$sale_stmt->execute();
$sale_result = $sale_stmt->get_result();
$sale = $sale_result->fetch_assoc();

if (!$sale) {
    redirect('physical_sales_history.php', false);
}

// Fetch sale items
$items_sql = "SELECT psi.*, p.name as product_name 
              FROM physical_sales_items psi 
              JOIN products p ON psi.product_id = p.id 
              WHERE psi.sale_id = ?";
$items_stmt = $db->con->prepare($items_sql);
$items_stmt->bind_param("i", $sale_id);
$items_stmt->execute();
$sale_items = $items_stmt->get_result();

// Get company information
$company_name = "SWISSWOOD WORKS";
$company_address = "300/A Dehipagoda, Muruthagahamula";
$company_phone = "+94 76 132 1604/+94 75 361 4324";
$company_email = "swisswoodworks@gmail.com";
$company_logo = "libs/images/SW.png";

// Generate invoice number
$invoice_number = 'INV-' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Sales Invoice - <?php echo htmlspecialchars($sale['sale_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Invoice styles - matching customer invoice design */
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 12px;
            line-height: 20px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
            background: white;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
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
            max-width: 100px;
            height: auto;
            margin-bottom: 10px;
        }

        .company-info h2 {
            color: #333;
            font-size: 20px;
            margin: 0 0 5px 0;
        }

        .company-info p,
        .invoice-info p {
            margin: 2px 0;
            color: #666;
        }

        .customer-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 5px;
        }

        .customer-info h4 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .invoice-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
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
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #666;
            font-size: 11px;
        }

        .thank-you {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .invoice-buttons {
            margin-top: 20px;
            text-align: center;
        }

        .btn-print {
            background: #000000;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-print:hover {
            background: #333333;
            color: white;
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
            .invoice-container * {
                color: #000 !important;
            }
        }

        /* Admin portal background */
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="container">
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
                    <p><strong>Sale #:</strong> <?php echo htmlspecialchars($sale['sale_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars(date('F d, Y', strtotime($sale['created_at']))); ?></p>
                    <p><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($sale['status'])); ?></p>
                </div>
            </div>

            <div class="customer-info">
                <h4>Bill To:</h4>
                <?php if (!empty($sale['customer_name'])): ?>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?></p>
                    <?php if (!empty($sale['customer_phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($sale['customer_email'])): ?>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($sale['customer_email']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><strong>Customer:</strong> Walk-in Customer</p>
                <?php endif; ?>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sale_items && $sale_items->num_rows > 0) : ?>
                        <?php while ($item = $sale_items->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="text-right">LKR <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right">LKR <?php echo number_format($item['discount_amount'], 2); ?></td>
                                <td class="text-right">LKR <?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center">No items found for this sale.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                        <td class="text-right"><strong>LKR <?php echo number_format($sale['subtotal'], 2); ?></strong></td>
                    </tr>
                    <?php if ($sale['discount_amount'] > 0): ?>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Total Discount:</strong></td>
                            <td class="text-right"><strong>-LKR <?php echo number_format($sale['discount_amount'], 2); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                        <td class="text-right"><strong>LKR <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="invoice-footer">
                <p><strong>Payment Method:</strong> 
                    <?php 
                    // Handle payment method display with smart fallback
                    $payment_method = $sale['payment_method'];
                    
                    // If payment_method is 0 or invalid, determine from payment amounts
                    if ($payment_method === '0' || $payment_method === 0 || empty($payment_method)) {
                        $cash_amt = $sale['cash_amount'] ?? 0;
                        $card_amt = $sale['card_amount'] ?? 0;
                        
                        // Count how many payment methods were used
                        $methods_count = 0;
                        if ($cash_amt > 0) $methods_count++;
                        if ($card_amt > 0) $methods_count++;
                        
                        if ($methods_count > 1) {
                            echo 'Mixed Payment';
                        } elseif ($cash_amt > 0) {
                            echo 'Cash';
                        } elseif ($card_amt > 0) {
                            echo 'Card';
                        } else {
                            echo 'Cash'; // Default fallback
                        }
                    } else {
                        echo ucwords(str_replace('_', ' ', $payment_method));
                    }
                    ?>
                </p>
                <?php if ($sale['cash_amount'] > 0): ?>
                    <p><strong>Cash Amount:</strong> LKR <?php echo number_format($sale['cash_amount'], 2); ?></p>
                <?php endif; ?>
                <?php if ($sale['card_amount'] > 0): ?>
                    <p><strong>Card Amount:</strong> LKR <?php echo number_format($sale['card_amount'], 2); ?></p>
                <?php endif; ?>
                <?php if ($sale['change_given'] > 0): ?>
                    <p><strong>Change Given:</strong> LKR <?php echo number_format($sale['change_given'], 2); ?></p>
                <?php endif; ?>
                <p class="thank-you">Thank you for your business!</p>
                <p class="computer-generated">This is a computer-generated invoice.</p>
            </div>

            <div class="invoice-buttons">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
