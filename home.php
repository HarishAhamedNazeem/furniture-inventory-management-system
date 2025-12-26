<?php
/*
|--------------------------------------------------------------------------
| Home Page - Regular User Dashboard
|--------------------------------------------------------------------------
| Main dashboard for regular users (user_level 3)
| Author: Assistant
| Version: 1.0
|
*/

ob_start();
require_once('includes/load.php');

// Check if user is logged in
if(!$session->isUserLoggedIn(true)) { 
    redirect('index.php', false);
}

$user = current_user();
$user_level = $user['user_level'];

// Redirect admin and special users to their respective pages
if($user_level === '1') {
    redirect('admin.php', false);
} elseif($user_level === '2') {
    redirect('special.php', false);
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>
                    <span class="glyphicon glyphicon-th"></span>
                    <span>Welcome, <?php echo $user['name']; ?>!</span>
                </strong>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Employee Dashboard</h2>
                        <p>Welcome to the Inventory Management System. You are logged in as a regular user.</p>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Quick Actions</h3>
                                    </div>
                                    <div class="panel-body">
                                        <ul class="list-group">
                                            <li class="list-group-item">
                                                <a href="product.php">
                                                    <i class="glyphicon glyphicon-list"></i> View Products
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="stock_report.php">
                                                    <i class="glyphicon glyphicon-stats"></i> Stock Report
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="profile.php">
                                                    <i class="glyphicon glyphicon-user"></i> My Profile
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">System Information</h3>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong>User Level:</strong> Regular User</p>
                                        <p><strong>Last Login:</strong> <?php echo $user['last_login']; ?></p>
                                        <p><strong>Account Status:</strong> Active</p>
                                        
                                        <hr>
                                        
                                        <h4>Available Features:</h4>
                                        <ul>
                                            <li>View product inventory</li>
                                            <li>Generate stock reports</li>
                                            <li>Update personal profile</li>
                                            <li>Change password</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
