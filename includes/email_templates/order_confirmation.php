<?php
// Order Confirmation Email Template
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
        .order-details {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .item-table th, .item-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .item-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .total-section {
            background-color: #e8f5e8;
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
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
        <p>Thank you for your order!</p>
    </div>
    
    <div class="content">
        <h2>Hello <?php echo htmlspecialchars($customer['name']); ?>,</h2>
        
        <p>We have received your order and it is being processed. Here are the details:</p>
        
        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>Order Date:</strong> <?php echo formatEmailDate($order['created_at']); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
            <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
        </div>
        
        <h3>Order Items</h3>
        <table class="item-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo formatEmailCurrency($item['sale_price']); ?></td>
                    <td><?php echo formatEmailCurrency($item['sale_price'] * $item['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <h3>Order Summary</h3>
            <p><strong>Subtotal:</strong> <?php echo formatEmailCurrency($order['subtotal']); ?></p>
            <?php if($order['discount_amount'] > 0): ?>
            <p><strong>Discount:</strong> -<?php echo formatEmailCurrency($order['discount_amount']); ?></p>
            <?php endif; ?>
            <p><strong>Total Amount:</strong> <?php echo formatEmailCurrency($order['total_amount']); ?></p>
        </div>
        
        <h3>Delivery Information</h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
        
        <p>We will send you another email when your order is ready for shipment.</p>
        
        <p>If you have any questions about your order, please contact our customer service team.</p>
        
        <p>Thank you for choosing us!</p>
        
        <p>Best regards,<br>
        The Inventory System Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; <?php echo date('Y'); ?> Inventory System. All rights reserved.</p>
    </div>
</body>
</html>
