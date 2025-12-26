<?php
// Order Rejection Email Template
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
            background-color: #e74c3c;
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
        .rejection-notice {
            background-color: #fff5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #e74c3c;
            border: 1px solid #fecaca;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background-color: #e74c3c;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .order-info {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .reason-box {
            background-color: #fef2f2;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }
        .contact-info {
            background-color: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #0ea5e9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .highlight {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Rejection Notice</h1>
        <p>Important information about your order</p>
    </div>
    
    <div class="content">
        <h2>Dear <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>We regret to inform you that your order has been rejected by our team.</p>
        
        <div class="rejection-notice">
            <h3>Order Rejection Details</h3>
            <p><strong>Order Number:</strong> <span class="highlight"><?php echo htmlspecialchars($order['order_number']); ?></span></p>
            <p><strong>Status:</strong> <span class="status-badge">Rejected</span></p>
            <p><strong>Rejection Date:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
        </div>
        
        <?php if (!empty($rejectionReason)): ?>
        <div class="reason-box">
            <h3>Reason for Rejection:</h3>
            <p><?php echo nl2br(htmlspecialchars($rejectionReason)); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="order-info">
            <h3>What happens next?</h3>
            <ul>
                <li>Your order has been cancelled and will not be processed</li>
                <li>If any payment was made, it will be refunded within 3-5 business days</li>
                <li>You can place a new order with corrected information if needed</li>
                <li>Our customer service team is available to assist you</li>
            </ul>
        </div>
        
        <div class="contact-info">
            <h3>Need Help?</h3>
            <p>If you have any questions about this rejection or need assistance with placing a new order, please don't hesitate to contact us:</p>
            <ul>
                <li><strong>Email:</strong> support@swisswoodworks.com</li>
                <li><strong>Phone:</strong> +94 XX XXX XXXX</li>
                <li><strong>Hours:</strong> Monday - Friday, 9:00 AM - 6:00 PM</li>
            </ul>
        </div>
        
        <p>We apologize for any inconvenience this may cause and appreciate your understanding.</p>
        
        <p>Thank you for considering Swisswood Works for your needs.</p>
        
        <p>Best regards,<br>
        <strong>The Swisswood Works Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Swisswood Works. All rights reserved.</p>
    </div>
</body>
</html>
