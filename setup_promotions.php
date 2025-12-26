<?php
// Sample promotion creation script
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('includes/config.php');
require_once('includes/database.php');

echo "<h2>Promotion Setup</h2>";

// Test database connection
if ($db->con->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $db->con->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Check if promotions table exists
$table_check = $db->query("SHOW TABLES LIKE 'promotions'");
if ($db->num_rows($table_check) > 0) {
    echo "<p style='color: green;'>✅ Promotions table exists</p>";
    
    // Check if there are existing promotions
    $count_result = $db->query("SELECT COUNT(*) as total FROM promotions");
    $total_promotions = $db->fetch_assoc($count_result)['total'];
    echo "<p>Total promotions in database: <strong>" . $total_promotions . "</strong></p>";
    
    if ($total_promotions == 0) {
        echo "<h3>Creating Sample Promotions</h3>";
        
        // Create a sample promotion for all products
        $sample_promotion_sql = "INSERT INTO promotions (name, description, discount_type, discount_value, applies_to, start_date, end_date, is_active) 
                                VALUES ('Summer Sale', 'Get 10% off on all products', 'percentage', 10.00, 'all_products', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
        
        if ($db->query($sample_promotion_sql)) {
            echo "<p style='color: green;'>✅ Created sample promotion: Summer Sale (10% off all products)</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create sample promotion: " . $db->con->error . "</p>";
        }
        
        // Create a category-specific promotion (if categories exist)
        $cat_check = $db->query("SELECT COUNT(*) as total FROM categories");
        $total_categories = $db->fetch_assoc($cat_check)['total'];
        
        if ($total_categories > 0) {
            $category_promotion_sql = "INSERT INTO promotions (name, description, discount_type, discount_value, applies_to, start_date, end_date, is_active) 
                                     VALUES ('Category Special', 'Get 15% off on specific category', 'percentage', 15.00, 'specific_categories', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
            
            if ($db->query($category_promotion_sql)) {
                $promotion_id = $db->insert_id();
                
                // Get first category and link it to the promotion
                $first_cat = $db->query("SELECT id FROM categories LIMIT 1");
                if ($first_cat && $db->num_rows($first_cat) > 0) {
                    $category = $db->fetch_assoc($first_cat);
                    $link_sql = "INSERT INTO promotion_categories (promotion_id, category_id) VALUES ({$promotion_id}, {$category['id']})";
                    
                    if ($db->query($link_sql)) {
                        echo "<p style='color: green;'>✅ Created category-specific promotion (15% off)</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Created promotion but failed to link to category</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to create category promotion: " . $db->con->error . "</p>";
            }
        }
        
    } else {
        echo "<h3>Existing Promotions</h3>";
        $promotions_result = $db->query("SELECT * FROM promotions ORDER BY created_at DESC");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Value</th><th>Applies To</th><th>Active</th><th>Start Date</th><th>End Date</th></tr>";
        while ($promotion = $db->fetch_assoc($promotions_result)) {
            echo "<tr>";
            echo "<td>" . $promotion['id'] . "</td>";
            echo "<td>" . htmlspecialchars($promotion['name']) . "</td>";
            echo "<td>" . $promotion['discount_type'] . "</td>";
            echo "<td>" . $promotion['discount_value'] . "</td>";
            echo "<td>" . $promotion['applies_to'] . "</td>";
            echo "<td>" . ($promotion['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $promotion['start_date'] . "</td>";
            echo "<td>" . $promotion['end_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Promotions table does not exist</p>";
}

echo "<hr>";
echo "<p><a href='customer/shop.php'>Go to Shop</a> | <a href='customer/cart.php'>Go to Cart</a></p>";
?>
