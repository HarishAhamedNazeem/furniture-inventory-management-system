<?php
// Disable error reporting to prevent output before JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once('includes/load.php');
require_once('includes/admin_promotions.php');
if (!$session->isUserLoggedIn(true)) { redirect('index.php', false);}

// Set proper headers for JSON response
header('Content-Type: application/json');

// Initialize response
$response = ['success' => false, 'data' => '', 'error' => ''];

try {
    // Handle auto suggestion
    if(isset($_POST['product_name']) && strlen($_POST['product_name'])) {
        $products = find_product_by_title($_POST['product_name']);
        $html = '';
        
        if($products){
            foreach ($products as $product):
                $html .= "<li class=\"list-group-item\">";
                $html .= htmlspecialchars($product['name']);
                $html .= "</li>";
            endforeach;
        } else {
            $html .= '<li class="list-group-item">Not found</li>';
        }
        
        $response['success'] = true;
        $response['data'] = $html;
    }
    // Handle product search for sale
    elseif(isset($_POST['p_name']) && strlen($_POST['p_name'])) {
        $product_title = remove_junk($db->escape($_POST['p_name']));
        
        // Enhanced product search with LIKE for better matching
        $sql = "SELECT p.*, c.name as categorie_name 
                FROM products p 
                LEFT JOIN categories c ON p.categorie_id = c.id 
                WHERE p.name LIKE '%{$product_title}%' 
                AND p.quantity > 0 
                ORDER BY p.name ASC 
                LIMIT 10";
        
        $results = find_by_sql($sql);
        $html = '';
        
        if($results && count($results) > 0){
            foreach ($results as $result) {
                // Get promotions for this product
                $promotions = getProductPromotionsAdmin($result['id'], $result['categorie_id']);
                $promotion_info = getPromotionInfoAdmin($result['sale_price'], $promotions);
                
                $original_price = $result['sale_price']; // This is the regular price
                $sale_price = $original_price;
                $discount_amount = 0;
                $promotion_id = 0;
                $promotion_name = '';
                
                if ($promotion_info) {
                    $sale_price = $promotion_info['discounted_price'];
                    $discount_amount = $promotion_info['discount_amount'];
                    $promotion_id = $promotion_info['promotion']['id'];
                    $promotion_name = $promotion_info['promotion']['name'];
                }

                $html .= "<tr class=\"product-row\">";

                $html .= "<td>";
                $html .= "<strong>".htmlspecialchars($result['name'])."</strong>";
                if ($promotion_info) {
                    $html .= "<br><span class=\"label label-success\"><i class=\"glyphicon glyphicon-tag\"></i> ".htmlspecialchars($promotion_name)."</span>";
                }
                $html .= "<br><small class=\"text-muted\">SKU: ".$result['id']."</small>";
                $html .= "</td>";
                
                $html  .= "<td>";
                $html  .= "<div class=\"input-group\">";
                $html  .= "<span class=\"input-group-addon\">$</span>";
                $html  .= "<input type=\"number\" class=\"form-control original-price\" value=\"".number_format($original_price, 2)."\" readonly>";
                $html  .= "</div>";
                $html  .= "</td>";
                
                $html  .= "<td>";
                $html  .= "<div class=\"input-group\">";
                $html  .= "<span class=\"input-group-addon\">$</span>";
                $html  .= "<input type=\"number\" class=\"form-control sale-price\" name=\"price\" value=\"".number_format($sale_price, 2)."\" step=\"0.01\" min=\"0\">";
                $html  .= "</div>";
                $html  .= "</td>";
                
                $html  .= "<td>";
                $html  .= "<div class=\"input-group\">";
                $html  .= "<span class=\"input-group-addon\">$</span>";
                $html  .= "<input type=\"number\" class=\"form-control discount-amount\" value=\"".number_format($discount_amount, 2)."\" readonly>";
                $html  .= "</div>";
                $html  .= "</td>";
                
                $html .= "<td>";
                $html .= "<input type=\"number\" class=\"form-control quantity\" name=\"quantity\" value=\"1\" min=\"1\" onchange=\"calculateRowTotal(this)\">";
                $html .= "</td>";
                
                $html  .= "<td>";
                $total = $sale_price * 1;
                $html  .= "<div class=\"input-group\">";
                $html  .= "<span class=\"input-group-addon\">$</span>";
                $html  .= "<input type=\"number\" class=\"form-control total-amount\" name=\"total\" value=\"".number_format($total, 2)."\" readonly>";
                $html  .= "</div>";
                $html  .= "</td>";
                
                $html  .= "<td>";
                $html  .= "<input type=\"date\" class=\"form-control\" name=\"date\" value=\"".date('Y-m-d')."\">";
                $html  .= "</td>";
                
                $html  .= "<td>";
                $html  .= "<button type=\"submit\" name=\"add_sale\" class=\"btn btn-success btn-sm\">";
                $html  .= "<i class=\"glyphicon glyphicon-plus\"></i> Add Sale";
                $html  .= "</button>";
                $html  .= "</td>";
                
                // Hidden fields for form submission
                $html .= "<input type=\"hidden\" name=\"s_id\" value=\"{$result['id']}\">";
                $html .= "<input type=\"hidden\" name=\"promotion_id\" value=\"{$promotion_id}\">";
                $html .= "<input type=\"hidden\" name=\"original_total\" value=\"{$original_price}\">";
                $html .= "<input type=\"hidden\" name=\"discount_amount\" value=\"{$discount_amount}\">";
                
                $html  .= "</tr>";
            }
        } else {
            $html = '<tr class="no-results">';
            $html .= '<td colspan="8" class="text-center text-muted">';
            $html .= '<i class="glyphicon glyphicon-info-sign"></i> ';
            $html .= 'No products found matching "' . htmlspecialchars($product_title) . '"';
            $html .= '<br><small>Try searching with a different term or check if the product exists</small>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $response['success'] = true;
        $response['data'] = $html;
    } else {
        $response['error'] = 'No search term provided';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Error searching for products: ' . $e->getMessage();
}

// Output JSON response
echo json_encode($response);
?>
