<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
?>
<?php
  $promotion = find_by_id('promotions',(int)$_GET['id']);
  if(!$promotion){
    $session->msg("d","Missing Promotion id.");
    redirect('promotions.php');
  }
?>
<?php
  $delete_id = delete_by_id('promotions',(int)$promotion['id']);
  if($delete_id){
      $session->msg("s","Promotion deleted.");
      redirect('promotions.php');
  } else {
      $session->msg("d","Promotion deletion failed.");
      redirect('promotions.php');
  }
?>
