<?php
// Order Status Update Email Template
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
            background-color: #2c3e50;
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
        .status-update {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background-color: #27ae60;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Status Update</h1>
        <p>Your order status has been updated</p>
    </div>
    
    <div class="content">
        <h2>Hello <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>We have an update regarding your order:</p>
        
        <div class="status-update">
            <h3>Status Update</h3>
            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>New Status:</strong> <span class="status-badge"><?php echo ucfirst($newStatus); ?></span></p>
            <p><strong>Updated:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
        </div>
        
        <div class="order-info">
            <h3>What this means:</h3>
            <?php
            $statusMessages = [
                'pending' => 'Your order is being reviewed and will be processed shortly.',
                'confirmed' => 'Your order has been confirmed and is being prepared.',
                'processing' => 'Your order is being processed and prepared for shipment.',
                'shipped' => 'Your order has been shipped and is on its way to you.',
                'delivered' => 'Your order has been delivered successfully.',
                'cancelled' => 'Your order has been cancelled. If you have any questions, please contact us.',
                'refunded' => 'Your order has been refunded. The refund will be processed within 3-5 business days.'
            ];
            
            $message = isset($statusMessages[$newStatus]) ? $statusMessages[$newStatus] : 'Your order status has been updated.';
            echo '<p>' . $message . '</p>';
            ?>
        </div>
        
        <?php if($newStatus === 'shipped'): ?>
        <p><strong>Tracking Information:</strong> We will send you tracking details once they become available.</p>
        <?php endif; ?>
        
        <?php if($newStatus === 'delivered'): ?>
        <p>We hope you enjoy your purchase! If you have any questions or concerns, please don't hesitate to contact us.</p>
        <?php endif; ?>
        
        <p>You can view your order details and track its progress by logging into your account.</p>
        
        <p>If you have any questions about this update, please contact our customer service team.</p>
        
        <p>Thank you for your patience!</p>
        
        <p>Best regards,<br>
        The Inventory System Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Inventory System. All rights reserved.</p>
    </div>
</body>
</html>
