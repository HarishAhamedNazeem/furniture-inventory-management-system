<?php
// Invoice Ready Email Template
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #8e44ad;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .invoice-info {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #8e44ad;
        }
        .download-section {
            background-color: #f0e6ff;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #8e44ad;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .invoice-icon {
            font-size: 48px;
            color: #8e44ad;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice Ready</h1>
        <p>Your invoice is now available for download</p>
    </div>
    
    <div class="content">
        <div class="invoice-icon">📄</div>
        
        <h2>Hello <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>Your invoice is now ready and available for download.</p>
        
        <div class="invoice-info">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoiceNumber); ?></p>
            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>Invoice Date:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
            <p><strong>Total Amount:</strong> <?php echo formatEmailCurrency($order['total_amount']); ?></p>
        </div>
        
        <div class="download-section">
            <h3>Download Your Invoice</h3>
            <p>Click the button below to download your invoice in PDF format.</p>
            <a href="#" class="btn">Download Invoice</a>
            <p><small>You can also access this invoice anytime from your account dashboard.</small></p>
        </div>
        
        <p><strong>Important:</strong> Please keep this invoice for your records. You may need it for warranty claims, returns, or tax purposes.</p>
        
        <p>If you have any questions about your invoice or need assistance, please contact our customer service team.</p>
        
        <p>Thank you for your business!</p>
        
        <p>Best regards,<br>
        The Inventory System Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Inventory System. All rights reserved.</p>
    </div>
</body>
</html>
