<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'SW_inventory_system';

// Create connection without database
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    // Select the database
    $conn->select_db($database);
} else {
    die("Error creating database: " . $conn->error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Set MySQL timezone to Sri Lanka (Asia/Colombo)
$conn->query("SET time_zone = '+05:30'");

// Create customers table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `customers` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `address` text,
    `status` tinyint(1) NOT NULL DEFAULT '1',
    `registration_type` enum('online','walkin') NOT NULL DEFAULT 'online',
    `created_at` datetime NOT NULL,
    `last_login` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating customers table: " . $conn->error);
}

// Create orders table if it doesn't exist (corrected table name)
$sql = "CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) unsigned NOT NULL,
    `order_number` varchar(50) NOT NULL,
    `total_amount` decimal(25,2) NOT NULL,
    `shipping_address` text NOT NULL,
    `payment_method` varchar(50) NOT NULL,
    `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
    `status` enum('pending','processing','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_number` (`order_number`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating orders table: " . $conn->error);
}

// Create order_items table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `order_items` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `order_id` int(11) unsigned NOT NULL,
    `product_id` int(11) unsigned NOT NULL,
    `quantity` int(11) NOT NULL,
    `price` decimal(25,2) NOT NULL,
    `subtotal` decimal(25,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating order_items table: " . $conn->error);
}

// Create products table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `products` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `quantity` int(11) NOT NULL,
    `buy_price` decimal(25,2) NOT NULL,
    `sale_price` decimal(25,2) NOT NULL,
    `images` json DEFAULT NULL,
    `primary_image_index` int(11) DEFAULT 0,
    `date` datetime NOT NULL,
    `categorie_id` int(11) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `categorie_id` (`categorie_id`),
    CONSTRAINT `products_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating products table: " . $conn->error);
}

// Create categories table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(60) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating categories table: " . $conn->error);
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `user_level` int(11) NOT NULL,
    `status` int(1) NOT NULL,
    `last_login` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    KEY `user_level` (`user_level`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";

if (!$conn->query($sql)) {
    die("Error creating users table: " . $conn->error);
}

// Create user_groups table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `user_groups` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `group_name` varchar(150) NOT NULL,
    `group_level` int(11) NOT NULL,
    `group_status` int(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `group_level` (`group_level`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";

if (!$conn->query($sql)) {
    die("Error creating user_groups table: " . $conn->error);
}

// Add indexes if they don't exist (optional but good for performance)

// Ensure new image system columns exist
$check_images_column = "SHOW COLUMNS FROM `products` LIKE 'images'";
$images_column_result = $conn->query($check_images_column);
if ($images_column_result && $images_column_result->num_rows == 0) {
    $add_images_sql = "ALTER TABLE `products` ADD COLUMN `images` json DEFAULT NULL AFTER `sale_price`";
    $conn->query($add_images_sql);
}

$check_primary_column = "SHOW COLUMNS FROM `products` LIKE 'primary_image_index'";
$primary_column_result = $conn->query($check_primary_column);
if ($primary_column_result && $primary_column_result->num_rows == 0) {
    $add_primary_sql = "ALTER TABLE `products` ADD COLUMN `primary_image_index` int(11) DEFAULT 0 AFTER `images`";
    $conn->query($add_primary_sql);
}

// Remove legacy image column if it exists
$check_legacy_image = "SHOW COLUMNS FROM `products` LIKE 'image'";
$legacy_image_result = $conn->query($check_legacy_image);
if ($legacy_image_result && $legacy_image_result->num_rows > 0) {
    $remove_legacy_sql = "ALTER TABLE `products` DROP COLUMN `image`";
    $conn->query($remove_legacy_sql);
}

// Ensure description column exists
$check_description_column = "SHOW COLUMNS FROM `products` LIKE 'description'";
$description_column_result = $conn->query($check_description_column);
if ($description_column_result && $description_column_result->num_rows == 0) {
    $add_description_sql = "ALTER TABLE `products` ADD COLUMN `description` text DEFAULT NULL AFTER `name`";
    $conn->query($add_description_sql);
}

// Create promotions table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `promotions` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `discount_type` enum('percentage','fixed_amount') NOT NULL DEFAULT 'percentage',
    `discount_value` decimal(10,2) NOT NULL,
    `max_discount_amount` decimal(10,2) DEFAULT NULL,
    `applies_to` enum('all_products','specific_products','specific_categories') NOT NULL DEFAULT 'all_products',
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT '1',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating promotions table: " . $conn->error);
}

// Create promotion_products table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `promotion_products` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `promotion_id` int(11) unsigned NOT NULL,
    `product_id` int(11) unsigned NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_promotion_product` (`promotion_id`, `product_id`),
    KEY `promotion_id` (`promotion_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `promotion_products_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `promotion_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating promotion_products table: " . $conn->error);
}

// Create promotion_categories table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `promotion_categories` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `promotion_id` int(11) unsigned NOT NULL,
    `category_id` int(11) unsigned NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_promotion_category` (`promotion_id`, `category_id`),
    KEY `promotion_id` (`promotion_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `promotion_categories_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `promotion_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!$conn->query($sql)) {
    die("Error creating promotion_categories table: " . $conn->error);
}

// Example: Check and add index for products.categorie_id
// $index_check_sql = "SHOW INDEX FROM `products` WHERE Key_name = 'categorie_id'";
// $index_result = $conn->query($index_check_sql);
// if ($index_result && $index_result->num_rows == 0) {
//     $add_index_sql = "CREATE INDEX `categorie_id` ON `products` (`categorie_id`)";
//     $conn->query($add_index_sql);
// }

// Example: Check and add foreign key constraint for products.categorie_id
// $fk_check_sql = "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = 'products' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'products_ibfk_1'";
// $fk_result = $conn->query($fk_check_sql);
// if ($fk_result && $fk_result->num_rows == 0) {
//     $add_fk_sql = "ALTER TABLE `products` ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE";
//     $conn->query($add_fk_sql);
// }

// Example: Correct order_items foreign keys to reference `orders` table
// $fk_check_sql_oi1 = "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = 'order_items' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'order_items_ibfk_1'";
// $fk_result_oi1 = $conn->query($fk_check_sql_oi1);
// if ($fk_result_oi1 && $fk_result_oi1->num_rows == 0) {
//     // Drop incorrect constraint if it exists (might be named differently)
//     // ALTER TABLE `order_items` DROP FOREIGN KEY `order_items_ibfk_1`;
//     $add_fk_sql_oi1 = "ALTER TABLE `order_items` ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE";
//     $conn->query($add_fk_sql_oi1);
// }

// Ensure all necessary tables exist and foreign keys are correct
// The existing CREATE TABLE IF NOT EXISTS statements should handle initial creation.
// Manual database inspection might be needed if FKs are consistently an issue.

?> 