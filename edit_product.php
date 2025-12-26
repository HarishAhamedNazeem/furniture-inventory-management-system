<?php
  $page_title = 'Edit product';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(2);
?>
<?php
$product = find_by_id('products',(int)$_GET['id']);
$all_categories = find_all('categories');
$all_suppliers = find_all('suppliers');
if(!$product){
  $session->msg("d","Missing product id.");
  redirect('product.php');
}

// Get current product image info
$current_image = isset($product['image']) ? $product['image'] : null;
?>
<?php
 if(isset($_POST['product'])){
    $req_fields = array('product-title','product-categorie','product-quantity','buying-price', 'saleing-price' );
    validate_fields($req_fields);

   if(empty($errors)){
       $p_name  = remove_junk($db->escape($_POST['product-title']));
       $p_desc  = $db->escape($_POST['product-description']);
       $p_cat   = (int)$_POST['product-categorie'];
       $p_supplier = isset($_POST['product-supplier']) && !empty($_POST['product-supplier']) ? (int)$_POST['product-supplier'] : null;
       $p_qty   = remove_junk($db->escape($_POST['product-quantity']));
       $p_buy   = remove_junk($db->escape($_POST['buying-price']));
       $p_sale  = remove_junk($db->escape($_POST['saleing-price']));
       
       $query   = "UPDATE products SET";
       $query  .=" name ='{$p_name}', description=" . ($p_desc ? "'{$p_desc}'" : "NULL") . ", quantity ='{$p_qty}',";
       $query  .=" buy_price ='{$p_buy}', sale_price ='{$p_sale}', categorie_id ='{$p_cat}', supplier_id = " . ($p_supplier ? "'{$p_supplier}'" : "NULL");
       $query  .=" WHERE id ='{$product['id']}'";
       $result = $db->query($query);
       
       if($result){
         // Handle multiple image uploads
         if(isset($_FILES['product-images']) && !empty($_FILES['product-images']['name'][0])) {
           $primary_index = isset($_POST['primary-image']) ? (int)$_POST['primary-image'] : 0;
           $upload_result = handle_multiple_image_upload($product['id'], $_FILES, $primary_index);
           
           if (!empty($upload_result['errors'])) {
             $session->msg('w', 'Product updated but some images failed to upload: ' . implode(', ', $upload_result['errors']));
           } else if (!empty($upload_result['files'])) {
             $session->msg('s', "Product updated successfully with " . count($upload_result['files']) . " new image(s)!");
           } else {
             $session->msg('s', "Product updated successfully!");
           }
         } else {
           $session->msg('s',"Product updated successfully!");
         }
         
         redirect('product.php', false);
       } else {
         $session->msg('d',' Sorry failed to update!');
         redirect('edit_product.php?id='.$product['id'], false);
       }

   } else{
       $session->msg("d", $errors);
       redirect('edit_product.php?id='.$product['id'], false);
   }

 }

?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>
  <div class="row">
      <div class="panel panel-default">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Edit Product</span>
         </strong>
        </div>
        <div class="panel-body">
         <div class="col-md-7">
           <form method="post" action="edit_product.php?id=<?php echo (int)$product['id'] ?>" enctype="multipart/form-data">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="product-title" value="<?php echo remove_junk($product['name']);?>">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-align-left"></i>
                  </span>
                  <textarea class="form-control" name="product-description" placeholder="Product Description (Optional)" rows="3"><?php echo isset($product['description']) ? htmlspecialchars($product['description']) : ''; ?></textarea>
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-4">
                    <label for="product-categorie">Product Category *</label>
                    <select class="form-control" name="product-categorie" id="product-categorie" required>
                      <option value="">Select a category</option>
                      <?php foreach ($all_categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php if($product['categorie_id'] === $cat['id']): echo "selected"; endif; ?>>
                          <?php echo remove_junk($cat['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label for="product-supplier">Supplier (Optional)</label>
                    <select class="form-control" name="product-supplier" id="product-supplier">
                      <option value="">Select Supplier (Optional)</option>
                      <?php foreach ($all_suppliers as $supplier): ?>
                        <option value="<?php echo (int)$supplier['id']; ?>" <?php if(isset($product['supplier_id']) && $product['supplier_id'] == $supplier['id']): echo "selected"; endif; ?>>
                          <?php echo remove_junk($supplier['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="product-images">Add More Images:</label>
                      <input type="file" class="form-control" name="product-images[]" id="product-images" accept="image/*" multiple onchange="previewImages(this)">
                      <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP (Max: 2MB each). You can select multiple images.</small>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Current Images Display -->
              <?php 
              $current_images = get_product_images($product['id']);
              if (!empty($current_images)): ?>
              <div class="form-group">
                <label>Current Images:</label>
                <div class="row">
                  <?php foreach ($current_images as $index => $img): ?>
                  <div class="col-md-3 mb-3">
                    <div class="thumbnail" style="max-width: 150px;">
                      <img src="uploads/products/<?php echo $img['image_filename']; ?>" alt="Product Image <?php echo $index + 1; ?>" style="max-width: 100%; height: 120px; object-fit: cover;">
                      <div class="caption text-center">
                        <small class="text-muted">
                          <?php if ($img['is_primary']): ?>
                            <span class="badge badge-primary">Primary</span>
                          <?php endif; ?>
                          <?php echo $img['image_filename']; ?>
                        </small>
                        <br>
                        <a href="delete_product_image.php?image_id=<?php echo $img['id']; ?>&product_id=<?php echo $product['id']; ?>" 
                           class="btn btn-danger btn-xs" 
                           onclick="return confirm('Are you sure you want to delete this image?')">
                          <i class="glyphicon glyphicon-trash"></i> Delete
                        </a>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
              
              <!-- New Image Preview -->
              <div class="form-group" id="image-preview-container" style="display: none;">
                <label>New Image Preview:</label>
                <div id="image-preview-grid" class="row">
                  <!-- Images will be dynamically added here -->
                </div>
                <div class="form-group mt-3">
                  <label>Select Primary Image for New Images:</label>
                  <div id="primary-image-selection">
                    <!-- Radio buttons will be dynamically added here -->
                  </div>
                </div>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-4">
                  <div class="form-group">
                    <label for="qty">Quantity</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                       <i class="glyphicon glyphicon-shopping-cart"></i>
                      </span>
                      <input type="number" class="form-control" name="product-quantity" value="<?php echo remove_junk($product['quantity']); ?>">
                   </div>
                  </div>
                 </div>
                 <div class="col-md-4">
                  <div class="form-group">
                    <label for="qty">Buying price per unit</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                        <i class="glyphicon glyphicon-usd"></i>
                      </span>
                      <input type="number" class="form-control" name="buying-price" value="<?php echo remove_junk($product['buy_price']);?>">
                      <span class="input-group-addon">.00</span>
                   </div>
                  </div>
                 </div>
                  <div class="col-md-4">
                   <div class="form-group">
                     <label for="qty">Selling price per unit</label>
                     <div class="input-group">
                       <span class="input-group-addon">
                         <i class="glyphicon glyphicon-usd"></i>
                       </span>
                       <input type="number" class="form-control" name="saleing-price" value="<?php echo remove_junk($product['sale_price']);?>">
                       <span class="input-group-addon">.00</span>
                    </div>
                   </div>
                  </div>
               </div>
              </div>
              <button type="submit" name="product" class="btn btn-danger">Update</button>
          </form>
         </div>
        </div>
      </div>
  </div>

<?php include_once('layouts/footer.php'); ?>

<script>
function previewImages(input) {
    const previewContainer = document.getElementById('image-preview-container');
    const previewGrid = document.getElementById('image-preview-grid');
    const primarySelection = document.getElementById('primary-image-selection');
    
    // Clear previous previews
    previewGrid.innerHTML = '';
    primarySelection.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        previewContainer.style.display = 'block';
        
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Create image preview
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-3 mb-3';
                colDiv.innerHTML = `
                    <div class="thumbnail" style="max-width: 150px;">
                        <img src="${e.target.result}" alt="Preview ${i + 1}" style="max-width: 100%; height: 120px; object-fit: cover;">
                        <div class="caption text-center">
                            <small class="text-muted">${file.name}</small>
                        </div>
                    </div>
                `;
                previewGrid.appendChild(colDiv);
                
                // Create primary image radio button
                const radioDiv = document.createElement('div');
                radioDiv.className = 'form-check form-check-inline';
                radioDiv.innerHTML = `
                    <input class="form-check-input" type="radio" name="primary-image" id="primary-${i}" value="${i}" ${i === 0 ? 'checked' : ''}>
                    <label class="form-check-label" for="primary-${i}">
                        Image ${i + 1}
                    </label>
                `;
                primarySelection.appendChild(radioDiv);
            };
            
            reader.readAsDataURL(file);
        }
    } else {
        previewContainer.style.display = 'none';
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const fileInput = document.getElementById('product-images');
    
    // File size validation
    fileInput.addEventListener('change', function() {
        const files = this.files;
        const maxSize = 2 * 1024 * 1024; // 2MB
        const maxFiles = 10; // Maximum 10 images
        
        if (files.length > maxFiles) {
            alert(`Maximum ${maxFiles} images allowed`);
            this.value = '';
            document.getElementById('image-preview-container').style.display = 'none';
            return;
        }
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.size > maxSize) {
                alert(`File "${file.name}" is too large (max 2MB)`);
                this.value = '';
                document.getElementById('image-preview-container').style.display = 'none';
                return;
            }
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const title = document.querySelector('input[name="product-title"]').value.trim();
        const category = document.querySelector('select[name="product-categorie"]').value;
        const quantity = document.querySelector('input[name="product-quantity"]').value.trim();
        const buyPrice = document.querySelector('input[name="buying-price"]').value.trim();
        const salePrice = document.querySelector('input[name="saleing-price"]').value.trim();
        
        if (!title || !category || !quantity || !buyPrice || !salePrice) {
            e.preventDefault();
            alert('Please fill in all required fields');
            return false;
        }
        
        if (parseFloat(buyPrice) < 0 || parseFloat(salePrice) < 0) {
            e.preventDefault();
            alert('Prices cannot be negative');
            return false;
        }
        
        if (parseInt(quantity) < 0) {
            e.preventDefault();
            alert('Quantity cannot be negative');
            return false;
        }
    });
});
</script>
