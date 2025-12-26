<?php
// Order Acceptance Email Template
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
            background-color: #28a745;
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
        .acceptance-notice {
            background-color: #d4edda;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
            border: 1px solid #c3e6cb;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background-color: #28a745;
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
        .delivery-info {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .customer-message {
            background-color: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #0ea5e9;
            font-style: italic;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .highlight {
            color: #28a745;
            font-weight: bold;
        }
        .timeline {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .timeline-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 14px;
        }
        .timeline-icon.completed {
            background-color: #28a745;
        }
        .timeline-icon.current {
            background-color: #007bff;
        }
        .timeline-icon.pending {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Accepted!</h1>
        <p>Your order has been confirmed and is being processed</p>
    </div>
    
    <div class="content">
        <h2>Dear <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>Great news! We're excited to inform you that your order has been accepted and is now being processed.</p>
        
        <div class="acceptance-notice">
            <h3>Order Acceptance Details</h3>
            <p><strong>Order Number:</strong> <span class="highlight"><?php echo htmlspecialchars($order['order_number']); ?></span></p>
            <p><strong>Status:</strong> <span class="status-badge">Processing</span></p>
            <p><strong>Accepted On:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
        </div>
        
        <?php if (!empty($customerMessage)): ?>
        <div class="customer-message">
            <h3>Personal Message from Our Team:</h3>
            <p>"<?php echo nl2br(htmlspecialchars($customerMessage)); ?>"</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($estimatedDelivery)): ?>
        <div class="delivery-info">
            <h3>Estimated Delivery Information</h3>
            <p><strong>Expected Delivery Date:</strong> <?php echo date('F j, Y', strtotime($estimatedDelivery)); ?></p>
            <p>We'll keep you updated on your order's progress and notify you when it's ready for delivery.</p>
        </div>
        <?php endif; ?>
        
        <div class="timeline">
            <h3>Order Progress Timeline</h3>
            <div class="timeline-item">
                <div class="timeline-icon completed">✓</div>
                <div>Order Placed</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon completed">✓</div>
                <div>Order Accepted</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon current">⚙</div>
                <div>Processing & Preparation</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon pending">📦</div>
                <div>Ready for Shipment</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon pending">🚚</div>
                <div>Out for Delivery</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon pending">✓</div>
                <div>Delivered</div>
            </div>
        </div>
        
        <div class="order-info">
            <h3>What happens next?</h3>
            <ul>
                <li>Our team is now preparing your order for shipment</li>
                <li>You'll receive updates as your order progresses</li>
                <li>We'll notify you when your order is ready for delivery</li>
                <li>You can track your order status in your account dashboard</li>
            </ul>
        </div>
        
        <p>Thank you for choosing Swisswood Works! We appreciate your business and look forward to delivering your order.</p>
        
        <p>If you have any questions about your order, please don't hesitate to contact our customer service team.</p>
        
        <p>Best regards,<br>
        <strong>The Swisswood Works Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Swisswood Works. All rights reserved.</p>
    </div>
</body>
</html>
