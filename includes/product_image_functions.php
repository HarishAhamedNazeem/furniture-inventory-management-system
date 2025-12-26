<?php
/**
 * Product Image Management Functions
 * Updated to work with images stored directly in products table
 */

/**
 * Get all images for a product
 */
function get_product_images($product_id) {
    global $db;
    
    $sql = "SELECT images, primary_image_index FROM products WHERE id = " . (int)$product_id;
    $result = $db->query($sql);
    
    if ($db->num_rows($result) == 0) {
        return array();
    }
    
    $product = $db->fetch_assoc($result);
    
    if (empty($product['images'])) {
        return array();
    }
    
    $images_array = json_decode($product['images'], true);
    if (!is_array($images_array)) {
        return array();
    }
    
    $images = array();
    $primary_index = isset($product['primary_image_index']) ? (int)$product['primary_image_index'] : 0;
    
    foreach ($images_array as $index => $filename) {
        $images[] = array(
            'id' => $index,
            'product_id' => $product_id,
            'image_filename' => $filename,
            'image_order' => $index,
            'is_primary' => ($index == $primary_index) ? 1 : 0,
            'date_added' => null
        );
    }
    
    return $images;
}

/**
 * Get primary image for a product
 */
function get_primary_product_image($product_id) {
    global $db;
    
    $sql = "SELECT images, primary_image_index FROM products WHERE id = " . (int)$product_id;
    $result = $db->query($sql);
    
    if ($db->num_rows($result) == 0) {
        return null;
    }
    
    $product = $db->fetch_assoc($result);
    
    if (empty($product['images'])) {
        return null;
    }
    
    $images_array = json_decode($product['images'], true);
    if (!is_array($images_array) || empty($images_array)) {
        return null;
    }
    
    $primary_index = isset($product['primary_image_index']) ? (int)$product['primary_image_index'] : 0;
    
    // Ensure primary_index is within bounds
    if ($primary_index >= count($images_array)) {
        $primary_index = 0;
    }
    
    return array(
        'id' => $primary_index,
        'product_id' => $product_id,
        'image_filename' => $images_array[$primary_index],
        'image_order' => $primary_index,
        'is_primary' => 1,
        'date_added' => null
    );
}

/**
 * Add image to product
 */
function add_product_image($product_id, $image_filename, $is_primary = false, $image_order = 0) {
    global $db;
    
    // Get current images
    $sql = "SELECT images, primary_image_index FROM products WHERE id = " . (int)$product_id;
    $result = $db->query($sql);
    
    if ($db->num_rows($result) == 0) {
        return false;
    }
    
    $product = $db->fetch_assoc($result);
    $images_array = array();
    $primary_index = 0;
    
    if (!empty($product['images'])) {
        $images_array = json_decode($product['images'], true);
        if (!is_array($images_array)) {
            $images_array = array();
        }
        $primary_index = isset($product['primary_image_index']) ? (int)$product['primary_image_index'] : 0;
    }
    
    // Add new image
    $images_array[] = $image_filename;
    $new_primary_index = $primary_index;
    
    // If this is set as primary, update primary index
    if ($is_primary) {
        $new_primary_index = count($images_array) - 1;
    }
    
    // Update products table
    $images_json = json_encode($images_array);
    $update_sql = "UPDATE products SET images = '" . $db->escape($images_json) . "', primary_image_index = " . (int)$new_primary_index . " WHERE id = " . (int)$product_id;
    
    return $db->query($update_sql);
}

/**
 * Update product image
 */
function update_product_image($image_id, $image_filename = null, $is_primary = null, $image_order = null, $product_id = null) {
    global $db;
    
    // If product_id is not provided, we can't proceed
    if (!$product_id) {
        return false;
    }
    
    // Get current images
    $sql = "SELECT images, primary_image_index FROM products WHERE id = " . (int)$product_id;
    $result = $db->query($sql);
    
    if ($db->num_rows($result) == 0) {
        return false;
    }
    
    $product = $db->fetch_assoc($result);
    $images_array = json_decode($product['images'], true);
    $primary_index = isset($product['primary_image_index']) ? (int)$product['primary_image_index'] : 0;
    
    if (!is_array($images_array) || $image_id >= count($images_array)) {
        return false;
    }
    
    // Update image filename if provided
    if ($image_filename !== null) {
        $images_array[$image_id] = $image_filename;
    }
    
    // Update primary index if provided
    if ($is_primary !== null) {
        if ($is_primary) {
            $primary_index = $image_id;
        }
    }
    
    // Update products table
    $images_json = json_encode($images_array);
    $update_sql = "UPDATE products SET images = '" . $db->escape($images_json) . "', primary_image_index = " . (int)$primary_index . " WHERE id = " . (int)$product_id;
    
    return $db->query($update_sql);
}

/**
 * Delete product image
 */
function delete_product_image($image_id, $product_id = null) {
    global $db;
    
    // If product_id is not provided, we can't proceed
    if (!$product_id) {
        return false;
    }
    
    // Get current images
    $sql = "SELECT images, primary_image_index FROM products WHERE id = " . (int)$product_id;
    $result = $db->query($sql);
    
    if ($db->num_rows($result) == 0) {
        return false;
    }
    
    $product = $db->fetch_assoc($result);
    $images_array = json_decode($product['images'], true);
    $primary_index = isset($product['primary_image_index']) ? (int)$product['primary_image_index'] : 0;
    
    if (!is_array($images_array) || $image_id >= count($images_array)) {
        return false;
    }
    
    // Get filename for file deletion
    $filename = $images_array[$image_id];
    
    // Delete the file
    $file_path = SITE_ROOT . DS . '..' . DS . 'uploads' . DS . 'products' . DS . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Remove image from array
    unset($images_array[$image_id]);
    $images_array = array_values($images_array); // Re-index array
    
    // Update primary index if necessary
    $new_primary_index = $primary_index;
    if ($image_id == $primary_index) {
        // If we deleted the primary image, set first image as primary
        $new_primary_index = 0;
    } elseif ($image_id < $primary_index) {
        // If we deleted an image before the primary, adjust index
        $new_primary_index = $primary_index - 1;
    }
    
    // Update products table
    $images_json = json_encode($images_array);
    $update_sql = "UPDATE products SET images = '" . $db->escape($images_json) . "', primary_image_index = " . (int)$new_primary_index . " WHERE id = " . (int)$product_id;
    
    return $db->query($update_sql);
}

/**
 * Get product ID from image ID
 * Note: This function now requires the product_id to be passed as a parameter
 * since image_id is now just an array index
 */
function get_product_id_from_image_id($image_id, $product_id = null) {
    // Since we're now storing images in the products table,
    // we need the product_id to be passed as a parameter
    // This function is kept for backward compatibility but should be updated
    // in calling code to pass the product_id
    return $product_id;
}

/**
 * Delete all images for a product
 */
function delete_all_product_images($product_id) {
    global $db;
    
    // Get all images for this product
    $images = get_product_images($product_id);
    
    // Delete files
    foreach ($images as $image) {
        $file_path = SITE_ROOT . DS . '..' . DS . 'uploads' . DS . 'products' . DS . $image['image_filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Clear images from products table
    $sql = "UPDATE products SET images = NULL, primary_image_index = 0 WHERE id = " . (int)$product_id;
    return $db->query($sql);
}

/**
 * Handle multiple file uploads
 */
function handle_multiple_image_upload($product_id, $files, $is_primary_index = 0) {
    $uploaded_files = array();
    $errors = array();
    
    if (!isset($files['product-images']) || !is_array($files['product-images']['name'])) {
        return array('files' => $uploaded_files, 'errors' => $errors);
    }
    
    $file_count = count($files['product-images']['name']);
    
    // Get current images to append to them
    $current_images = get_product_images($product_id);
    $current_filenames = array();
    foreach ($current_images as $img) {
        $current_filenames[] = $img['image_filename'];
    }
    
    for ($i = 0; $i < $file_count; $i++) {
        // Skip empty files
        if ($files['product-images']['error'][$i] == UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        // Check for upload errors
        if ($files['product-images']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file " . ($i + 1) . ": " . get_upload_error_message($files['product-images']['error'][$i]);
            continue;
        }
        
        // Validate file
        $file_name = $files['product-images']['name'][$i];
        $file_tmp = $files['product-images']['tmp_name'][$i];
        $file_size = $files['product-images']['size'][$i];
        
        // Check file size (2MB limit)
        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = "File '{$file_name}' is too large (max 2MB)";
            continue;
        }
        
        // Check file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "File '{$file_name}' has invalid type. Allowed: " . implode(', ', $allowed_types);
            continue;
        }
        
        // Generate unique filename
        $unique_filename = uniqid() . '_' . time() . '_' . $i . '.' . $file_extension;
        $upload_path = SITE_ROOT . DS . '..' . DS . 'uploads' . DS . 'products' . DS . $unique_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $uploaded_files[] = $unique_filename;
        } else {
            $errors[] = "Failed to upload file '{$file_name}'";
        }
    }
    
    // If we have uploaded files, update the products table
    if (!empty($uploaded_files)) {
        // Combine current images with new uploaded files
        $all_images = array_merge($current_filenames, $uploaded_files);
        
        // Calculate primary index (current count + primary index of new uploads)
        $primary_index = count($current_filenames) + $is_primary_index;
        
        // Update products table
        global $db;
        $images_json = json_encode($all_images);
        $update_sql = "UPDATE products SET images = '" . $db->escape($images_json) . "', primary_image_index = " . (int)$primary_index . " WHERE id = " . (int)$product_id;
        
        if (!$db->query($update_sql)) {
            $errors[] = "Failed to save images to database";
            // Clean up uploaded files
            foreach ($uploaded_files as $filename) {
                $file_path = SITE_ROOT . DS . '..' . DS . 'uploads' . DS . 'products' . DS . $filename;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            $uploaded_files = array();
        }
    }
    
    return array('files' => $uploaded_files, 'errors' => $errors);
}

/**
 * Get upload error message
 */
function get_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?>
