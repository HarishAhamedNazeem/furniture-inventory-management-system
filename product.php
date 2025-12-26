<?php
  $page_title = 'All Product';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(2);

  // Get search query and filters
  $search = isset($_GET['search']) ? trim($_GET['search']) : '';
  $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
  $supplier_filter = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;
  $stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';
  
  // Get all categories and suppliers for filter dropdowns
  $categories = find_all('categories');
  $suppliers = find_all('suppliers');
  
  // Check if categories have parent-child relationships
  $has_parent = false;
  foreach ($categories as $cat) {
    if (isset($cat['parent_id']) && $cat['parent_id'] !== null) {
      $has_parent = true;
      break;
    }
  }
  
  // Build the SQL query with filters
  $sql = "SELECT p.*, c.name as categorie, s.name as supplier_name 
          FROM products p 
          LEFT JOIN categories c ON p.categorie_id = c.id 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE 1=1";
  
  if (!empty($search)) {
    $sql .= " AND (p.name LIKE '%{$db->escape($search)}%' OR c.name LIKE '%{$db->escape($search)}%' OR p.description LIKE '%{$db->escape($search)}%')";
  }
  
  if ($category_filter > 0) {
    $sql .= " AND p.categorie_id = {$category_filter}";
  }
  
  if ($supplier_filter > 0) {
    $sql .= " AND p.supplier_id = {$supplier_filter}";
  }
  
  if ($stock_filter === 'low') {
    $sql .= " AND p.quantity <= 10";
  } elseif ($stock_filter === 'out') {
    $sql .= " AND p.quantity = 0";
  }
  
  $sql .= " ORDER BY p.name ASC";
  $products_result = $db->query($sql);
  $products = [];
  if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
      $products[] = $row;
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
          <span>All Products</span>
       </strong>
        </div>
        <div class="panel-body">
          <!-- Search and Filter Form -->
          <form method="GET" class="form-horizontal user-search-bar" style="margin-bottom: 25px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
            <div class="row">
              <div class="col-md-2" style="padding-right: 10px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="search" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Search Products</label>
                  <div class="has-feedback">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <span class="glyphicon glyphicon-search form-control-feedback"></span>
                  </div>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 5px; padding-right: 5px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="category" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Category</label>
                  <select class="form-control" id="category" name="category">
                    <option value="0">All Categories</option>
                    <?php if ($has_parent): ?>
                      <?php 
                        $by_parent = [];
                        foreach ($categories as $c) {
                          $parentKey = isset($c['parent_id']) && $c['parent_id'] !== null ? (int)$c['parent_id'] : 0;
                          if (!isset($by_parent[$parentKey])) $by_parent[$parentKey] = [];
                          $by_parent[$parentKey][] = $c;
                        }
                        $main_list = isset($by_parent[0]) ? $by_parent[0] : [];
                      ?>
                      <?php foreach ($main_list as $main): ?>
                        <optgroup label="<?php echo htmlspecialchars($main['name']); ?>">
                          <?php if (isset($by_parent[(int)$main['id']])): ?>
                            <?php foreach ($by_parent[(int)$main['id']] as $sub): ?>
                              <option value="<?php echo (int)$sub['id']; ?>" <?php echo ($category_filter == $sub['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sub['name']); ?></option>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <option value="<?php echo (int)$main['id']; ?>" <?php echo ($category_filter == $main['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($main['name']); ?></option>
                          <?php endif; ?>
                        </optgroup>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <?php foreach($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                          <?php echo remove_junk($cat['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 5px; padding-right: 5px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="supplier" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Supplier</label>
                  <select class="form-control" id="supplier" name="supplier">
                    <option value="0">All Suppliers</option>
                    <?php foreach($suppliers as $supplier): ?>
                      <option value="<?php echo (int)$supplier['id']; ?>" <?php echo ($supplier_filter == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo remove_junk($supplier['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 5px; padding-right: 20px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="stock" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Stock Status</label>
                  <select class="form-control" id="stock" name="stock">
                    <option value="">All Stock</option>
                    <option value="low" <?php echo ($stock_filter === 'low') ? 'selected' : ''; ?>>Low Stock (≤10)</option>
                    <option value="out" <?php echo ($stock_filter === 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4" style="padding-left: 20px;">
                <div class="form-group" style="margin-bottom: 15px;">
                  <div style="display: flex; gap: 5px; margin-top: 30px; flex-wrap: nowrap;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex: 1; padding: 8px 12px;">
                      <i class="glyphicon glyphicon-filter"></i> Apply
                    </button>
                    <?php if (!empty($search) || $category_filter > 0 || $supplier_filter > 0 || !empty($stock_filter)) : ?>
                      <a href="product.php" class="btn btn-default btn-sm" style="flex: 1; padding: 8px 12px;">
                        <i class="glyphicon glyphicon-remove"></i> Clear
                      </a>
                    <?php endif; ?>
                    <a href="export_products.php?type=csv" class="btn btn-success btn-sm" style="flex: 1; padding: 8px 12px; background-color: #28a745; border-color: #28a745;">
                      <span class="glyphicon glyphicon-download-alt"></span> Export CSV
                    </a>
                    <a href="export_products.php?type=pdf" class="btn btn-danger btn-sm" style="flex: 1; padding: 8px 12px;">
                      <span class="glyphicon glyphicon-file"></span> Export PDF
                    </a>
                    <a href="add_product.php" class="btn btn-success btn-sm" style="flex: 1; padding: 8px 12px;">
                      <span class="glyphicon glyphicon-plus"></span> Add New Product
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <?php if (!empty($search) || $category_filter > 0 || $supplier_filter > 0 || !empty($stock_filter)) : ?>
              <div class="row" style="margin-top: 10px;">
                <div class="col-md-12">
                  <span class="text-muted" style="font-size: 13px;">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    <?php 
                      $active_filters = [];
                      if (!empty($search)) $active_filters[] = "Search: \"$search\"";
                      if ($category_filter > 0) {
                        $cat_name = '';
                        foreach($categories as $cat) {
                          if ($cat['id'] == $category_filter) {
                            $cat_name = $cat['name'];
                            break;
                          }
                        }
                        $active_filters[] = "Category: $cat_name";
                      }
                      if ($supplier_filter > 0) {
                        $sup_name = '';
                        foreach($suppliers as $sup) {
                          if ($sup['id'] == $supplier_filter) {
                            $sup_name = $sup['name'];
                            break;
                          }
                        }
                        $active_filters[] = "Supplier: $sup_name";
                      }
                      if ($stock_filter === 'low') $active_filters[] = "Stock: Low Stock";
                      if ($stock_filter === 'out') $active_filters[] = "Stock: Out of Stock";
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
                <th> Photo</th>
                <th> Product Title </th>
                <th> Description </th>
                <th class="text-center" style="width: 10%;"> Categories </th>
                <th class="text-center" style="width: 10%;"> Supplier </th>
                <th class="text-center" style="width: 10%;"> In-Stock </th>
                <th class="text-center" style="width: 10%;"> Buying Price (per unit) </th>
                <th class="text-center" style="width: 10%;"> Selling Price (per unit) </th>
                <th class="text-center" style="width: 10%;"> Product Added </th>
                <th class="text-center" style="width: 100px;"> Actions </th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product):?>
              <tr>
                <td class="text-center"><?php echo count_id();?></td>
                <td>
                  <?php 
                  // Get primary image from new system
                  $primary_image = get_primary_product_image($product['id']);
                  if ($primary_image): ?>
                    <img class="img-avatar img-circle user-avatar" src="uploads/products/<?php echo $primary_image['image_filename']; ?>" alt="">
                  <?php else: ?>
                    <img class="img-avatar img-circle user-avatar" src="uploads/products/no_image.png" alt="">
                  <?php endif; ?>
                </td>
                <td> <?php echo remove_junk($product['name']); ?></td>
                <td> 
                  <?php 
                    // Robust description handling
                    $description = '';
                    if (isset($product['description']) && $product['description'] !== null) {
                        $description = trim($product['description']);
                    }
                    
                    if (empty($description)) {
                      echo '<span class="text-muted">No description</span>';
                    } else {
                      // Truncate long descriptions for table display
                      $truncated = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                      echo '<span title="' . htmlspecialchars($description) . '">' . htmlspecialchars($truncated) . '</span>';
                    }
                  ?>
                </td>
                <td class="text-center">
                  <?php $fullCat = (isset($product['parent_name']) && $product['parent_name']) ? ($product['parent_name'].' › '.$product['categorie']) : $product['categorie']; ?>
                  <span class="badge user-role-badge" style="background:#e67e22;">&nbsp;<?php echo remove_junk($fullCat); ?>&nbsp;</span>
                </td>
                <td class="text-center">
                  <?php if(isset($product['supplier_name']) && !empty($product['supplier_name'])): ?>
                    <span class="badge user-role-badge" style="background:#3498db;">&nbsp;<?php echo remove_junk($product['supplier_name']); ?>&nbsp;</span>
                  <?php else: ?>
                    <span class="text-muted">No supplier</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if($product['quantity'] == 0): ?>
                    <span class="badge user-role-badge" style="background:#ed5153;">Out</span>
                  <?php elseif($product['quantity'] <= 10): ?>
                    <span class="badge user-role-badge" style="background:#f1c40f; color:#333;">Low (<?php echo $product['quantity']; ?>)</span>
                  <?php else: ?>
                    <span class="badge user-role-badge" style="background:#45CB85;">In (<?php echo $product['quantity']; ?>)</span>
                  <?php endif; ?>
                </td>
                <td class="text-center"> <?php echo remove_junk($product['buy_price']); ?></td>
                <td class="text-center"> <?php echo remove_junk($product['sale_price']); ?></td>
                <td class="text-center"> <?php echo read_date($product['date']); ?></td>
                <td class="text-center">
                  <div class="btn-group">
                    <a href="edit_product.php?id=<?php echo (int)$product['id'];?>" class="btn btn-info btn-sm"  title="Edit" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-edit"></span>
                    </a>
                    <button class="btn btn-danger btn-sm delete-product-btn" data-id="<?php echo (int)$product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" title="Delete" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-trash"></span>
                    </button>
                  </div>
                </td>
              </tr>
             <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteProductModal" tabindex="-1" role="dialog" aria-labelledby="deleteProductModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h4>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the product <strong id="productName"></strong>?</p>
          <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDeleteProductBtn" class="btn btn-danger">Delete Product</a>
        </div>
      </div>
    </div>
  </div>
  
  <script>
  // Modal delete logic
  document.addEventListener('DOMContentLoaded', function() {
    var deleteButtons = document.querySelectorAll('.delete-product-btn');
    var confirmBtn = document.getElementById('confirmDeleteProductBtn');
    var productName = document.getElementById('productName');
    var productId = null;
    
    deleteButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        productId = btn.getAttribute('data-id');
        var name = btn.getAttribute('data-name');
        productName.textContent = name;
        $('#deleteProductModal').modal('show');
      });
    });
    
    confirmBtn.addEventListener('click', function() {
      if (productId) {
        window.location.href = 'delete_product.php?id=' + productId;
      }
    });
  });
  </script>
  
  <?php include_once('layouts/footer.php'); ?>
