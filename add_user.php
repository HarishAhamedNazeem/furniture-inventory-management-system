<?php
  $page_title = 'Add User';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  $groups = find_all('user_groups');
?>
<?php
  if(isset($_POST['add_user'])){

   $req_fields = array('full-name','username','password','level' );
   validate_fields($req_fields);

   if(empty($errors)){
           $name   = remove_junk($db->escape($_POST['full-name']));
       $username   = remove_junk($db->escape($_POST['username']));
       $password   = remove_junk($db->escape($_POST['password']));
       $user_level = (int)$db->escape($_POST['level']);
       $password = sha1($password);
        $query = "INSERT INTO users (";
        $query .="name,username,password,user_level,status";
        $query .=") VALUES (";
        $query .=" '{$name}', '{$username}', '{$password}', '{$user_level}','1'";
        $query .=")";
        if($db->query($query)){
          //sucess
          $session->msg('s',"User account has been creted! ");
          redirect('add_user.php', false);
        } else {
          //failed
          $session->msg('d',' Sorry failed to create account!');
          redirect('add_user.php', false);
        }
   } else {
     $session->msg("d", $errors);
      redirect('add_user.php',false);
   }
 }
?>
<?php include_once('layouts/header.php'); ?>
  <?php echo display_msg($msg); ?>
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <div class="panel panel-default user-form-panel">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Add New User</span>
         </strong>
        </div>
        <div class="panel-body">
          <form method="post" action="add_user.php">
            <div class="form-group has-feedback">
                <label for="name">Name</label>
                <input type="text" class="form-control" name="full-name" placeholder="Full Name">
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <label for="username">Username</label>
                <input type="text" class="form-control" name="username" placeholder="Username">
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <label for="password">Password</label>
                <input type="password" class="form-control" name ="password" id="password" placeholder="Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                <div id="password-strength" style="margin-top:5px;"></div>
            </div>
            <div class="form-group">
              <label for="level">User Role</label>
                <select class="form-control" name="level">
                  <?php foreach ($groups as $group ):?>
                   <option value="<?php echo $group['group_level'];?>"><?php echo ucwords($group['group_name']);?></option>
                <?php endforeach;?>
                </select>
            </div>
            <div class="form-group clearfix">
              <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
              <a href="users.php" class="btn btn-default" style="margin-left: 10px;">Cancel</a>
            </div>
        </form>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Password strength meter
    document.addEventListener('DOMContentLoaded', function() {
      var password = document.getElementById('password');
      var strength = document.getElementById('password-strength');
      if(password) {
        password.addEventListener('input', function() {
          var val = password.value;
          var score = 0;
          if (val.length > 5) score++;
          if (val.match(/[A-Z]/)) score++;
          if (val.match(/[0-9]/)) score++;
          if (val.match(/[^A-Za-z0-9]/)) score++;
          var msg = '';
          var color = '';
          switch(score) {
            case 0:
            case 1: msg = 'Weak'; color = 'red'; break;
            case 2: msg = 'Medium'; color = 'orange'; break;
            case 3: msg = 'Strong'; color = 'green'; break;
            case 4: msg = 'Very Strong'; color = 'darkgreen'; break;
          }
          strength.textContent = msg;
          strength.style.color = color;
        });
      }
    });
  </script>
<?php include_once('layouts/footer.php'); ?>
