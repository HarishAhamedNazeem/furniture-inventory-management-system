<?php
  ob_start();
  require_once('includes/load.php');
  if($session->isUserLoggedIn(true)) { redirect('admin.php', false);}
?>
<?php include_once('layouts/header.php'); ?>
<div class="login-page">
    <div class="text-center">
       <img src="libs/images/SW.png" alt="SWISS WOODWORKS Logo" style="max-width: 200px; margin-bottom: 20px;">
       <h4>Welcome!</h4>
       <!--<h2>SWISS WOODWORKS</h2>-->
       <!--<h4>Inventory Management System</h4>-->
       <h4>Sign in to continue</h4>
     </div>
     <?php echo display_msg($msg); ?>
      <form method="post" action="auth.php" class="clearfix">
        <div class="form-group">
              <label for="username" class="control-label">Username</label>
              <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" autocomplete="username" required>
        </div>
        <div class="form-group">
            <label for="password" class="control-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" autocomplete="current-password" required>
        </div>
        <div class="form-group">
                <button type="submit" class="btn btn-success" style="border-radius:5%">Login</button>
        </div>
    </form>
</div>
<?php include_once('layouts/footer.php'); ?>
