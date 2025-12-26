<?php
// Password Reset Email Template
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
            background-color: #f39c12;
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
        .reset-section {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #f39c12;
        }
        .reset-link {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #f39c12;
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
        .security-icon {
            font-size: 48px;
            color: #f39c12;
            text-align: center;
            margin: 20px 0;
        }
        .warning {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Password Reset Request</h1>
        <p>Reset your account password</p>
    </div>
    
    <div class="content">
        <div class="security-icon">🔒</div>
        
        <h2>Hello <?php echo htmlspecialchars($customerName); ?>,</h2>
        
        <p>We received a request to reset your password. If you made this request, please click the button below to reset your password.</p>
        
        <div class="reset-section">
            <h3>Password Reset Details</h3>
            <p><strong>Request Time:</strong> <?php echo formatEmailDate(date('Y-m-d H:i:s')); ?></p>
            <p><strong>Reset Token:</strong> <?php echo htmlspecialchars($resetToken); ?></p>
        </div>
        
        <div class="reset-link">
            <h3>Reset Your Password</h3>
            <p>Click the button below to reset your password:</p>
            <a href="#" class="btn">Reset Password</a>
            <p><small>This link will expire in 24 hours for security reasons.</small></p>
        </div>
        
        <div class="warning">
            <h4>⚠️ Security Notice</h4>
            <ul>
                <li>If you did not request this password reset, please ignore this email</li>
                <li>Your password will not be changed until you click the link above</li>
                <li>This reset link will expire in 24 hours</li>
                <li>For security, do not share this email with anyone</li>
            </ul>
        </div>
        
        <p>If you're having trouble clicking the button, you can also copy and paste the following link into your browser:</p>
        <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px;">
            [Reset Password Link]
        </p>
        
        <p>If you have any questions or need assistance, please contact our customer service team.</p>
        
        <p>Best regards,<br>
        The Inventory System Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Inventory System. All rights reserved.</p>
    </div>
</body>
</html>
