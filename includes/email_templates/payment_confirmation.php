<?php
// Payment Confirmation Email Template
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
            background-color: #27ae60;
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
        .payment-confirmation {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .amount-highlight {
            background-color: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .payment-details {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .success-icon {
            font-size: 48px;
            color: #27ae60;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Confirmed</h1>
        <p>Your payment has been successfully processed</p>
    </div>
    
    <div class="content">
        <div class="success-icon">✓</div>
        
        <h2>Hello <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>Great news! Your payment has been successfully processed and confirmed.</p>
        
        <div class="payment-confirmation">
            <h3>Payment Details</h3>
            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>Payment Date:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
            <p><strong>Payment Status:</strong> <span style="color: #27ae60; font-weight: bold;">CONFIRMED</span></p>
        </div>
        
        <div class="amount-highlight">
            <h3>Amount Paid</h3>
            <p style="font-size: 24px; font-weight: bold; color: #27ae60;">
                <?php echo formatEmailCurrency($order['total_amount']); ?>
            </p>
        </div>
        
        <div class="payment-details">
            <h3>What happens next?</h3>
            <ul>
                <li>Your order will be processed and prepared for shipment</li>
                <li>You will receive a confirmation email when your order is shipped</li>
                <li>Tracking information will be provided once available</li>
                <li>Your invoice is now available for download</li>
            </ul>
        </div>
        
        <p>You can view your order details and track its progress by logging into your account.</p>
        
        <p>If you have any questions about your payment or order, please contact our customer service team.</p>
        
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
