<?php
require_once('includes/load.php');
require_once('includes/db.php');
require_once('layouts/header.php');

// Check if order id is provided
if (!isset($_GET['id'])) {
    redirect('orders.php', false);
}

$order_id = (int)$_GET['id'];

// Fetch order details with more information
$order_sql = "SELECT o.*, c.name as customer_name, c.email, c.phone, c.address 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              WHERE o.id = ? LIMIT 1";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    redirect('orders.php', false);
}

// Fetch order items
$items_sql = "SELECT oi.*, p.name as product_name, p.sale_price 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

// Calculate totals
$subtotal = 0;
$item_count = 0;
while ($item = $order_items->fetch_assoc()) {
    $subtotal += $item['quantity'] * $item['price'];
    $item_count += $item['quantity'];
}
$order_items->data_seek(0); // Reset pointer for display

// Get order notes/history
$notes_sql = "SELECT notes FROM orders WHERE id = ?";
$notes_stmt = $conn->prepare($notes_sql);
$notes_stmt->bind_param("i", $order_id);
$notes_stmt->execute();
$notes_result = $notes_stmt->get_result();
$notes_data = $notes_result->fetch_assoc();
$order_notes = $notes_data['notes'] ?? '';

?>

<!-- Order Header -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-12">
        <div class="panel panel-default" style="border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="panel-heading" style="border-radius: 8px 8px 0 0;">
                <div class="row">
                    <div class="col-md-8">
                        <h3 style="margin: 0; font-weight: 600;">
                            <i class="fa fa-file-text-o"></i>
                            Order Details #<?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>
                        </h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">
                            <i class="fa fa-calendar"></i> 
                            <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <div style="margin-bottom: 10px;">
                            <span class="status-badge status-<?php echo $order['status']; ?>" style="padding: 8px 16px; border-radius: 25px; font-weight: bold; text-transform: uppercase; font-size: 12px;">
                                <i class="fa fa-<?php echo $order['status'] === 'pending' ? 'clock-o' : ($order['status'] === 'processing' ? 'cog' : ($order['status'] === 'delivered' ? 'check-circle' : ($order['status'] === 'cancelled' ? 'times-circle' : 'truck'))); ?>"></i>
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo $order['payment_status']; ?>" style="padding: 6px 12px; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 11px;">
                                <i class="fa fa-<?php echo $order['payment_status'] === 'paid' ? 'check-circle' : ($order['payment_status'] === 'failed' ? 'times-circle' : 'clock-o'); ?>"></i>
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Information Cards -->
<div class="row" style="margin-bottom: 20px;">
    <!-- Customer Information Card -->
    <div class="col-md-6">
        <div class="panel panel-default" style="border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 8px;">
            <div class="panel-heading" style="border-radius: 8px 8px 0 0;">
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="fa fa-user"></i> Customer Information
                </h4>
            </div>
            <div class="panel-body" style="padding: 20px;">
                <div class="customer-details">
                    <div style="margin-bottom: 15px;">
                        <strong style="color: #2c3e50; font-size: 16px;"><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <i class="fa fa-envelope" style="color: #3498db; width: 20px;"></i>
                        <span style="margin-left: 10px;"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <?php if (!empty($order['phone'])): ?>
                    <div style="margin-bottom: 10px;">
                        <i class="fa fa-phone" style="color: #3498db; width: 20px;"></i>
                        <span style="margin-left: 10px;"><?php echo htmlspecialchars($order['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['shipping_address'])): ?>
                    <div style="margin-bottom: 10px;">
                        <i class="fa fa-map-marker" style="color: #3498db; width: 20px; vertical-align: top;"></i>
                        <span style="margin-left: 10px; display: inline-block; max-width: calc(100% - 30px);">
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Summary Card -->
    <div class="col-md-6">
        <div class="panel panel-default" style="border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 8px;">
            <div class="panel-heading" style="border-radius: 8px 8px 0 0;">
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="fa fa-shopping-cart"></i> Order Summary
                </h4>
            </div>
            <div class="panel-body" style="padding: 20px;">
                <div class="order-summary">
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span><i class="fa fa-cube"></i> Total Items:</span>
                            <strong><?php echo $item_count; ?> items</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span><i class="fa fa-calendar"></i> Order Date:</span>
                            <strong><?php echo date('M d, Y', strtotime($order['created_at'])); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span><i class="fa fa-credit-card"></i> Payment Method:</span>
                            <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method']))); ?></strong>
                        </div>
                        <?php if (!empty($order['tracking_number'])): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span><i class="fa fa-truck"></i> Tracking:</span>
                            <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['estimated_delivery'])): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span><i class="fa fa-clock-o"></i> Est. Delivery:</span>
                            <strong><?php echo date('M d, Y', strtotime($order['estimated_delivery'])); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <hr style="margin: 15px 0;">
                    <div style="text-align: center;">
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Subtotal:</span>
                                    <strong>LKR <?php echo number_format($subtotal, 2); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="color: #27ae60;">Discount:</span>
                                    <strong style="color: #27ae60;">-LKR <?php echo number_format($order['discount_amount'], 2); ?></strong>
                                </div>
                                <hr style="margin: 10px 0;">
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 24px; font-weight: bold; color: #27ae60;">
                            LKR <?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <small class="text-muted">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Items Table -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default" style="border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 8px;">
            <div class="panel-heading" style="border-radius: 8px 8px 0 0;">
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="fa fa-list"></i> Order Items
                </h4>
            </div>
            <div class="panel-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-hover" style="margin-bottom: 0;">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th style="border: none; padding: 15px;"><i class="fa fa-cube"></i> Product</th>
                                <th style="border: none; padding: 15px; text-align: center;"><i class="fa fa-hashtag"></i> Quantity</th>
                                <th style="border: none; padding: 15px; text-align: right;"><i class="fa fa-money"></i> Unit Price</th>
                                <th style="border: none; padding: 15px; text-align: right;"><i class="fa fa-calculator"></i> Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($order_items && $order_items->num_rows > 0) : ?>
                                <?php while ($item = $order_items->fetch_assoc()) : ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 15px; vertical-align: middle;">
                                            <strong style="color: #2c3e50;"><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        </td>
                                        <td style="padding: 15px; text-align: center; vertical-align: middle;">
                                            <span class="badge" style="background-color: #3498db; font-size: 12px; padding: 6px 10px;">
                                                <?php echo htmlspecialchars($item['quantity']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px; text-align: right; vertical-align: middle;">
                                            <strong>LKR <?php echo number_format($item['price'], 2); ?></strong>
                                        </td>
                                        <td style="padding: 15px; text-align: right; vertical-align: middle;">
                                            <strong style="color: #27ae60; font-size: 16px;">LKR <?php echo number_format($item['quantity'] * $item['price'], 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="text-center" style="padding: 40px;">
                                        <div style="color: #7f8c8d;">
                                            <i class="fa fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px;"></i>
                                            <h4>No items found</h4>
                                            <p>This order doesn't have any items.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Notes/Timeline -->
<?php if (!empty($order_notes)): ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="panel panel-default" style="border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 8px;">
            <div class="panel-heading" style="border-radius: 8px 8px 0 0;">
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="fa fa-sticky-note"></i> Order Notes & Timeline
                </h4>
            </div>
            <div class="panel-body" style="padding: 20px;">
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #f39c12;">
                    <pre style="margin: 0; white-space: pre-wrap; font-family: inherit; font-size: 14px; line-height: 1.5;"><?php echo htmlspecialchars($order_notes); ?></pre>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="text-center">
            <a href="orders.php" class="btn btn-default btn-lg" style="margin-right: 10px;">
                <i class="fa fa-arrow-left"></i> Back to Orders
            </a>
            <?php if ($order['payment_status'] === 'paid'): ?>
                <a href="generate_order_invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-success btn-lg" target="_blank">
                    <i class="fa fa-file-pdf-o"></i> Generate Invoice
                </a>
            <?php endif; ?>
            <?php 
            // Debug: Show current values (remove this after testing)
            echo "<!-- Debug: Status=" . $order['status'] . ", Payment=" . $order['payment_status'] . " -->";
            
            // Hide edit button when order is completed (delivered or shipped) AND payment is paid, or when order is cancelled
            $hideEditButton = (($order['status'] === 'delivered' || $order['status'] === 'shipped') && $order['payment_status'] === 'paid') || $order['status'] === 'cancelled';
            ?>
            <?php if (!$hideEditButton): ?>
            <button type="button" class="btn btn-warning btn-lg edit-status" 
                    data-order-id="<?php echo (int)$order['id']; ?>"
                    data-order-number="<?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>"
                    data-current-status="<?php echo $order['status']; ?>"
                    data-current-payment-status="<?php echo htmlspecialchars($order['payment_status']); ?>"
                    style="margin-left: 10px;">
                <i class="fa fa-edit"></i> Edit Order
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Enhanced Order Edit Modal (same as in orders.php) -->
<div class="modal fade" id="editStatusModal" tabindex="-1" role="dialog" aria-labelledby="editStatusModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editStatusModalLabel">
                    <i class="fa fa-edit"></i> Edit Order Details
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="border-left: 4px solid #007bff;">
                            <h5><i class="fa fa-info-circle"></i> Order Management</h5>
                            <p>Update order status, payment information, and add internal notes for order <strong id="editOrderNumber"></strong>.</p>
                        </div>
                    </div>
                </div>
                
                <form id="editStatusesForm">
                    <input type="hidden" id="orderId" name="order_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status"><i class="fa fa-tasks"></i> Order Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_status"><i class="fa fa-credit-card"></i> Payment Status</label>
                                <select class="form-control" id="payment_status" name="payment_status">
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="trackingNumber"><i class="fa fa-truck"></i> Tracking Number</label>
                                <input type="text" class="form-control" id="trackingNumber" name="tracking_number" placeholder="Enter tracking number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="carrier"><i class="fa fa-shipping-fast"></i> Carrier</label>
                                <input type="text" class="form-control" id="carrier" name="carrier" placeholder="Enter carrier name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="editNotes"><i class="fa fa-sticky-note"></i> Internal Notes</label>
                                <textarea class="form-control" id="editNotes" name="notes" rows="3" placeholder="Add internal notes about this order..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="customerNotification"><i class="fa fa-bell"></i> Customer Notification Message</label>
                                <textarea class="form-control" id="customerNotification" name="customer_message" rows="2" placeholder="Message to send to customer (optional)..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="sendCustomerEmail" name="send_email" checked>
                                        <i class="fa fa-envelope"></i> Send email notification to customer
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveStatuses">
                    <i class="fa fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced status badges for view_order.php */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-pending {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.status-processing {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.status-shipped {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.status-delivered {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.status-cancelled {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.status-paid {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.status-failed {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

/* Enhanced panel styling */
.panel {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.panel-heading {
    border-radius: 8px 8px 0 0;
    border: none;
}

/* Enhanced button styling */
.btn-lg {
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Enhanced table styling */
.table {
    border-radius: 8px;
    overflow: hidden;
}

.table th {
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Enhanced form styling */
.form-control {
    border-radius: 6px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

/* Badge styling */
.badge {
    border-radius: 12px;
    font-weight: 600;
}
</style>

<script>
// Enhanced JavaScript for view_order.php
(function() {
    function waitForJQueryAndBootstrap() {
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is loaded successfully.');
            
            if (typeof $.fn.modal !== 'undefined') {
                console.log('Bootstrap is loaded successfully.');
                
                $(document).ready(function() {
                    console.log('jQuery document ready executed.');
                    
                    // Prevent form submission
                    $('#editStatusesForm').on('submit', function(e) {
                        e.preventDefault();
                        console.log('Form submission prevented');
                        return false;
                    });

                    // Handle edit statuses button click
                    $('.edit-status').on('click', function() {
                        var orderId = $(this).data('order-id');
                        var currentStatus = $(this).data('current-status');
                        var currentPaymentStatus = $(this).data('current-payment-status');
                        var orderNumber = $(this).data('order-number');
                        
                        // Populate basic fields
                        $('#editStatusModal #orderId').val(orderId);
                        $('#editStatusModal #status').val(currentStatus);
                        $('#editStatusModal #payment_status').val(currentPaymentStatus);
                        $('#editStatusModal #editOrderNumber').text(orderNumber);
                        
                        // Clear additional fields
                        $('#editStatusModal #trackingNumber').val('');
                        $('#editStatusModal #carrier').val('');
                        $('#editStatusModal #editNotes').val('');
                        $('#editStatusModal #customerNotification').val('');
                        $('#editStatusModal #sendCustomerEmail').prop('checked', true);
                        
                        // Show modal
                        $('#editStatusModal').modal('show');
                    });

                    // Handle save statuses button click
                    $('#saveStatuses').on('click', function() {
                        var orderId = $('#editStatusModal #orderId').val();
                        var newStatus = $('#editStatusModal #status').val();
                        var newPaymentStatus = $('#editStatusModal #payment_status').val();
                        var trackingNumber = $('#editStatusModal #trackingNumber').val();
                        var carrier = $('#editStatusModal #carrier').val();
                        var notes = $('#editStatusModal #editNotes').val();
                        var customerMessage = $('#editStatusModal #customerNotification').val();
                        var sendEmail = $('#editStatusModal #sendCustomerEmail').is(':checked');
                        
                        if (!orderId || !newStatus || !newPaymentStatus) {
                            alert('Error: Missing order ID or status/payment status');
                            return;
                        }

                        // Disable the save button to prevent double submission
                        var saveButton = $(this);
                        saveButton.prop('disabled', true);
                        saveButton.html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                        var formData = new FormData();
                        formData.append('update_statuses', true);
                        formData.append('order_id', orderId);
                        formData.append('status', newStatus);
                        formData.append('payment_status', newPaymentStatus);
                        formData.append('tracking_number', trackingNumber);
                        formData.append('carrier', carrier);
                        formData.append('notes', notes);
                        formData.append('customer_message', customerMessage);
                        formData.append('send_email', sendEmail);

                        // Send the request
                        fetch('update_statuses.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok, status: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(result => {
                            console.log('AJAX success callback executed.');
                            
                            if(result.success) {
                                // Hide edit button if order is cancelled
                                if (newStatus === 'cancelled') {
                                    $('.edit-status').hide();
                                }
                                
                                // Show success message
                                showMessage('Order status updated successfully!', 'success');
                                
                                // Use jQuery to hide the Bootstrap modal
                                $('#editStatusModal').modal('hide');
                                
                                // Reload the page to show updated information
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                throw new Error(result.error || 'Unknown error occurred');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error updating statuses: ' + error.message);
                        })
                        .finally(() => {
                            // Re-enable the save button
                            saveButton.prop('disabled', false);
                            saveButton.html('<i class="fa fa-save"></i> Save Changes');
                        });
                    });

                    // Message display function
                    function showMessage(message, type) {
                        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                        var messageHtml = '<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span>' +
                            '</button>' +
                            '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message +
                            '</div>';
                        
                        $('body').append(messageHtml);
                        
                        // Auto-dismiss after 5 seconds
                        setTimeout(function() {
                            $('.alert').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    }
                });
            } else {
                console.log('Bootstrap not loaded yet, retrying...');
                setTimeout(waitForJQueryAndBootstrap, 100);
            }
        } else {
            console.log('jQuery not loaded yet, retrying...');
            setTimeout(waitForJQueryAndBootstrap, 100);
        }
    }
    
    // Start waiting for jQuery and Bootstrap
    waitForJQueryAndBootstrap();
})();
</script>

<?php require_once('layouts/footer.php'); ?> 