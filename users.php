<?php
  $page_title = 'All User';
  require_once('includes/load.php');
?>
<?php
// Checkin What level user has permission to view this page
 page_require_level(1);

// Get search query if exists
$search = isset($_GET['search']) ? $_GET['search'] : '';

//pull out all user form database
$all_users = find_all_user();

// Filter users if search query exists
if (!empty($search)) {
    $all_users = array_filter($all_users, function($user) use ($search) {
        return (
            stripos($user['name'], $search) !== false ||
            stripos($user['username'], $search) !== false ||
            stripos($user['group_name'], $search) !== false
        );
    });
}
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
   <div class="col-md-12">
     <?php echo display_msg($msg); ?>
   </div>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Users</span>
       </strong>
      </div>
     <div class="panel-body">
      <!-- Search Form -->
      <form method="GET" class="form-horizontal user-search-bar" style="margin-bottom: 25px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
        <div class="row">
          <div class="col-md-6" style="padding-right: 20px;">
            <div class="form-group" style="margin-bottom: 15px;">
              <label for="user-search" class="control-label" style="font-weight: 600; margin-bottom: 8px;">Search Users</label>
              <div class="has-feedback">
                <input type="text" class="form-control" id="user-search" name="search" placeholder="Search by name, username, or role..." value="<?php echo htmlspecialchars($search); ?>">
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
              </div>
            </div>
          </div>
          <div class="col-md-6" style="padding-left: 15px;">
            <div class="form-group" style="margin-bottom: 15px;">
              <div style="display: flex; gap: 10px; margin-top: 30px; justify-content: space-between;">
                <div style="display: flex; gap: 10px;">
                  <button type="submit" class="btn btn-primary btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                    <i class="glyphicon glyphicon-search"></i> Search
                  </button>
                  <?php if (!empty($search)): ?>
                    <a href="users.php" class="btn btn-default btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                      <i class="glyphicon glyphicon-remove"></i> Clear
                    </a>
                  <?php endif; ?>
                </div>
                <div>
                  <a href="add_user.php" class="btn btn-success btn-sm" style="flex: 0 0 auto; padding: 8px 16px;">
                    <i class="glyphicon glyphicon-plus"></i> Add New User
                  </a>
                </div>
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
            <th>Avatar</th>
            <th>Name </th>
            <th>Username</th>
            <th class="text-center" style="width: 15%;">User Role</th>
            <th class="text-center" style="width: 10%;">Status</th>
            <th style="width: 20%;">Last Login</th>
            <th class="text-center" style="width: 100px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($all_users as $a_user): ?>
          <tr>
           <td class="text-center"><?php echo count_id();?></td>
           <td class="text-center">
             <?php if (!empty($a_user['avatar'])): ?>
               <img src="uploads/users/<?php echo $a_user['avatar']; ?>" class="img-avatar user-avatar" alt="Avatar">
             <?php else: ?>
               <div class="user-initials-avatar"><?php echo strtoupper(substr($a_user['name'],0,1)); ?></div>
             <?php endif; ?>
           </td>
           <td><?php echo remove_junk(ucwords($a_user['name']))?></td>
           <td><?php echo remove_junk(ucwords($a_user['username']))?></td>
           <td class="text-center">
             <span class="badge user-role-badge role-<?php echo strtolower($a_user['group_name']); ?>">
               <?php echo remove_junk(ucwords($a_user['group_name']))?>
             </span>
           </td>
           <td class="text-center">
           <?php if($a_user['status'] === '1'): ?>
            <span class="label label-success">Active</span>
          <?php else: ?>
            <span class="label label-danger">Deactive</span>
          <?php endif;?>
           </td>
           <td><?php echo read_date($a_user['last_login'])?></td>
           <td class="text-center">
             <div class="btn-group">
                <a href="edit_user.php?id=<?php echo (int)$a_user['id'];?>" class="btn btn-warning btn-sm" data-toggle="tooltip" title="Edit">
                  <span class="glyphicon glyphicon-edit"></span>
               </a>
                <button class="btn btn-danger btn-sm delete-user-btn" data-id="<?php echo (int)$a_user['id'];?>" data-toggle="tooltip" title="Remove">
                  <span class="glyphicon glyphicon-trash"></span>
                </button>
                </div>
           </td>
          </tr>
        <?php endforeach;?>
       </tbody>
     </table>
     <!-- Delete Confirmation Modal -->
     <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel">
       <div class="modal-dialog" role="document">
         <div class="modal-content">
           <div class="modal-header">
             <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
             <h4 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h4>
           </div>
           <div class="modal-body">
             Are you sure you want to delete this user?
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
             <a href="#" id="confirmDeleteUserBtn" class="btn btn-danger">Delete</a>
           </div>
         </div>
       </div>
     </div>
     <script>
     // Modal delete logic
     document.addEventListener('DOMContentLoaded', function() {
       var deleteButtons = document.querySelectorAll('.delete-user-btn');
       var confirmBtn = document.getElementById('confirmDeleteUserBtn');
       var userId = null;
       deleteButtons.forEach(function(btn) {
         btn.addEventListener('click', function() {
           userId = btn.getAttribute('data-id');
           $('#deleteUserModal').modal('show');
         });
       });
       confirmBtn.addEventListener('click', function() {
         if (userId) {
           window.location.href = 'delete_user.php?id=' + userId;
         }
       });
     });
     </script>
     </div>
    </div>
  </div>
</div>
  <?php include_once('layouts/footer.php'); ?>
