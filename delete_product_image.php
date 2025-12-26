<?php
require_once('includes/load.php');

// Check if user has permission
page_require_level(2);

$image_id = isset($_GET['image_id']) ? (int)$_GET['image_id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($image_id <= 0 || $product_id <= 0) {
    $session->msg('d', 'Invalid image or product ID');
    redirect('product.php', false);
}

// Verify the product exists and has images
$product_check = $db->query("SELECT images FROM products WHERE id = " . $product_id);
if ($db->num_rows($product_check) == 0) {
    $session->msg('d', 'Product not found');
    redirect('product.php', false);
}

$product = $db->fetch_assoc($product_check);
$images_array = json_decode($product['images'], true);

if (!is_array($images_array) || $image_id >= count($images_array)) {
    $session->msg('d', 'Image not found or does not belong to this product');
    redirect('edit_product.php?id=' . $product_id, false);
}

// Delete the image
if (delete_product_image($image_id, $product_id)) {
    $session->msg('s', 'Image deleted successfully');
} else {
    $session->msg('d', 'Failed to delete image');
}

redirect('edit_product.php?id=' . $product_id, false);
?>
