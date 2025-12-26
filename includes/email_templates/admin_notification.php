<?php
// Admin Notification Email Template
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
        .notification-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #e74c3c;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .alert-icon {
            font-size: 48px;
            color: #e74c3c;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Notification</h1>
        <p>System Alert</p>
    </div>
    
    <div class="content">
        <div class="alert-icon">⚠️</div>
        
        <h2>Admin Alert</h2>
        
        <div class="notification-content">
            <h3>Notification Details</h3>
            <p><strong>Time:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
            <p><strong>Message:</strong></p>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
            
            <?php if(!empty($additionalData)): ?>
            <h4>Additional Information:</h4>
            <ul>
                <?php foreach($additionalData as $key => $value): ?>
                <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        
        <p>Please review this notification and take appropriate action if necessary.</p>
        
        <p>Best regards,<br>
        Inventory System</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email from the Inventory System.</p>
        <p>&copy; <?php echo date('Y'); ?> Inventory System. All rights reserved.</p>
    </div>
</body>
</html>
