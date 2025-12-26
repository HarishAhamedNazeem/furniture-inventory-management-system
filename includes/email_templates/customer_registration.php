<?php
// Customer Registration Email Template
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
            background-color: #3498db;
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
        .welcome-section {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .features {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
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
        .welcome-icon {
            font-size: 48px;
            color: #3498db;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to Swisswood Works!</h1>
        <p>Your account has been successfully created</p>
    </div>
    
    <div class="content">
        <div class="welcome-icon">🎉</div>
        
        <h2>Hello <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>Welcome to Swisswood Works Online Store! We're excited to have you as a new customer.</p>
        
        <div class="welcome-section">
            <h3>Account Details</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
            <p><strong>Registration Date:</strong> <?php echo formatEmailDate($customer['created_at']); ?></p>
        </div>
        
        <div class="features">
            <h3>What you can do with your account:</h3>
            <ul>
                <li>Browse our extensive product catalog</li>
                <li>Add items to your wishlist</li>
                <li>Place orders online</li>
                <li>Track your order status</li>
                <li>Download invoices</li>
                <li>Manage your profile</li>
                <li>View order history</li>
                <li>Receive exclusive offers and promotions</li>
            </ul>
        </div>
        
        <p>To get started, simply log in to your account and start exploring our products.</p>
        
        <!-- <div style="text-align: center;">
            <a href="customer/dashboard.php" class="btn">Start Shopping</a>
        </div> -->
        
        <p>If you have any questions or need assistance, our customer service team is here to help.</p>
        
        <p>Thank you for choosing us!</p>
        
        <p>Best regards,<br>
        The Swisswood Works Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Swisswood Works. All rights reserved.</p>
    </div>
</body>
</html>
