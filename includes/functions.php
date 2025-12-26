<?php
 $errors = array();

 /*--------------------------------------------------------------*/
 /* Function for Remove escapes special
 /* characters in a string for use in an SQL statement
 /*--------------------------------------------------------------*/
function real_escape($str){
  global $con;
  $escape = mysqli_real_escape_string($con,$str);
  return $escape;
}
/*--------------------------------------------------------------*/
/* Function for Remove html characters
/*--------------------------------------------------------------*/
function remove_junk($str){
  if ($str === null || $str === '') {
    return $str;
  }
  $str = htmlspecialchars(strip_tags($str));
  return $str;
}
/*--------------------------------------------------------------*/
/* Function for Uppercase first character
/*--------------------------------------------------------------*/
function first_character($str){
  $val = str_replace('-'," ",$str);
  $val = ucfirst($val);
  return $val;
}
/*--------------------------------------------------------------*/
/* Function for Checking input fields not empty
/*--------------------------------------------------------------*/
function validate_fields($var){
  global $errors;
  foreach ($var as $field) {
    if(!isset($_POST[$field]) || empty($_POST[$field])){
      $errors = $field ." can't be blank.";
      return $errors;
    }
    $val = remove_junk($_POST[$field]);
    if($val==''){
      $errors = $field ." can't be blank.";
      return $errors;
    }
  }
}
/*--------------------------------------------------------------*/
/* Function for Display Session Message
   Ex echo displayt_msg($message);
/*--------------------------------------------------------------*/
function display_msg($msg =''){
   $output = array();
   if(!empty($msg)) {
      foreach ($msg as $key => $value) {
         $output  = "<div class=\"alert alert-{$key}\">";
         $output .= "<a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a>";
         $output .= remove_junk(first_character($value));
         $output .= "</div>";
      }
      return $output;
   } else {
     return "" ;
   }
}
/*--------------------------------------------------------------*/
/* Function for redirect
/*--------------------------------------------------------------*/
function redirect($url, $permanent = false)
{
    if (headers_sent() === false)
    {
      header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
    }

    exit();
}
/*--------------------------------------------------------------*/
/* Function for find out total saleing price, buying price and profit
/*--------------------------------------------------------------*/
function total_price($totals){
   $sum = 0;
   $sub = 0;
   foreach($totals as $total ){
     $sum += $total['total_saleing_price'];
     $sub += $total['total_buying_price'];
     $profit = $sum - $sub;
   }
   return array($sum,$profit);
}
/*--------------------------------------------------------------*/
/* Function for Readable date time
/*--------------------------------------------------------------*/
function read_date($str){
     if($str)
      return date('F j, Y, g:i:s a', strtotime($str));
     else
      return null;
  }
/*--------------------------------------------------------------*/
/* Function for  Readable Make date time
/*--------------------------------------------------------------*/
function make_date(){
  return strftime("%Y-%m-%d %H:%M:%S", time());
}
/*--------------------------------------------------------------*/
/* Function for  Readable date time
/*--------------------------------------------------------------*/
function count_id(){
  static $count = 1;
  return $count++;
}
/*--------------------------------------------------------------*/
/* Function for Creting random string
/*--------------------------------------------------------------*/
function randString($length = 5)
{
  $str='';
  $cha = "0123456789abcdefghijklmnopqrstuvwxyz";

  for($x=0; $x<$length; $x++)
   $str .= $cha[mt_rand(0,strlen($cha))];
  return $str;
}


/**
 * Find products by supplier
 */
function find_products_by_supplier($supplier_id) {
   global $db;
   
   // First check if supplier_id column exists
   $check_column = "SHOW COLUMNS FROM products LIKE 'supplier_id'";
   $result = find_by_sql($check_column);
   
   if(empty($result)) {
     // If supplier_id column doesn't exist, return all products
     $sql  = "SELECT p.*, c.name as categorie ";
     $sql .= "FROM products p ";
     $sql .= "LEFT JOIN categories c ON p.categorie_id = c.id ";
     $sql .= "ORDER BY p.name ASC";
   } else {
     // If supplier_id column exists, filter by supplier
     $sql  = "SELECT p.*, c.name as categorie ";
     $sql .= "FROM products p ";
     $sql .= "LEFT JOIN categories c ON p.categorie_id = c.id ";
     $sql .= "WHERE p.supplier_id = '{$supplier_id}' ";
     $sql .= "ORDER BY p.name ASC";
   }
   
   return find_by_sql($sql);
}

/**
 * Render a dashboard widget card
 * @param int $count
 * @param string $label
 * @param string $color (CSS color or class)
 * @param string $icon (Font Awesome or glyphicon class)
 * @param string $iconBg (background color or class)
 * @return string
 */
function render_widget_card($count, $label, $color, $icon, $iconBg) {
    return '<div class="dashboard-widget-card">'
        . '<div class="widget-info">'
        . '<div class="widget-count" style="color:' . $color . ';">' . $count . '</div>'
        . '<div class="widget-label" style="color:' . $color . ';">' . $label . '</div>'
        . '</div>'
        . '<div class="widget-icon" style="background:' . $iconBg . ';">'
        . '<i class="' . $icon . '" style="color:' . $color . ';"></i>'
        . '</div>'
        . '</div>';
}

?>
