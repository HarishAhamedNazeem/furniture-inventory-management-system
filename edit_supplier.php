<?php
  $page_title = 'Edit Supplier';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);
?>
<?php
  // Get supplier ID from GET or POST
  $supplier_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0);
  
  if($supplier_id > 0){
    $supplier = find_by_id('suppliers', $supplier_id);
    if(!$supplier){
      $session->msg("d","Supplier not found with ID: " . $supplier_id);
      redirect('suppliers.php');
    }
  } else {
    $session->msg("d","Missing supplier id.");
    redirect('suppliers.php');
  }
?>
<?php
 if(isset($_POST['edit_supplier'])){
   $req_fields = array('supplier-name','contact-person','phone');
   validate_fields($req_fields);
   
   if(!empty($errors)){
     $session->msg("d", "Validation failed: " . $errors);
     redirect('edit_supplier.php?id='.$supplier_id,false);
   }
   
   if(empty($errors)){
     $s_name  = remove_junk($db->escape($_POST['supplier-name']));
     $s_contact = remove_junk($db->escape($_POST['contact-person']));
     $s_phone  = remove_junk($db->escape($_POST['phone']));
     $s_email  = remove_junk($db->escape($_POST['email']));
     $s_address = remove_junk($db->escape($_POST['address']));
     $s_status = remove_junk($db->escape($_POST['status']));

     $query = "UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, status=? WHERE id=?";
     $stmt = $db->con->prepare($query);
     
     if (!$stmt) {
       $session->msg('d','Prepare failed: ' . $db->con->error);
       redirect('edit_supplier.php?id='.$supplier_id, false);
     }
     
     $stmt->bind_param('ssssssi', $s_name, $s_contact, $s_phone, $s_email, $s_address, $s_status, $supplier_id);
     $result = $stmt->execute();
     
     if($result && $stmt->affected_rows === 1){
       $session->msg('s',"Supplier updated successfully!");
       redirect('suppliers.php', false);
     } else {
       $error_msg = 'Sorry, failed to update supplier!';
       if (!$result) {
         $error_msg .= ' Execute error: ' . $stmt->error;
       } else {
         $error_msg .= ' Affected rows: ' . $stmt->affected_rows . ' (Supplier ID: ' . $supplier_id . ')';
       }
       $session->msg('d', $error_msg);
       redirect('edit_supplier.php?id='.$supplier_id, false);
     }
     $stmt->close();

   } else{
     $session->msg("d", $errors);
     redirect('edit_supplier.php?id='.$supplier_id,false);
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
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Edit Supplier</span>
       </strong>
      </div>
      <div class="panel-body">
         <div class="col-md-7">
           <form method="post" action="edit_supplier.php">
              <input type="hidden" name="supplier_id" value="<?php echo (int)$supplier['id']; ?>">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="supplier-name" value="<?php echo remove_junk($supplier['name']); ?>">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-user"></i>
                  </span>
                  <input type="text" class="form-control" name="contact-person" value="<?php echo remove_junk($supplier['contact_person']); ?>">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-phone"></i>
                  </span>
                  <input type="text" class="form-control" name="phone" value="<?php echo remove_junk($supplier['phone']); ?>">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-envelope"></i>
                  </span>
                  <input type="email" class="form-control" name="email" value="<?php echo remove_junk($supplier['email']); ?>">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-home"></i>
                  </span>
                  <textarea class="form-control" name="address"><?php echo remove_junk($supplier['address']); ?></textarea>
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-ok"></i>
                  </span>
                  <select class="form-control" name="status">
                    <option value="1" <?php if($supplier['status'] === '1') echo 'selected="selected"'; ?>>Active</option>
                    <option value="0" <?php if($supplier['status'] === '0') echo 'selected="selected"'; ?>>Inactive</option>
                  </select>
               </div>
              </div>
              <button type="submit" name="edit_supplier" class="btn btn-primary">Update supplier</button>
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?> 