<?php
  $page_title = 'All categories';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  
  // Get search query if exists
  $search = isset($_GET['search']) ? trim($_GET['search']) : '';
  
  // Fetch categories with search if provided
  if (!empty($search)) {
    $sql = "SELECT * FROM categories WHERE name LIKE '%{$db->escape($search)}%' ORDER BY name ASC";
    $categories_result = $db->query($sql);
    $all_categories = [];
    if ($categories_result) {
      while ($row = $categories_result->fetch_assoc()) {
        $all_categories[] = $row;
      }
    }
  } else {
    $all_categories = find_all('categories');
  }
?>
<?php
 if(isset($_POST['add_cat'])){
  $req_field = array('categorie-name');
   validate_fields($req_field);
   $cat_name = remove_junk($db->escape($_POST['categorie-name']));
   if(empty($errors)){
     $sql  = "INSERT INTO categories (name) VALUES ('{$cat_name}')";
      if($db->query($sql)){
        $session->msg("s", "Successfully Added New Category");
        redirect('categorie.php',false);
      } else {
        $session->msg("d", "Sorry Failed to insert.");
        redirect('categorie.php',false);
      }
   } else {
     $session->msg("d", $errors);
     redirect('categorie.php',false);
   }
 }
?>
<?php include_once('layouts/header.php'); ?>

  <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
    <div class="row">
    <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-list"></span>
          <span>All Categories</span>
       </strong>
      </div>
      <div class="panel-body">
        <!-- Search Form -->
        <form method="GET" class="form-horizontal user-search-bar" style="margin-bottom: 25px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
          <div class="row">
            <div class="col-md-3" style="padding-right: 25px;">
              <div class="form-group" style="margin-bottom: 15px;">
                <label for="category-search" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Search Categories</label>
                <div class="has-feedback">
                  <input type="text" class="form-control" id="category-search" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>">
                  <span class="glyphicon glyphicon-search form-control-feedback"></span>
                </div>
              </div>
            </div>
            <div class="col-md-3" style="padding-left: 10px; padding-right: 10px;">
              <div class="form-group" style="margin-bottom: 15px;">
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                  <button type="submit" class="btn btn-primary btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                    <i class="glyphicon glyphicon-search"></i> Search
                  </button>
                  <?php if (!empty($search)): ?>
                    <a href="categorie.php" class="btn btn-default btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                      <i class="glyphicon glyphicon-remove"></i> Clear
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="col-md-6" style="padding-left: 10px;">
              <div class="form-group" style="margin-bottom: 15px;">
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                  <a href="export_categories.php?type=csv" class="btn btn-success btn-sm" style="flex: 1; padding: 8px 12px; background-color: #28a745; border-color: #28a745;">
                    <span class="glyphicon glyphicon-download-alt"></span> Export CSV
                  </a>
                  <a href="export_categories.php?type=pdf" class="btn btn-danger btn-sm" style="flex: 1; padding: 8px 12px;">
                    <span class="glyphicon glyphicon-file"></span> Export PDF
                  </a>
                  <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addCategoryModal" style="flex: 1; padding: 8px 12px;">
                    <i class="glyphicon glyphicon-plus"></i> Add New
                  </button>
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
        <table class="table table-bordered table-striped user-table">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th>Categories</th>
                <th class="text-center" style="width: 100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_categories as $cat):?>
                <tr>
                  <td class="text-center"><?php echo count_id();?></td>
                  <td><span class="badge user-role-badge" style="background:#e74c3c;"><?php echo remove_junk(ucfirst($cat['name'])); ?></span></td>
                  <td class="text-center">
                    <div class="btn-group">
                      <a href="edit_categorie.php?id=<?php echo (int)$cat['id'];?>"  class="btn btn-warning btn-sm" data-toggle="tooltip" title="Edit">
                        <span class="glyphicon glyphicon-edit"></span>
                      </a>
                      <button class="btn btn-danger btn-sm delete-category-btn" data-id="<?php echo (int)$cat['id']; ?>" data-name="<?php echo htmlspecialchars($cat['name']); ?>" data-toggle="tooltip" title="Remove">
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
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog" aria-labelledby="deleteCategoryModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h4>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the category <strong id="categoryName"></strong>?</p>
          <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDeleteCategoryBtn" class="btn btn-danger">Delete Category</a>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Add Category Modal -->
  <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="addCategoryLabel">Add New Category</h4>
        </div>
        <div class="modal-body">
          <form method="post" action="categorie.php" id="addCategoryForm">
            <div class="form-group has-feedback">
              <label>Category Name</label>
              <input type="text" class="form-control" name="categorie-name" placeholder="Category Name" required>
              <span class="glyphicon glyphicon-tag form-control-feedback"></span>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" name="add_cat" class="btn btn-success">Save Category</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <script>
  // Modal delete logic
  document.addEventListener('DOMContentLoaded', function() {
    var deleteButtons = document.querySelectorAll('.delete-category-btn');
    var confirmBtn = document.getElementById('confirmDeleteCategoryBtn');
    var categoryName = document.getElementById('categoryName');
    var categoryId = null;
    
    deleteButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        categoryId = btn.getAttribute('data-id');
        var name = btn.getAttribute('data-name');
        categoryName.textContent = name;
        $('#deleteCategoryModal').modal('show');
      });
    });
    
    confirmBtn.addEventListener('click', function() {
      if (categoryId) {
        window.location.href = 'delete_categorie.php?id=' + categoryId;
      }
    });
  });
  </script>
  
  <?php include_once('layouts/footer.php'); ?>
