<?php
  $page_title = 'Promotions Management';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  
  // Get search query if exists
  $search = isset($_GET['search']) ? trim($_GET['search']) : '';
  
  // Fetch promotions with search if provided
  if (!empty($search)) {
    $sql = "SELECT * FROM promotions WHERE name LIKE '%{$db->escape($search)}%' OR promo_code LIKE '%{$db->escape($search)}%' ORDER BY created_at DESC";
    $all_promotions = $db->query($sql);
  } else {
    $sql = "SELECT * FROM promotions ORDER BY created_at DESC";
    $all_promotions = $db->query($sql);
  }
  
  // Get categories for promotion assignment
  $categories = find_all('categories');
  $products = find_all('products');
?>
<?php
 if(isset($_POST['add_promotion'])){
  $req_field = array('promotion-name', 'discount-type', 'discount-value', 'start-date', 'end-date');
   validate_fields($req_field);
   
   $promo_name = remove_junk($db->escape($_POST['promotion-name']));
   $description = remove_junk($db->escape($_POST['description']));
   $promo_code = remove_junk($db->escape($_POST['promo-code']));
   $discount_type = remove_junk($db->escape($_POST['discount-type']));
   $discount_value = (float)$_POST['discount-value'];
   $min_order_amount = (float)$_POST['min-order-amount'];
   $max_discount_amount = !empty($_POST['max-discount-amount']) ? (float)$_POST['max-discount-amount'] : null;
   $usage_limit = !empty($_POST['usage-limit']) ? (int)$_POST['usage-limit'] : null;
   $start_date = $_POST['start-date'];
   $end_date = $_POST['end-date'];
   $applies_to = $_POST['applies-to'];
   $is_active = isset($_POST['is-active']) ? 1 : 0;
   
   if(empty($errors)){
     $sql = "INSERT INTO promotions (name, description, promo_code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, start_date, end_date, applies_to, is_active, created_at, updated_at) 
             VALUES ('{$promo_name}', '{$description}', '{$promo_code}', '{$discount_type}', {$discount_value}, {$min_order_amount}, " . ($max_discount_amount ? $max_discount_amount : 'NULL') . ", " . ($usage_limit ? $usage_limit : 'NULL') . ", '{$start_date}', '{$end_date}', '{$applies_to}', {$is_active}, NOW(), NOW())";
     
     if($db->query($sql)){
       $promotion_id = $db->insert_id();
       
       // Handle category-specific promotions
       if($applies_to === 'specific_categories' && isset($_POST['categories'])){
         foreach($_POST['categories'] as $category_id){
           $cat_sql = "INSERT INTO promotion_categories (promotion_id, category_id) VALUES ({$promotion_id}, " . (int)$category_id . ")";
           $db->query($cat_sql);
         }
       }
       
       // Handle product-specific promotions
       if($applies_to === 'specific_products' && isset($_POST['products'])){
         foreach($_POST['products'] as $product_id){
           $prod_sql = "INSERT INTO promotion_products (promotion_id, product_id) VALUES ({$promotion_id}, " . (int)$product_id . ")";
           $db->query($prod_sql);
         }
       }
       
       $session->msg("s", "Successfully Added New Promotion");
       redirect('promotions.php',false);
     } else {
       $session->msg("d", "Sorry Failed to insert.");
       redirect('promotions.php',false);
     }
   } else {
     $session->msg("d", $errors);
     redirect('promotions.php',false);
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
            <span class="glyphicon glyphicon-gift"></span>
            <span>Promotions Management</span>
         </strong>
          <div class="pull-right">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addPromotionModal">Add New Promotion +</button>
          </div>
        </div>
        <div class="panel-body">
          <!-- Search Form -->
          <form method="GET" class="form-horizontal user-search-bar" style="margin-bottom: 20px;">
            <div class="form-group">
              <label for="search-input" class="control-label">Search Promotions</label>
              <div class="input-group">
                <input type="text" class="form-control" id="search-input" name="search" placeholder="Search promotions..." value="<?php echo htmlspecialchars($search); ?>">
                <span class="input-group-btn">
                  <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-search"></i> Search</button>
                </span>
              </div>
            </div>
            <?php if (!empty($search)): ?>
              <a href="promotions.php" class="btn btn-default" style="margin-left:10px;">Clear</a>
            <?php endif; ?>
          </form>
          
          <table class="table table-bordered table-striped user-table">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th>Promotion Name</th>
                <th>Promo Code</th>
                <th>Discount</th>
                <th>Valid Period</th>
                <th>Status</th>
                <th class="text-center" style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($promo = $db->fetch_assoc($all_promotions)): ?>
                <tr>
                  <td class="text-center"><?php echo count_id(); ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($promo['name']); ?></strong>
                    <?php if($promo['description']): ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($promo['description']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($promo['promo_code']): ?>
                      <span class="label label-info"><?php echo htmlspecialchars($promo['promo_code']); ?></span>
                    <?php else: ?>
                      <span class="text-muted">No code</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($promo['discount_type'] === 'percentage'): ?>
                      <?php echo $promo['discount_value']; ?>%
                    <?php else: ?>
                      LKR <?php echo number_format($promo['discount_value'], 2); ?>
                    <?php endif; ?>
                    <?php if($promo['min_order_amount'] > 0): ?>
                      <br><small class="text-muted">Min: LKR <?php echo number_format($promo['min_order_amount'], 2); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <small>
                      <?php echo date('M d, Y', strtotime($promo['start_date'])); ?><br>
                      to <?php echo date('M d, Y', strtotime($promo['end_date'])); ?>
                    </small>
                  </td>
                  <td>
                    <?php if($promo['is_active']): ?>
                      <span class="label label-success">Active</span>
                    <?php else: ?>
                      <span class="label label-default">Inactive</span>
                    <?php endif; ?>
                    <?php if($promo['usage_limit']): ?>
                      <br><small class="text-muted"><?php echo $promo['used_count']; ?>/<?php echo $promo['usage_limit']; ?> used</small>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group">
                      <a href="edit_promotion.php?id=<?php echo (int)$promo['id']; ?>" class="btn btn-warning btn-sm" data-toggle="tooltip" title="Edit">
                        <span class="glyphicon glyphicon-edit"></span>
                      </a>
                      <button class="btn btn-danger btn-sm delete-promotion-btn" data-id="<?php echo (int)$promo['id']; ?>" data-name="<?php echo htmlspecialchars($promo['name']); ?>" data-toggle="tooltip" title="Remove">
                        <span class="glyphicon glyphicon-trash"></span>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
       </div>
    </div>
   </div>
  </div>
  
  <!-- Add Promotion Modal -->
  <div class="modal fade" id="addPromotionModal" tabindex="-1" role="dialog" aria-labelledby="addPromotionLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="addPromotionLabel">Add New Promotion</h4>
        </div>
        <div class="modal-body">
          <form method="post" action="promotions.php" id="addPromotionForm">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Promotion Name *</label>
                  <input type="text" class="form-control" name="promotion-name" placeholder="Promotion Name" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Promo Code</label>
                  <input type="text" class="form-control" name="promo-code" placeholder="Optional promo code">
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label>Description</label>
              <textarea class="form-control" name="description" rows="2" placeholder="Promotion description"></textarea>
            </div>
            
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Discount Type *</label>
                  <select class="form-control" name="discount-type" required>
                    <option value="">Select Type</option>
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed_amount">Fixed Amount (LKR)</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Discount Value *</label>
                  <input type="number" class="form-control" name="discount-value" step="0.01" min="0" placeholder="0.00" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Max Discount Amount</label>
                  <input type="number" class="form-control" name="max-discount-amount" step="0.01" min="0" placeholder="Optional">
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Minimum Order Amount</label>
                  <input type="number" class="form-control" name="min-order-amount" step="0.01" min="0" value="0">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Usage Limit</label>
                  <input type="number" class="form-control" name="usage-limit" min="1" placeholder="Optional">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Applies To</label>
                  <select class="form-control" name="applies-to">
                    <option value="all_products">All Products</option>
                    <option value="specific_categories">Specific Categories</option>
                    <option value="specific_products">Specific Products</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Start Date *</label>
                  <input type="datetime-local" class="form-control" name="start-date" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>End Date *</label>
                  <input type="datetime-local" class="form-control" name="end-date" required>
                </div>
              </div>
            </div>
            
            <div class="form-group" id="categories-section" style="display:none;">
              <label>Select Categories</label>
              <div class="row">
                <?php foreach($categories as $cat): ?>
                  <div class="col-md-4">
                    <label class="checkbox-inline">
                      <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"> <?php echo htmlspecialchars($cat['name']); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            
            <div class="form-group" id="products-section" style="display:none;">
              <label>Select Products</label>
              <div class="row">
                <?php foreach($products as $prod): ?>
                  <div class="col-md-6">
                    <label class="checkbox-inline">
                      <input type="checkbox" name="products[]" value="<?php echo $prod['id']; ?>"> <?php echo htmlspecialchars($prod['name']); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            
            <div class="form-group">
              <label class="checkbox-inline">
                <input type="checkbox" name="is-active" checked> Active
              </label>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" name="add_promotion" class="btn btn-success">Save Promotion</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deletePromotionModal" tabindex="-1" role="dialog" aria-labelledby="deletePromotionModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="deletePromotionModalLabel">Confirm Delete</h4>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the promotion <strong id="promotionName"></strong>?</p>
          <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDeletePromotionBtn" class="btn btn-danger">Delete Promotion</a>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const appliesToSelect = document.querySelector('select[name="applies-to"]');
      const categoriesSection = document.getElementById('categories-section');
      const productsSection = document.getElementById('products-section');
      
      appliesToSelect.addEventListener('change', function() {
        categoriesSection.style.display = this.value === 'specific_categories' ? 'block' : 'none';
        productsSection.style.display = this.value === 'specific_products' ? 'block' : 'none';
      });
      
      // Modal delete logic
      var deleteButtons = document.querySelectorAll('.delete-promotion-btn');
      var confirmBtn = document.getElementById('confirmDeletePromotionBtn');
      var promotionName = document.getElementById('promotionName');
      var promotionId = null;
      
      deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
          promotionId = btn.getAttribute('data-id');
          var name = btn.getAttribute('data-name');
          promotionName.textContent = name;
          $('#deletePromotionModal').modal('show');
        });
      });
      
      confirmBtn.addEventListener('click', function() {
        if (promotionId) {
          window.location.href = 'delete_promotion.php?id=' + promotionId;
        }
      });
    });
  </script>
  
  <?php include_once('layouts/footer.php'); ?>
