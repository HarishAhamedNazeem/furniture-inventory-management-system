<?php
  $page_title = 'Delete Supplier';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);
?>
<?php
  $supplier_id = (int)$_GET['id'];
  
  if($supplier_id <= 0){
    $session->msg("d","Invalid supplier id.");
    redirect('suppliers.php');
  }
  
  $supplier = find_by_id('suppliers', $supplier_id);
  if(!$supplier){
    $session->msg("d","Supplier not found.");
    redirect('suppliers.php');
  }
?>
<?php
  $delete_id = delete_by_id('suppliers', $supplier_id);
  if($delete_id){
      $session->msg("s","Supplier '{$supplier['name']}' deleted successfully.");
      redirect('suppliers.php');
  } else {
      $session->msg("d","Failed to delete supplier '{$supplier['name']}'.");
      redirect('suppliers.php');
  }
?>