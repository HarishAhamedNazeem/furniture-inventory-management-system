<?php
$page_title = 'Physical Sale Details';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(1);

$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sale_id <= 0) {
    $session->msg("d", "Invalid sale ID");
    redirect('physical_sales_history.php', false);
}

// Get sale details
$sale_sql = "SELECT ps.*, u.name as cashier_name 
             FROM physical_sales ps 
             JOIN users u ON ps.cashier_id = u.id 
             WHERE ps.id = ?";
$stmt = $db->con->prepare($sale_sql);
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

if (!$sale) {
    $session->msg("d", "Sale not found");
    redirect('physical_sales_history.php', false);
}

// Get sale items
$items_sql = "SELECT psi.*, p.name as product_name, p.images as product_images, p.primary_image_index,
                     pr.name as promotion_name, pr.discount_type, pr.discount_value
              FROM physical_sales_items psi 
              JOIN products p ON psi.product_id = p.id 
              LEFT JOIN promotions pr ON psi.promotion_id = pr.id
              WHERE psi.sale_id = ? 
              ORDER BY psi.id ASC";
$stmt = $db->con->prepare($items_sql);
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Process items to extract primary image from JSON
$items = [];
while ($item = $items_result->fetch_assoc()) {
    // Extract primary image from JSON
    if (!empty($item['product_images'])) {
        $images_array = json_decode($item['product_images'], true);
        if (is_array($images_array) && !empty($images_array)) {
            $primary_index = isset($item['primary_image_index']) ? (int)$item['primary_image_index'] : 0;
            // Ensure primary_index is within bounds
            if ($primary_index >= count($images_array)) {
                $primary_index = 0;
            }
            $item['product_image'] = $images_array[$primary_index];
        } else {
            $item['product_image'] = null;
        }
    } else {
        $item['product_image'] = null;
    }
    $items[] = $item;
}

// Refunds functionality removed as requested
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <strong>
                    <span class="glyphicon glyphicon-list-alt"></span>
                    <span>Sale Details - <?php echo htmlspecialchars($sale['sale_number']); ?></span>
                </strong>
                <div class="pull-right">
                    <a href="generate_walkin_sales_invoice.php?id=<?php echo $sale['id']; ?>" 
                       class="btn btn-info" target="_blank">
                        <i class="glyphicon glyphicon-file"></i> Generate Invoice
                    </a>
                    <a href="physical_sales_history.php" class="btn btn-default">
                        <i class="glyphicon glyphicon-arrow-left"></i> Back to History
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <!-- Sale Information -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Sale Information</h5>
                        <table class="table table-condensed">
                            <tr>
                                <td><strong>Sale Number:</strong></td>
                                <td><?php echo htmlspecialchars($sale['sale_number']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date & Time:</strong></td>
                                <td><?php echo date('M d, Y H:i:s', strtotime($sale['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cashier:</strong></td>
                                <td><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($sale['status']) {
                                        case 'completed':
                                            $status_class = 'label-success';
                                            break;
                                        case 'refunded':
                                            $status_class = 'label-danger';
                                            break;
                                        case 'partially_refunded':
                                            $status_class = 'label-warning';
                                            break;
                                    }
                                    ?>
                                    <span class="label <?php echo $status_class; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $sale['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Customer Information</h5>
                        <table class="table table-condensed">
                            <?php if (!empty($sale['customer_name'])): ?>
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($sale['customer_phone'])): ?>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo htmlspecialchars($sale['customer_phone']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($sale['customer_email'])): ?>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($sale['customer_email']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (empty($sale['customer_name']) && empty($sale['customer_phone']) && empty($sale['customer_email'])): ?>
                                <tr>
                                    <td colspan="2" class="text-muted">Walk-in Customer</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Items Table -->
                <h5>Items Sold</h5>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="row">
                                        <div class="col-xs-3">
                                            <?php if ($item['product_image'] && file_exists('uploads/products/' . $item['product_image'])): ?>
                                                <img src="uploads/products/<?php echo $item['product_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                     class="img-responsive" style="max-height: 50px;">
                                            <?php else: ?>
                                                <div class="no-image" style="height: 50px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                    <i class="glyphicon glyphicon-picture text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-xs-9">
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <?php if ($item['promotion_name']): ?>
                                                <br><small class="text-success">
                                                    <i class="glyphicon glyphicon-tag"></i> 
                                                    <?php echo htmlspecialchars($item['promotion_name']); ?>
                                                    <?php if ($item['discount_type'] === 'percentage'): ?>
                                                        (<?php echo $item['discount_value']; ?>% OFF)
                                                    <?php else: ?>
                                                        (LKR <?php echo number_format($item['discount_value'], 2); ?> OFF)
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-right">LKR <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right">
                                    <?php if ($item['discount_amount'] > 0): ?>
                                        <span class="text-success">-LKR <?php echo number_format($item['discount_amount'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><strong>LKR <?php echo number_format($item['total_price'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Payment Information -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Payment Summary</h5>
                        <table class="table table-condensed">
                            <tr>
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-right">LKR <?php echo number_format($sale['subtotal'], 2); ?></td>
                            </tr>
                            <?php if ($sale['discount_amount'] > 0): ?>
                                <tr>
                                    <td><strong>Total Discount:</strong></td>
                                    <td class="text-right text-success">-LKR <?php echo number_format($sale['discount_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="success">
                                <td><strong>Total Amount:</strong></td>
                                <td class="text-right"><strong>LKR <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Details</h5>
                        <table class="table table-condensed">
                            <tr>
                                <td><strong>Payment Method:</strong></td>
                                <td>
                                    <span class="label label-info">
                                        <?php 
                                        // Handle payment method display with smart fallback
                                        $payment_method = $sale['payment_method'];
                                        
                                        // If payment_method is 0 or invalid, determine from payment amounts
                                        if ($payment_method === '0' || $payment_method === 0 || empty($payment_method)) {
                                            $cash_amt = $sale['cash_amount'] ?? 0;
                                            $card_amt = $sale['card_amount'] ?? 0;
                                            $bank_amt = $sale['bank_transfer_amount'] ?? 0;
                                            
                                            // Count how many payment methods were used
                                            $methods_count = 0;
                                            if ($cash_amt > 0) $methods_count++;
                                            if ($card_amt > 0) $methods_count++;
                                            if ($bank_amt > 0) $methods_count++;
                                            
                                            if ($methods_count > 1) {
                                                echo 'Mixed Payment';
                                            } elseif ($cash_amt > 0) {
                                                echo 'Cash';
                                            } elseif ($card_amt > 0) {
                                                echo 'Card';
                                            } elseif ($bank_amt > 0) {
                                                echo 'Bank Transfer';
                                            } else {
                                                echo 'Cash'; // Default fallback
                                            }
                                        } else {
                                            echo ucwords(str_replace('_', ' ', $payment_method));
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if ($sale['cash_amount'] > 0): ?>
                                <tr>
                                    <td><strong>Cash Amount:</strong></td>
                                    <td class="text-right">LKR <?php echo number_format($sale['cash_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($sale['card_amount'] > 0): ?>
                                <tr>
                                    <td><strong>Card Amount:</strong></td>
                                    <td class="text-right">LKR <?php echo number_format($sale['card_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($sale['bank_transfer_amount'] > 0): ?>
                                <tr>
                                    <td><strong>Bank Transfer:</strong></td>
                                    <td class="text-right">LKR <?php echo number_format($sale['bank_transfer_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($sale['change_given'] > 0): ?>
                                <tr>
                                    <td><strong>Change Given:</strong></td>
                                    <td class="text-right">LKR <?php echo number_format($sale['change_given'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <?php if (!empty($sale['notes'])): ?>
                    <hr>
                    <h5>Notes</h5>
                    <div class="well">
                        <?php echo nl2br(htmlspecialchars($sale['notes'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Action Buttons -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Actions</strong>
            </div>
            <div class="panel-body">
                <div class="btn-group-vertical btn-block">
                    <a href="generate_walkin_sales_invoice.php?id=<?php echo $sale['id']; ?>" 
                       class="btn btn-info" target="_blank">
                        <i class="glyphicon glyphicon-file"></i> Generate Invoice
                    </a>
                    <a href="physical_sales_history.php" class="btn btn-default">
                        <i class="glyphicon glyphicon-arrow-left"></i> Back to History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
