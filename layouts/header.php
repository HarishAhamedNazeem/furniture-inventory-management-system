<?php $user = current_user() ?: []; ?>
<!DOCTYPE html>
  <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title><?php if (!empty($page_title))
           echo remove_junk($page_title);
            elseif(!empty($user))
           echo ucfirst($user['name']);
            else echo "Inventory Management System";?>
    </title>
    <!-- Use local resources first to avoid CORB issues -->
    <link rel="stylesheet" href="libs/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="libs/css/datepicker3.min.css"/>
    <!-- CDN fallbacks -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" onerror="this.onerror=null;"/>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker3.min.css" onerror="this.onerror=null;"/>
    <link rel="stylesheet" href="libs/css/main.css" />
    <!-- Preload critical fonts -->
    <link rel="preload" href="libs/fonts/glyphicons-halflings-regular.woff2" as="font" type="font/woff2" crossorigin>
    <!-- Bootstrap font override -->
    <link rel="stylesheet" href="libs/css/bootstrap-fonts.css" />
    <!-- Font Awesome for icons - use local first -->
    <link rel="stylesheet" href="libs/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" onerror="this.onerror=null;"/>
    
    <!-- Preload jQuery to ensure it's available -->
    <script>
        // Simple jQuery fallback loader
        if (typeof jQuery === 'undefined') {
            var script = document.createElement('script');
            script.src = 'libs/js/jquery.min.js';
            script.async = false;
            document.head.appendChild(script);
        }
    </script>
  </head>
  <body<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? ' class="login-body"' : '' ?>>
  <?php  if ($session->isUserLoggedIn(true)): ?>
    <header id="header">
      <div class="logo pull-left">
        <img src="libs/images/SW.png" alt="SW Logo" class="sw-logo">
        <span class="logo-text">SWISS WOODOWORKS</span>
      </div>
      <div class="header-content">
      <div class="header-date pull-left">
        <strong><?php echo date("F j, Y, g:i a");?></strong>
      </div>
      <div class="pull-right clearfix">
        <ul class="info-menu list-inline list-unstyled">
          <li class="profile">
            <a href="#" data-toggle="dropdown" class="toggle" aria-expanded="false">
              <?php if (!empty($user) && !empty($user['image'])): ?>
              <img src="uploads/users/<?php echo $user['image']; ?>" alt="user-image" class="img-circle img-inline">
              <?php endif; ?>
              <span><?php echo remove_junk(ucfirst(isset($user['name']) ? $user['name'] : 'User')); ?> <i class="caret"></i></span>
            </a>
            <ul class="dropdown-menu">
              <li>
                  <a href="profile.php?id=<?php echo (int) (isset($user['id']) ? $user['id'] : 0); ?>">
                      <i class="glyphicon glyphicon-user"></i>
                      Profile
                  </a>
              </li>
             <li>
                 <a href="edit_account.php" title="edit account">
                     <i class="glyphicon glyphicon-cog"></i>
                     Settings
                 </a>
             </li>
             <li class="last">
                 <a href="logout.php">
                     <i class="glyphicon glyphicon-off"></i>
                     Logout
                 </a>
             </li>
           </ul>
          </li>
        </ul>
      </div>
     </div>
    </header>
    <div class="sidebar">
      <?php if (isset($user['user_level']) && $user['user_level'] === '1'): ?>
        <!-- admin menu -->
      <?php include_once(__DIR__ . '/admin_menu.php');?>

      <?php elseif (isset($user['user_level']) && $user['user_level'] === '2'): ?>
        <!-- Special user -->
      <?php if (file_exists(__DIR__ . '/special_menu.php')) {
        include_once(__DIR__ . '/special_menu.php');
      } else {
        include_once(__DIR__ . '/admin_menu.php');
      } ?>

      <?php elseif (isset($user['user_level']) && $user['user_level'] === '3'): ?>
        <!-- User menu -->
      <?php if (file_exists(__DIR__ . '/user_menu.php')) {
        include_once(__DIR__ . '/user_menu.php');
      } else {
        include_once(__DIR__ . '/admin_menu.php');
      } ?>

      <?php endif;?>

   </div>
<?php endif;?>

<div class="page">
  <div class="container-fluid">
