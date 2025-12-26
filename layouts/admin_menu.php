<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<ul>
  <li class="<?php echo $current_page === 'admin.php' ? 'active' : ''; ?>">
    <a href="admin.php">
      <i class="glyphicon glyphicon-home"></i>
      <span>Dashboard</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
    <a href='users.php'>
      <i class="glyphicon glyphicon-user"></i>
      <span>User Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
    <a href="customers.php" >
      <i class="glyphicon glyphicon-user"></i>
      <span>Customer Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'categorie.php' ? 'active' : ''; ?>">
    <a href="categorie.php" >
      <i class="glyphicon glyphicon-indent-left"></i>
      <span>Category Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'product.php' ? 'active' : ''; ?>">
    <a href="product.php" >
      <i class="glyphicon glyphicon-th-large"></i>
      <span>Product Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'suppliers.php' ? 'active' : ''; ?>">
    <a href="suppliers.php">
      <i class="glyphicon glyphicon-briefcase"></i>
      <span>Supplier Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
    <a href="orders.php" >
      <i class="glyphicon glyphicon-list-alt"></i>
      <span>Online Order Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'physical_sales.php' ? 'active' : ''; ?>">
    <a href="physical_sales.php" >
      <i class="glyphicon glyphicon-shopping-cart"></i>
      <span>Walk-in Sales Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'promotions.php' ? 'active' : ''; ?>">
    <a href="promotions.php" >
      <i class="glyphicon glyphicon-gift"></i>
      <span>Promotion Management</span>
    </a>
  </li>
  <li class="<?php echo $current_page === 'break_even_analyzer.php' ? 'active' : ''; ?>">
    <a href="break_even_analyzer.php" >
      <i class="glyphicon glyphicon-dashboard"></i>
      <span>Break-Even Analysis</span>
    </a>
  </li>
  <li class="<?php echo in_array($current_page, ['online_sales_history.php','physical_sales_history.php']) ? 'active' : ''; ?>">
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-list"></i>
      <span>Sales History</span>
    </a>
    <ul class="nav submenu">
       <li><a href="online_sales_history.php">Online Sales History</a></li>
       <li><a href="physical_sales_history.php">Walk-in Sales History</a></li>
   </ul>
  </li>
  <li class="<?php echo in_array($current_page, ['supplier_report.php','business_analytics_report.php','stock_report.php']) ? 'active' : ''; ?>">
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-stats"></i>
      <span>Reports</span>
    </a>
    <ul class="nav submenu">
       <li><a href="business_analytics_report.php">Sales & Revenue Report</a></li>
       <li><a href="stock_report.php">Stock Report</a></li>
       <li><a href="supplier_report.php">Supplier Report</a></li>
   </ul>
  </li>
</ul>
