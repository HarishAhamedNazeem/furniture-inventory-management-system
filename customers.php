<?php
require_once('includes/load.php');
require_once('includes/db.php');
require_once('layouts/header.php');

// Get customer type and search parameters
$customer_type = isset($_GET['type']) ? $_GET['type'] : 'online'; // Default to online
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query based on customer type using registration_type field
if ($customer_type === 'walkin') {
    // For walk-in customers, get data from customers table where registration_type = 'walkin'
    $sql = "SELECT c.id, c.name, c.email, c.phone, c.address, c.created_at 
            FROM customers c 
            WHERE c.status = 1 AND c.registration_type = 'walkin'";
} else {
    // For online customers, get data from customers table where registration_type = 'online'
    $sql = "SELECT c.id, c.name, c.email, c.phone, c.address, c.created_at 
            FROM customers c 
            WHERE c.status = 1 AND c.registration_type = 'online'";
}

// Add search filters
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $sql .= " AND (c.name LIKE '%$search_esc%' 
                OR c.email LIKE '%$search_esc%' 
                OR c.phone LIKE '%$search_esc%' 
                OR c.address LIKE '%$search_esc%')";
}

$sql .= " ORDER BY created_at ASC";
$result = $conn->query($sql);

?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>
                    <span class="glyphicon glyphicon-user"></span>
                    <span>Customer Management</span>
                </strong>
            </div>
            <div class="panel-body">
                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong>Success!</strong> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong>Error!</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Customer Type Selection Buttons -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-12 text-center">
                        <div class="btn-group" role="group" aria-label="Customer Type Selection">
                            <a href="customers.php?type=online" class="btn <?php echo $customer_type === 'online' ? 'btn-primary' : 'btn-default'; ?> btn-lg">
                                <span class="glyphicon glyphicon-globe"></span>
                                Online Customers
                            </a>
                            <a href="customers.php?type=walkin" class="btn <?php echo $customer_type === 'walkin' ? 'btn-primary' : 'btn-default'; ?> btn-lg">
                                <span class="glyphicon glyphicon-shopping-cart"></span>
                                Walk-in Customers
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Search Form -->
                <form method="GET" class="form-horizontal" style="margin-bottom: 25px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($customer_type); ?>">
                    <div class="row">
                        <div class="col-md-4" style="padding-right: 20px;">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="customer-search" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Search Customers</label>
                                <div class="has-feedback">
                                    <input type="text" class="form-control" id="customer-search" name="search" placeholder="Search by name, email, phone, or address..." value="<?php echo htmlspecialchars($search); ?>">
                                    <span class="glyphicon glyphicon-search form-control-feedback"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4" style="padding-left: 15px; padding-right: 15px;">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <div style="display: flex; gap: 10px; margin-top: 30px;">
                                    <button type="submit" class="btn btn-primary btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                                        <i class="glyphicon glyphicon-search"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="customers.php?type=<?php echo $customer_type; ?>" class="btn btn-default btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                                            <i class="glyphicon glyphicon-remove"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4" style="padding-left: 15px;">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <div style="display: flex; gap: 10px; margin-top: 30px;">
                                    <a href="export_customers.php?type=csv&customer_type=<?php echo $customer_type; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-success btn-sm" style="flex: 1; padding: 8px 12px; background-color: #28a745; border-color: #28a745;">
                                        <span class="glyphicon glyphicon-download-alt"></span> Export CSV
                                    </a>
                                    <a href="export_customers.php?type=pdf&customer_type=<?php echo $customer_type; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-danger btn-sm" style="flex: 1; padding: 8px 12px;">
                                        <span class="glyphicon glyphicon-file"></span> Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($search)): ?>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-12">
                                <span class="text-muted" style="font-size: 13px;">
                                    <i class="glyphicon glyphicon-info-sign"></i>
                                    Active filter: Search: "<?php echo htmlspecialchars($search); ?>"
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
                <!-- Customer Type Info -->
                <div class="alert alert-info">
                    <strong>
                        <?php if ($customer_type === 'online'): ?>
                            <span class="glyphicon glyphicon-globe"></span> Online Customers
                            <small>- Customers who have placed orders through the website</small>
                        <?php else: ?>
                            <span class="glyphicon glyphicon-shopping-cart"></span> Walk-in Customers  
                            <small>- Customers who have made purchases at physical store locations</small>
                        <?php endif; ?>
                    </strong>
                </div>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0) : ?>
                            <?php $count = 1; ?>
                            <?php while ($customer = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td class="text-center"><?php echo $count++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-xs delete-customer-btn" data-id="<?php echo (int)$customer['id']; ?>" data-type="<?php echo $customer_type; ?>" data-name="<?php echo htmlspecialchars($customer['name']); ?>" title="Delete Customer" data-toggle="tooltip">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="alert alert-warning">
                                        <strong>No <?php echo $customer_type === 'online' ? 'online' : 'walk-in'; ?> customers found.</strong>
                                        <?php if ($customer_type === 'online'): ?>
                                            <br>Online customers are those who have placed orders through the website.
                                        <?php else: ?>
                                            <br>Walk-in customers are those who have made purchases at physical store locations.
                                        <?php endif; ?>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" role="dialog" aria-labelledby="deleteCustomerModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deleteCustomerModalLabel">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the customer <strong id="customerName"></strong>?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteCustomerBtn" class="btn btn-danger">Delete Customer</a>
            </div>
        </div>
    </div>
</div>

<script>
// Modal delete logic
document.addEventListener('DOMContentLoaded', function() {
    var deleteButtons = document.querySelectorAll('.delete-customer-btn');
    var confirmBtn = document.getElementById('confirmDeleteCustomerBtn');
    var customerName = document.getElementById('customerName');
    var customerId = null;
    var customerType = null;
    
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            customerId = btn.getAttribute('data-id');
            customerType = btn.getAttribute('data-type');
            var name = btn.getAttribute('data-name');
            customerName.textContent = name;
            $('#deleteCustomerModal').modal('show');
        });
    });
    
    confirmBtn.addEventListener('click', function() {
        if (customerId && customerType) {
            window.location.href = 'delete_customer.php?id=' + customerId + '&type=' + customerType;
        }
    });
});
</script>

<style>
/* Customer Management Styles */
.btn-group .btn-lg {
    margin: 0 5px;
    min-width: 180px;
}

.customer-type-info {
    margin-bottom: 20px;
}

.table th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.table td {
    vertical-align: middle;
}

.label {
    font-size: 11px;
}

.btn-group .btn-xs {
    margin: 1px;
}

.alert-info {
    border-left: 4px solid #5bc0de;
}

.alert-warning {
    border-left: 4px solid #f0ad4e;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group .btn-lg {
        min-width: 150px;
        font-size: 14px;
    }
    
    .form-inline .form-group {
        margin-bottom: 10px;
    }
    
    .pull-right {
        float: none !important;
        margin-top: 10px;
    }
    
    .table-responsive {
        border: none;
    }
}

/* Customer type specific styling */
.online-customer-row {
    border-left: 3px solid #5bc0de;
}

.walkin-customer-row {
    border-left: 3px solid #f0ad4e;
}
</style>

<?php require_once('layouts/footer.php'); ?> 