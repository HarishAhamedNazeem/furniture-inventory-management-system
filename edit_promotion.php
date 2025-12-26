<?php
  $page_title = 'Edit Promotion';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
?>
<?php
  //Display promotion details
  $promotion = find_by_id('promotions',(int)$_GET['id']);
  if(!$promotion){
    $session->msg("d","Missing promotion id.");
    redirect('promotions.php');
  }
  
  // Get categories and products for selection
  $categories = find_all('categories');
  $products = find_all('products');
  
  // Get selected categories and products
  $selected_categories = [];
  $selected_products = [];
  
  if($promotion['applies_to'] === 'specific_categories'){
    $cat_sql = "SELECT category_id FROM promotion_categories WHERE promotion_id = " . (int)$promotion['id'];
    $cat_result = $db->query($cat_sql);
    while($row = $db->fetch_assoc($cat_result)){
      $selected_categories[] = $row['category_id'];
    }
  }
  
  if($promotion['applies_to'] === 'specific_products'){
    $prod_sql = "SELECT product_id FROM promotion_products WHERE promotion_id = " . (int)$promotion['id'];
    $prod_result = $db->query($prod_sql);
    while($row = $db->fetch_assoc($prod_result)){
      $selected_products[] = $row['product_id'];
    }
  }
?>
<?php
if(isset($_POST['edit_promotion'])){
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
    $sql = "UPDATE promotions SET 
            name='{$promo_name}', 
            description='{$description}', 
            promo_code='{$promo_code}', 
            discount_type='{$discount_type}', 
            discount_value={$discount_value}, 
            min_order_amount={$min_order_amount}, 
            max_discount_amount=" . ($max_discount_amount ? $max_discount_amount : 'NULL') . ", 
            usage_limit=" . ($usage_limit ? $usage_limit : 'NULL') . ", 
            start_date='{$start_date}', 
            end_date='{$end_date}', 
            applies_to='{$applies_to}', 
            is_active={$is_active}, 
            updated_at=NOW() 
            WHERE id=" . (int)$promotion['id'];
    
    if($db->query($sql)){
      // Delete existing category/product associations
      $db->query("DELETE FROM promotion_categories WHERE promotion_id = " . (int)$promotion['id']);
      $db->query("DELETE FROM promotion_products WHERE promotion_id = " . (int)$promotion['id']);
      
      // Add new category associations
      if($applies_to === 'specific_categories' && isset($_POST['categories'])){
        foreach($_POST['categories'] as $category_id){
          $cat_sql = "INSERT INTO promotion_categories (promotion_id, category_id) VALUES (" . (int)$promotion['id'] . ", " . (int)$category_id . ")";
          $db->query($cat_sql);
        }
      }
      
      // Add new product associations
      if($applies_to === 'specific_products' && isset($_POST['products'])){
        foreach($_POST['products'] as $product_id){
          $prod_sql = "INSERT INTO promotion_products (promotion_id, product_id) VALUES (" . (int)$promotion['id'] . ", " . (int)$product_id . ")";
          $db->query($prod_sql);
        }
      }
      
      $session->msg("s", "Successfully updated Promotion");
      redirect('promotions.php',false);
    } else {
      $session->msg("d", "Sorry! Failed to Update");
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
       <div class="panel-heading">
         <strong>
           <span class="glyphicon glyphicon-gift"></span>
           <span>Editing <?php echo remove_junk(ucfirst($promotion['name']));?></span>
        </strong>
       </div>
       <div class="panel-body">
         <form method="post" action="edit_promotion.php?id=<?php echo (int)$promotion['id'];?>">
           <div class="row">
             <div class="col-md-6">
               <div class="form-group">
                 <label>Promotion Name *</label>
                 <input type="text" class="form-control" name="promotion-name" value="<?php echo htmlspecialchars($promotion['name']); ?>" required>
               </div>
             </div>
             <div class="col-md-6">
               <div class="form-group">
                 <label>Promo Code</label>
                 <input type="text" class="form-control" name="promo-code" value="<?php echo htmlspecialchars($promotion['promo_code']); ?>">
               </div>
             </div>
           </div>
           
           <div class="form-group">
             <label>Description</label>
             <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($promotion['description']); ?></textarea>
           </div>
           
           <div class="row">
             <div class="col-md-4">
               <div class="form-group">
                 <label>Discount Type *</label>
                 <select class="form-control" name="discount-type" required>
                   <option value="percentage" <?php echo $promotion['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                   <option value="fixed_amount" <?php echo $promotion['discount_type'] === 'fixed_amount' ? 'selected' : ''; ?>>Fixed Amount (LKR)</option>
                 </select>
               </div>
             </div>
             <div class="col-md-4">
               <div class="form-group">
                 <label>Discount Value *</label>
                 <input type="number" class="form-control" name="discount-value" step="0.01" min="0" value="<?php echo $promotion['discount_value']; ?>" required>
               </div>
             </div>
             <div class="col-md-4">
               <div class="form-group">
                 <label>Max Discount Amount</label>
                 <input type="number" class="form-control" name="max-discount-amount" step="0.01" min="0" value="<?php echo $promotion['max_discount_amount']; ?>">
               </div>
             </div>
           </div>
           
           <div class="row">
             <div class="col-md-4">
               <div class="form-group">
                 <label>Minimum Order Amount</label>
                 <input type="number" class="form-control" name="min-order-amount" step="0.01" min="0" value="<?php echo $promotion['min_order_amount']; ?>">
               </div>
             </div>
             <div class="col-md-4">
               <div class="form-group">
                 <label>Usage Limit</label>
                 <input type="number" class="form-control" name="usage-limit" min="1" value="<?php echo $promotion['usage_limit']; ?>">
               </div>
             </div>
             <div class="col-md-4">
               <div class="form-group">
                 <label>Applies To</label>
                 <select class="form-control" name="applies-to" id="applies-to">
                   <option value="all_products" <?php echo $promotion['applies_to'] === 'all_products' ? 'selected' : ''; ?>>All Products</option>
                   <option value="specific_categories" <?php echo $promotion['applies_to'] === 'specific_categories' ? 'selected' : ''; ?>>Specific Categories</option>
                   <option value="specific_products" <?php echo $promotion['applies_to'] === 'specific_products' ? 'selected' : ''; ?>>Specific Products</option>
                 </select>
               </div>
             </div>
           </div>
           
           <div class="row">
             <div class="col-md-6">
               <div class="form-group">
                 <label>Start Date *</label>
                 <input type="datetime-local" class="form-control" name="start-date" value="<?php echo date('Y-m-d\TH:i', strtotime($promotion['start_date'])); ?>" required>
               </div>
             </div>
             <div class="col-md-6">
               <div class="form-group">
                 <label>End Date *</label>
                 <input type="datetime-local" class="form-control" name="end-date" value="<?php echo date('Y-m-d\TH:i', strtotime($promotion['end_date'])); ?>" required>
               </div>
             </div>
           </div>
           
           <div class="form-group" id="categories-section" style="<?php echo $promotion['applies_to'] === 'specific_categories' ? '' : 'display:none;'; ?>">
             <label>Select Categories</label>
             <div class="row">
               <?php foreach($categories as $cat): ?>
                 <div class="col-md-4">
                   <label class="checkbox-inline">
                     <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" <?php echo in_array($cat['id'], $selected_categories) ? 'checked' : ''; ?>> <?php echo htmlspecialchars($cat['name']); ?>
                   </label>
                 </div>
               <?php endforeach; ?>
             </div>
           </div>
           
           <div class="form-group" id="products-section" style="<?php echo $promotion['applies_to'] === 'specific_products' ? '' : 'display:none;'; ?>">
             <label>Select Products</label>
             <div class="row">
               <?php foreach($products as $prod): ?>
                 <div class="col-md-6">
                   <label class="checkbox-inline">
                     <input type="checkbox" name="products[]" value="<?php echo $prod['id']; ?>" <?php echo in_array($prod['id'], $selected_products) ? 'checked' : ''; ?>> <?php echo htmlspecialchars($prod['name']); ?>
                   </label>
                 </div>
               <?php endforeach; ?>
             </div>
           </div>
           
           <div class="form-group">
             <label class="checkbox-inline">
               <input type="checkbox" name="is-active" <?php echo $promotion['is_active'] ? 'checked' : ''; ?>> Active
             </label>
           </div>
           
           <button type="submit" name="edit_promotion" class="btn btn-primary">Update Promotion</button>
       </form>
       </div>
     </div>
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const appliesToSelect = document.getElementById('applies-to');
  const categoriesSection = document.getElementById('categories-section');
  const productsSection = document.getElementById('products-section');
  
  appliesToSelect.addEventListener('change', function() {
    categoriesSection.style.display = this.value === 'specific_categories' ? 'block' : 'none';
    productsSection.style.display = this.value === 'specific_products' ? 'block' : 'none';
  });
});
</script>

<?php include_once('layouts/footer.php'); ?>
