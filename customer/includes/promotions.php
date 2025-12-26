<?php
// Promotion calculation functions
function getProductPromotions($product_id, $category_id) {
    global $db;
    
    $current_time = date('Y-m-d H:i:s');
    
    
    $sql = "SELECT p.* FROM promotions p 
            LEFT JOIN promotion_products pp ON p.id = pp.promotion_id 
            WHERE p.is_active = 1 
            AND p.start_date <= '{$current_time}' 
            AND p.end_date >= '{$current_time}' 
            AND (p.applies_to = 'all_products' 
                 OR (p.applies_to = 'specific_products' AND pp.product_id = {$product_id})
                 OR (p.applies_to = 'specific_categories' AND p.id IN (
                     SELECT pc.promotion_id FROM promotion_categories pc WHERE pc.category_id = " . ($category_id ? $category_id : '0') . "
                 )))
            ORDER BY p.discount_value DESC";
    
    $result = $db->query($sql);
    $promotions = [];
    
    while ($row = $db->fetch_assoc($result)) {
        $promotions[] = $row;
    }
    
    return $promotions;
}

function calculateDiscountedPrice($original_price, $promotions) {
    if (empty($promotions)) {
        return $original_price;
    }
    
    $best_discount = 0;
    $best_promotion = null;
    
    foreach ($promotions as $promotion) {
        $discount_amount = 0;
        
        if ($promotion['discount_type'] === 'percentage') {
            $discount_amount = ($original_price * $promotion['discount_value']) / 100;
            
            
            if ($promotion['max_discount_amount'] && $discount_amount > $promotion['max_discount_amount']) {
                $discount_amount = $promotion['max_discount_amount'];
            }
        } else if ($promotion['discount_type'] === 'fixed_amount') {
            $discount_amount = $promotion['discount_value'];
        }
        
        if ($discount_amount > $best_discount) {
            $best_discount = $discount_amount;
            $best_promotion = $promotion;
        }
    }
    
    $final_price = $original_price - $best_discount;
    return max(0, $final_price); 
}

function getPromotionInfo($original_price, $promotions) {
    if (empty($promotions)) {
        return null;
    }
    
    $best_discount = 0;
    $best_promotion = null;
    
    foreach ($promotions as $promotion) {
        $discount_amount = 0;
        
        if ($promotion['discount_type'] === 'percentage') {
            $discount_amount = ($original_price * $promotion['discount_value']) / 100;
            
            if ($promotion['max_discount_amount'] && $discount_amount > $promotion['max_discount_amount']) {
                $discount_amount = $promotion['max_discount_amount'];
            }
        } else if ($promotion['discount_type'] === 'fixed_amount') {
            $discount_amount = $promotion['discount_value'];
        }
        
        if ($discount_amount > $best_discount) {
            $best_discount = $discount_amount;
            $best_promotion = $promotion;
        }
    }
    
    if ($best_promotion) {
        return [
            'promotion' => $best_promotion,
            'discount_amount' => $best_discount,
            'original_price' => $original_price,
            'discounted_price' => max(0, $original_price - $best_discount)
        ];
    }
    
    return null;
}
?>
