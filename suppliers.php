<?php
  $page_title = 'All Suppliers';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);
  $status = isset($_GET['status']) ? $_GET['status'] : 'all';
  $search = isset($_GET['search']) ? trim($_GET['search']) : '';
  $sql = "SELECT * FROM suppliers";
  $where = [];
  if ($status === 'active') {
    $where[] = "status = '1'";
  } elseif ($status === 'inactive') {
    $where[] = "status = '0'";
  }
  if (!empty($search)) {
    $search_esc = $db->escape($search);
    $where[] = "(name LIKE '%$search_esc%' OR contact_person LIKE '%$search_esc%' OR phone LIKE '%$search_esc%' OR email LIKE '%$search_esc%' OR address LIKE '%$search_esc%')";
  }
  if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
  }
  $suppliers_result = $db->query($sql);
  $suppliers = [];
  if ($suppliers_result) {
    while ($row = $suppliers_result->fetch_assoc()) {
      $suppliers[] = $row;
    }
  }
?>
<?php include_once('layouts/header.php'); ?>
  <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-list"></span>
          <span>All Suppliers</span>
       </strong>
        </div>
        <div class="panel-body">
          <form method="GET" class="form-horizontal user-search-bar" style="margin-bottom: 25px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
            <div class="row">
              <div class="col-md-3" style="padding-right: 15px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="search" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Search Suppliers</label>
                  <div class="has-feedback">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search suppliers..." value="<?php echo htmlspecialchars($search); ?>">
                    <span class="glyphicon glyphicon-search form-control-feedback"></span>
                  </div>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 10px; padding-right: 30px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="status" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Status</label>
                  <select class="form-control" id="status" name="status">
                    <option value="all" <?php if($status==='all') echo 'selected'; ?>>All Statuses</option>
                    <option value="active" <?php if($status==='active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if($status==='inactive') echo 'selected'; ?>>Inactive</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3" style="padding-left: 10px; padding-right: 10px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                      <i class="glyphicon glyphicon-filter"></i> Apply
                    </button>
                    <?php if (!empty($search) || $status !== 'all'): ?>
                      <a href="suppliers.php" class="btn btn-default btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                        <i class="glyphicon glyphicon-remove"></i> Clear
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4" style="padding-left: 10px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <div style="display: flex; gap: 8px; margin-top: 30px;">
                    <a href="export_suppliers.php?type=csv" class="btn btn-success btn-sm" style="flex: 1; padding: 8px 12px; background-color: #28a745; border-color: #28a745;">
                      <span class="glyphicon glyphicon-download-alt"></span> Export CSV
                    </a>
                    <a href="export_suppliers.php?type=pdf" class="btn btn-danger btn-sm" style="flex: 1; padding: 8px 12px;">
                      <span class="glyphicon glyphicon-file"></span> Export PDF
                    </a>
                    <a href="add_supplier.php" class="btn btn-success btn-sm" style="flex: 1; padding: 8px 12px;">
                      <span class="glyphicon glyphicon-plus"></span> Add New
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <?php if (!empty($search) || $status !== 'all'): ?>
              <div class="row" style="margin-top: 10px;">
                <div class="col-md-12">
                  <span class="text-muted" style="font-size: 13px;">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    <?php 
                      $active_filters = [];
                      if (!empty($search)) $active_filters[] = "Search: \"$search\"";
                      if ($status === 'active') $active_filters[] = "Status: Active";
                      if ($status === 'inactive') $active_filters[] = "Status: Inactive";
                      if (!empty($active_filters)) {
                        echo "Active filters: " . implode(', ', $active_filters);
                      }
                    ?>
                  </span>
                </div>
              </div>
            <?php endif; ?>
          </form>
          <table class="table table-bordered table-striped user-table">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th> Supplier Name </th>
                <th> Contact Person </th>
                <th> Phone </th>
                <th> Email </th>
                <th> Address </th>
                <th> Status </th>
                <th class="text-center" style="width: 100px;"> Actions </th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($suppliers)): ?>
                <?php foreach ($suppliers as $supplier):?>
                <tr>
                  <td class="text-center"><?php echo count_id();?></td>
                  <td><?php echo remove_junk($supplier['name']); ?></td>
                  <td><?php echo remove_junk($supplier['contact_person']); ?></td>
                  <td><?php echo remove_junk($supplier['phone']); ?></td>
                  <td><?php echo remove_junk($supplier['email']); ?></td>
                  <td><?php echo remove_junk($supplier['address']); ?></td>
                  <td class="text-center">
                    <?php if($supplier['status'] === '1'): ?>
                      <span class="badge user-role-badge" style="background:#45CB85;">Active</span>
                    <?php else: ?>
                      <span class="badge user-role-badge" style="background:#ed5153;">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group">
                      <a href="edit_supplier.php?id=<?php echo (int)$supplier['id'];?>" class="btn btn-info btn-sm"  title="Edit" data-toggle="tooltip">
                        <span class="glyphicon glyphicon-edit"></span>
                      </a>
                      <button class="btn btn-danger btn-sm delete-supplier-btn" data-id="<?php echo (int)$supplier['id']; ?>" data-name="<?php echo htmlspecialchars($supplier['name']); ?>" title="Delete" data-toggle="tooltip">
                        <span class="glyphicon glyphicon-trash"></span>
                      </button>
                    </div>
                  </td>
                </tr>
               <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center">No suppliers found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteSupplierModal" tabindex="-1" role="dialog" aria-labelledby="deleteSupplierModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="deleteSupplierModalLabel">Confirm Delete</h4>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the supplier <strong id="supplierName"></strong>?</p>
          <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDeleteSupplierBtn" class="btn btn-danger">Delete Supplier</a>
        </div>
      </div>
    </div>
  </div>
  
  <script>
  // Modal delete logic
  document.addEventListener('DOMContentLoaded', function() {
    var deleteButtons = document.querySelectorAll('.delete-supplier-btn');
    var confirmBtn = document.getElementById('confirmDeleteSupplierBtn');
    var supplierName = document.getElementById('supplierName');
    var supplierId = null;
    
    deleteButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        supplierId = btn.getAttribute('data-id');
        var name = btn.getAttribute('data-name');
        supplierName.textContent = name;
        $('#deleteSupplierModal').modal('show');
      });
    });
    
    confirmBtn.addEventListener('click', function() {
      if (supplierId) {
        window.location.href = 'delete_suppliers.php?id=' + supplierId;
      }
    });
  });
  </script>
  
<?php include_once('layouts/footer.php'); ?> 