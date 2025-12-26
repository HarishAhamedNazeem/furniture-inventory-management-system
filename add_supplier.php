<?php
  $page_title = 'Add Supplier';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);
?>
<?php
 if(isset($_POST['add_supplier'])){
   $req_fields = array('supplier-name','contact-person','phone');
   validate_fields($req_fields);
   if(empty($errors)){
     $s_name  = remove_junk($db->escape($_POST['supplier-name']));
     $s_contact = remove_junk($db->escape($_POST['contact-person']));
     $s_phone  = remove_junk($db->escape($_POST['phone']));
     $s_email  = remove_junk($db->escape($_POST['email']));
     $s_address = remove_junk($db->escape($_POST['address']));
     $s_status = remove_junk($db->escape($_POST['status']));
     $date    = make_date();

     $query  = "INSERT INTO suppliers (";
     $query .=" name,contact_person,phone,email,address,status,date";
     $query .=") VALUES (";
     $query .=" '{$s_name}', '{$s_contact}', '{$s_phone}', '{$s_email}', '{$s_address}', '{$s_status}', '{$date}'";
     $query .=")";
     if($db->query($query)){
       $session->msg('s',"Supplier added ");
       redirect('add_supplier.php', false);
     } else {
       $session->msg('d',' Sorry failed to added!');
       redirect('suppliers.php', false);
     }

   } else{
     $session->msg("d", $errors);
     redirect('add_supplier.php',false);
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
          <span>Add New Supplier</span>
       </strong>
      </div>
      <div class="panel-body">
         <div class="col-md-7">
           <form method="post" action="add_supplier.php">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="supplier-name" placeholder="Supplier Name">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-user"></i>
                  </span>
                  <input type="text" class="form-control" name="contact-person" placeholder="Contact Person">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-phone"></i>
                  </span>
                  <input type="text" class="form-control" name="phone" placeholder="Phone Number">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-envelope"></i>
                  </span>
                  <input type="email" class="form-control" name="email" placeholder="Email Address">
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-home"></i>
                  </span>
                  <textarea class="form-control" name="address" placeholder="Address"></textarea>
               </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-ok"></i>
                  </span>
                  <select class="form-control" name="status">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
               </div>
              </div>
              <button type="submit" name="add_supplier" class="btn btn-success">Add supplier</button>
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?> 