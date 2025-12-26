<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('includes/promotions.php');
require_once('../includes/product_image_functions.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Get product details
$product_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.categorie_id = c.id 
                WHERE p.id = " . $product_id;
$product_result = $db->query($product_sql);

if ($db->num_rows($product_result) == 0) {
    header('Location: shop.php');
    exit();
}

$product = $db->fetch_assoc($product_result);

// Get related products
$related_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.categorie_id = c.id 
                WHERE p.categorie_id = " . $product['categorie_id'] . " 
                AND p.id != " . $product_id . " 
                AND p.quantity > 0 
                ORDER BY p.date DESC 
                LIMIT 4";
$related_result = $db->query($related_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
</head>
<body class="customer-portal">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Product Detail -->
    <section class="py-4">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                    <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['categorie_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>

            <div class="row">
                <!-- Product Images -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <?php 
                        // Get all product images
                        $product_images = get_product_images($product['id']);
                        if (!empty($product_images)): ?>
                            <!-- Main Image Display with Magnify Effect -->
                            <div class="product-image-wrapper" id="main-product-image-wrapper">
                                <div class="magnify-hint" id="magnify-hint">
                                    <i class="fas fa-search-plus"></i>
                                    <span>Hover to zoom</span>
                                </div>
                                <div class="product-image-large" id="main-product-image" style="background-image: url('../uploads/products/<?php echo $product_images[0]['image_filename']; ?>')">
                                    <div class="position-absolute top-0 end-0 m-3" style="z-index: 20;">
                                        <button class="btn btn-light btn-sm wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Hover Magnify Lens -->
                                    <div class="magnify-lens" id="magnify-lens"></div>
                                    
                                    <!-- Navigation Arrows -->
                                    <?php if (count($product_images) > 1): ?>
                                    <div class="image-navigation">
                                        <button class="nav-arrow nav-prev" id="prev-image">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <button class="nav-arrow nav-next" id="next-image">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Image Counter -->
                                    <div class="image-counter">
                                        <span id="current-image">1</span> / <span id="total-images"><?php echo count($product_images); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Image Thumbnails -->
                            <?php if (count($product_images) > 1): ?>
                            <div class="product-thumbnails p-3">
                                <div class="row g-2">
                                    <?php foreach ($product_images as $index => $img): ?>
                                    <div class="col-3">
                                        <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                             onclick="changeMainImage('<?php echo $img['image_filename']; ?>', this)"
                                             data-image-filename="<?php echo $img['image_filename']; ?>"
                                             data-image-index="<?php echo $index; ?>"
                                             style="cursor: pointer;">
                                            <img src="../uploads/products/<?php echo $img['image_filename']; ?>" 
                                                 alt="Product View <?php echo $index + 1; ?>" 
                                                 class="img-fluid rounded"
                                                 style="width: 100%; height: 60px; object-fit: cover;">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Fallback to default image -->
                            <div class="product-image-wrapper" id="main-product-image-wrapper">
                                <div class="magnify-hint" id="magnify-hint">
                                    <i class="fas fa-search-plus"></i>
                                    <span>Hover to zoom</span>
                                </div>
                                <div class="product-image-large" id="main-product-image" style="background-image: url('../uploads/products/default.jpg')">
                                    <div class="position-absolute top-0 end-0 m-3" style="z-index: 20;">
                                        <button class="btn btn-light btn-sm wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    <!-- Hover Magnify Lens -->
                                    <div class="magnify-lens" id="magnify-lens"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6 mb-4">
                    <div class="product-info-card">
                        <div class="card-body">
                            <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <div class="product-category-badge"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            
                            <?php if (!empty($product['description'])): ?>
                            <div class="mb-4">
                                <h5 class="mb-3" style="color: var(--text-primary); font-weight: 600;">Description</h5>
                                <p class="product-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="product-price-section">
                                <?php 
                                // Get promotions for this product
                                $promotions = getProductPromotions($product['id'], $product['categorie_id']);
                                $promotion_info = getPromotionInfo($product['sale_price'], $promotions);
                                
                                if ($promotion_info): ?>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <span class="product-price-current">
                                            LKR <?php echo number_format($promotion_info['discounted_price'], 2); ?>
                                        </span>
                                        <span class="product-price-original">
                                            LKR <?php echo number_format($promotion_info['original_price'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="text-center">
                                        <span class="promotion-badge-large"><?php echo $promotion_info['promotion']['discount_value']; ?>% OFF</span>
                                        <?php if($promotion_info['promotion']['name']): ?>
                                            <div class="mt-2">
                                                <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($promotion_info['promotion']['name']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <span class="product-price-current">
                                            LKR <?php echo number_format($product['sale_price'], 2); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="stock-status">
                                <span class="stock-badge <?php echo $product['quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php echo $product['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                </span>
                                <?php if ($product['quantity'] > 0 && $product['quantity'] <= 10): ?>
                                    <span class="stock-badge low-stock">Low Stock</span>
                                <?php endif; ?>
                                <small style="color: var(--text-muted); margin-left: auto;"><?php echo $product['quantity']; ?> available</small>
                            </div>

                            <div class="quantity-section">
                                <label for="quantity" class="form-label mb-3" style="color: var(--text-primary); font-weight: 600;">Quantity</label>
                                <div class="quantity-control-modern">
                                    <button class="quantity-btn-modern" id="decrease-qty">-</button>
                                    <input type="number" class="quantity-input-modern" id="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                                    <button class="quantity-btn-modern" id="increase-qty">+</button>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <?php if ($product['quantity'] > 0): ?>
                                    <button class="btn-add-to-cart-modern add-to-cart-detail" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn-add-to-cart-modern" disabled style="background: var(--danger-color); opacity: 0.7;">
                                        <i class="fas fa-times me-2"></i>Out of Stock
                                    </button>
                                <?php endif; ?>
                                
                                <a href="shop.php" class="btn-back-to-shop-modern">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Shop
                                </a>
                            </div>

                            <!-- Product Details -->
                            <div class="product-details-section">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="detail-item">
                                            <div class="detail-label">Category</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="detail-item">
                                            <div class="detail-label">SKU</div>
                                            <div class="detail-value">#<?php echo $product['id']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if ($db->num_rows($related_result) > 0): ?>
                <div class="related-products-section">
                    <h3 class="section-title">Related Products</h3>
                    <div class="row">
                        <?php while ($related = $db->fetch_assoc($related_result)): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="product-card">
                                    <div class="product-image" style="background-image: url('../uploads/products/<?php 
                                        // Get primary image from new system
                                        $related_primary_image = get_primary_product_image($related['id']);
                                        if ($related_primary_image) {
                                            $related_image_filename = $related_primary_image['image_filename'];
                                        } else {
                                            $related_image_filename = !empty($related['image']) ? $related['image'] : 'default.jpg';
                                        }
                                        echo $related_image_filename;
                                    ?>')">
                                        <div class="product-overlay">
                                            <div class="d-flex gap-2">
                                                <a href="product_detail.php?id=<?php echo $related['id']; ?>" 
                                                   class="btn btn-light btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <button class="btn btn-light btn-sm wishlist-btn" 
                                                        data-product-id="<?php echo $related['id']; ?>">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-body">
                                        <h6 class="product-title"><?php echo htmlspecialchars($related['name']); ?></h6>
                                        <?php if (!empty($related['description'])): ?>
                                        <p class="product-description text-muted small mb-2">
                                            <?php 
                                            $description = htmlspecialchars($related['description']);
                                            echo strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description;
                                            ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php 
                                        // Get promotions for related product
                                        $related_promotions = getProductPromotions($related['id'], $related['categorie_id']);
                                        $related_promotion_info = getPromotionInfo($related['sale_price'], $related_promotions);
                                        
                                        if ($related_promotion_info): ?>
                                            <div class="product-price">
                                                <span class="text-decoration-line-through text-muted me-2">LKR <?php echo number_format($related_promotion_info['original_price'], 2); ?></span>
                                                <span class="text-danger fw-bold">LKR <?php echo number_format($related_promotion_info['discounted_price'], 2); ?></span>
                                            </div>
                                            <div class="promotion-badge">
                                                <span class="badge bg-danger"><?php echo $related_promotion_info['promotion']['discount_value']; ?>% OFF</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="product-price">LKR <?php echo number_format($related['sale_price'], 2); ?></div>
                                        <?php endif; ?>
                                        <small class="product-category"><?php echo htmlspecialchars($related['category_name']); ?></small>
                                        
                                        <?php if ($related['quantity'] > 0): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-primary btn-sm w-100 add-to-cart-btn" 
                                                        data-product-id="<?php echo $related['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($related['name']); ?>">
                                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-3">
                                                <button class="btn btn-secondary btn-sm w-100" disabled>
                                                    <i class="fas fa-times me-2"></i>Out of Stock
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Price calculation variables
        <?php 
        $promotions = getProductPromotions($product['id'], $product['categorie_id']);
        $promotion_info = getPromotionInfo($product['sale_price'], $promotions);
        ?>
        const basePrice = <?php echo $promotion_info ? $promotion_info['discounted_price'] : $product['sale_price']; ?>;
        const originalPrice = <?php echo $promotion_info ? $promotion_info['original_price'] : $product['sale_price']; ?>;
        const hasPromotion = <?php echo $promotion_info ? 'true' : 'false'; ?>;
        
        // Function to update price display
        function updatePriceDisplay(quantity) {
            const totalPrice = basePrice * quantity;
            const totalOriginalPrice = originalPrice * quantity;
            
            const currentPriceElement = document.querySelector('.product-price-current');
            const originalPriceElement = document.querySelector('.product-price-original');
            
            if (currentPriceElement) {
                currentPriceElement.textContent = 'LKR ' + totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2});
            }
            
            if (originalPriceElement && hasPromotion) {
                originalPriceElement.textContent = 'LKR ' + totalOriginalPrice.toLocaleString('en-US', {minimumFractionDigits: 2});
            }
        }
        
        // Quantity controls
        document.getElementById('decrease-qty').addEventListener('click', function() {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                updatePriceDisplay(currentValue - 1);
            }
        });

        document.getElementById('increase-qty').addEventListener('click', function() {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max);
            if (currentValue < maxValue) {
                input.value = currentValue + 1;
                updatePriceDisplay(currentValue + 1);
            }
        });
        
        // Also update price when quantity input changes directly
        document.getElementById('quantity').addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            updatePriceDisplay(quantity);
        });

        // Add to cart from detail page
        document.querySelector('.add-to-cart-detail').addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const quantity = document.getElementById('quantity').value;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            this.disabled = true;
            
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    this.innerHTML = '<i class="fas fa-check me-2"></i>Added!';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-success');
                    
                    // Update cart count in header
                    const cartBadge = document.querySelector('.badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary');
                        this.disabled = false;
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                    this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                this.disabled = false;
            });
        });

        // Add to cart from related products
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
                this.disabled = true;
                
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        this.innerHTML = '<i class="fas fa-check me-2"></i>Added!';
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-success');
                        
                        // Update cart count in header
                        const cartBadge = document.querySelector('.badge');
                        if (cartBadge) {
                            cartBadge.textContent = data.cart_count;
                        }
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                            this.classList.remove('btn-success');
                            this.classList.add('btn-primary');
                            this.disabled = false;
                        }, 2000);
                    } else {
                        alert('Error: ' + data.message);
                        this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                    this.disabled = false;
                });
            });
        });

        document.querySelectorAll('.wishlist-btn').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.dataset.productId;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('ajax/add_to_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                        this.disabled = true;
                    } else {
                        alert(data.message);
                        this.innerHTML = '<i class="fas fa-heart"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Something went wrong');
                    this.innerHTML = '<i class="fas fa-heart"></i>';
                });
            });
        });
        
        // Simple image navigation system
        let currentImageIndex = 0;
        let totalImages = 0;
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const thumbnails = document.querySelectorAll('.thumbnail-item');
            totalImages = thumbnails.length;
            
            console.log('Initialized navigation with', totalImages, 'images');
            
            // Set up event listeners for navigation buttons
            const prevBtn = document.getElementById('prev-image');
            const nextBtn = document.getElementById('next-image');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    console.log('Previous button clicked');
                    if (currentImageIndex > 0) {
                        currentImageIndex--;
                        showImage(currentImageIndex);
                    }
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    console.log('Next button clicked');
                    if (currentImageIndex < totalImages - 1) {
                        currentImageIndex++;
                        showImage(currentImageIndex);
                    }
                });
            }
            
            updateNavigationState();
        });
        
        // Function to show specific image by index
        function showImage(index) {
            console.log('Showing image at index:', index);
            
            const thumbnails = document.querySelectorAll('.thumbnail-item');
            const mainImage = document.getElementById('main-product-image');
            
            if (thumbnails[index] && mainImage) {
                const imageFilename = thumbnails[index].getAttribute('data-image-filename');
                console.log('Image filename:', imageFilename);
                
                // Update main image
                mainImage.style.backgroundImage = `url('../uploads/products/${imageFilename}')`;
                
                // Update active thumbnail
                thumbnails.forEach((thumb, i) => {
                    thumb.classList.toggle('active', i === index);
                });
                
                // Update counter
                const currentSpan = document.getElementById('current-image');
                if (currentSpan) {
                    currentSpan.textContent = index + 1;
                }
                
                updateNavigationState();
                
                // Trigger event for magnify to update
                window.dispatchEvent(new Event('imageChanged'));
            }
        }
        
        // Update navigation button states
        function updateNavigationState() {
            const prevBtn = document.getElementById('prev-image');
            const nextBtn = document.getElementById('next-image');
            
            if (prevBtn) {
                prevBtn.disabled = currentImageIndex === 0;
                prevBtn.style.opacity = currentImageIndex === 0 ? '0.5' : '1';
            }
            
            if (nextBtn) {
                nextBtn.disabled = currentImageIndex === totalImages - 1;
                nextBtn.style.opacity = currentImageIndex === totalImages - 1 ? '0.5' : '1';
            }
        }
        
        // Function to change main product image (for thumbnail clicks)
        window.changeMainImage = function(imageFilename, thumbnailElement) {
            const thumbnails = document.querySelectorAll('.thumbnail-item');
            const index = Array.from(thumbnails).indexOf(thumbnailElement);
            
            if (index !== -1) {
                currentImageIndex = index;
                showImage(currentImageIndex);
            }
        };
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentImageIndex > 0) {
                e.preventDefault();
                currentImageIndex--;
                showImage(currentImageIndex);
            } else if (e.key === 'ArrowRight' && currentImageIndex < totalImages - 1) {
                e.preventDefault();
                currentImageIndex++;
                showImage(currentImageIndex);
            }
        });
        
        // Hover Magnify Effect
        (function() {
            const imageWrapper = document.getElementById('main-product-image-wrapper');
            const magnifyLens = document.getElementById('magnify-lens');
            const mainImage = document.getElementById('main-product-image');
            
            if (!imageWrapper || !magnifyLens || !mainImage) return;
            
            // Detect touch device
            const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            if (isTouchDevice) return; // Disable on touch devices
            
            let isMagnifying = false;
            let currentImageBg = mainImage.style.backgroundImage;
            const zoomLevel = 2.5; // Magnification level
            
            imageWrapper.addEventListener('mouseenter', function(e) {
                isMagnifying = true;
                magnifyLens.style.display = 'block';
                updateMagnifier(e);
            });
            
            imageWrapper.addEventListener('mouseleave', function(e) {
                isMagnifying = false;
                magnifyLens.style.display = 'none';
            });
            
            imageWrapper.addEventListener('mousemove', function(e) {
                if (isMagnifying) {
                    updateMagnifier(e);
                }
            });
            
            function updateMagnifier(e) {
                const rect = imageWrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const lensSize = 180;
                const lensSizeHalf = lensSize / 2;
                
                // Position the lens centered on cursor
                let lensX = x - lensSizeHalf;
                let lensY = y - lensSizeHalf;
                
                // Constrain lens within image bounds
                lensX = Math.max(0, Math.min(lensX, rect.width - lensSize));
                lensY = Math.max(0, Math.min(lensY, rect.height - lensSize));
                
                magnifyLens.style.left = lensX + 'px';
                magnifyLens.style.top = lensY + 'px';
                
                // Calculate background size for zoom
                const bgSizeX = rect.width * zoomLevel;
                const bgSizeY = rect.height * zoomLevel;
                
                // Calculate exact background position to show zoomed area under cursor
                const percentX = (x / rect.width) * 100;
                const percentY = (y / rect.height) * 100;
                
                magnifyLens.style.backgroundImage = mainImage.style.backgroundImage;
                magnifyLens.style.backgroundSize = bgSizeX + 'px ' + bgSizeY + 'px';
                magnifyLens.style.backgroundPosition = percentX + '% ' + percentY + '%';
            }
            
            // Update magnifier when image changes
            window.addEventListener('imageChanged', function() {
                currentImageBg = mainImage.style.backgroundImage;
            });
        })();
    </script>

    <style>
        /* Image Wrapper for Magnify Effect */
        .product-image-wrapper {
            position: relative;
            cursor: zoom-in;
            overflow: hidden;
        }
        
        /* Modern Product Detail Styling */
        .product-image-large {
            height: 500px;
            background-size: cover;
            background-position: center;
            border-radius: var(--radius-xl);
            position: relative;
            transition: background-image var(--transition-normal);
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--border-light);
            overflow: hidden;
        }
        
        /* Magnify Lens */
        .magnify-lens {
            position: absolute;
            width: 180px;
            height: 180px;
            border: 3px solid rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            background-repeat: no-repeat;
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 0 0 3px var(--primary-color),
                0 8px 24px rgba(0, 0, 0, 0.4),
                inset 0 0 40px rgba(0, 0, 0, 0.1);
            pointer-events: none;
            z-index: 15;
            display: none;
            cursor: none;
            transition: all 0.1s ease-out;
            backdrop-filter: blur(2px);
        }
        
        .magnify-lens::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            height: 90%;
            border-radius: 50%;
            background: radial-gradient(circle, 
                transparent 0%, 
                transparent 70%, 
                rgba(255, 255, 255, 0.1) 71%,
                rgba(0, 0, 0, 0.05) 100%
            );
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .magnify-lens::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 2px;
            height: 80%;
            background: linear-gradient(to bottom, 
                transparent, 
                rgba(0, 0, 0, 0.2) 25%, 
                rgba(255, 255, 255, 0.8) 48%,
                rgba(255, 255, 255, 0.8) 52%,
                rgba(0, 0, 0, 0.2) 75%,
                transparent
            );
        }
        
        /* Magnify Hint */
        .magnify-hint {
            position: absolute;
            bottom: var(--spacing-lg);
            left: 50%;
            transform: translateX(-50%);
            background: rgba(26, 26, 26, 0.9);
            color: var(--text-white);
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-xl);
            font-size: var(--font-size-sm);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            z-index: 10;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeHint 3s ease-in-out forwards;
            box-shadow: var(--shadow-lg);
            pointer-events: none;
        }
        
        .magnify-hint i {
            font-size: var(--font-size-base);
        }
        
        @keyframes fadeHint {
            0% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            70% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
        }
        
        .product-image-large::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.1) 0%, transparent 50%, rgba(0,0,0,0.1) 100%);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }
        
        .product-image-large:hover::before {
            opacity: 1;
        }
        
        .product-thumbnails {
            background: var(--bg-secondary);
            border-top: 2px solid var(--border-color);
            padding: var(--spacing-lg) !important;
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
        }
        
        .thumbnail-item {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            transition: all var(--transition-normal);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }
        
        .thumbnail-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--primary-color);
            opacity: 0;
            transition: opacity var(--transition-normal);
            z-index: 1;
        }
        
        .thumbnail-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .thumbnail-item:hover::before {
            opacity: 0.1;
        }
        
        .thumbnail-item.active {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.15);
            transform: translateY(-2px);
        }
        
        .thumbnail-item.active::before {
            opacity: 0.15;
        }
        
        .thumbnail-item img {
            transition: all var(--transition-normal);
            position: relative;
            z-index: 2;
        }
        
        .thumbnail-item:hover img {
            transform: scale(1.05);
        }
        
        /* Modern Navigation Arrows */
        .image-navigation {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 var(--spacing-lg);
            pointer-events: none;
            z-index: 10;
        }
        
        .nav-arrow {
            background: var(--bg-primary);
            border: 2px solid var(--border-dark);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-normal);
            pointer-events: all;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(10px);
        }
        
        .nav-arrow:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: scale(1.1);
            box-shadow: var(--shadow-xl);
        }
        
        .nav-arrow:hover i {
            color: var(--text-white);
        }
        
        .nav-arrow i {
            color: var(--text-secondary);
            font-size: var(--font-size-lg);
            transition: color var(--transition-normal);
        }
        
        .nav-arrow:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            background: var(--bg-tertiary);
            border-color: var(--border-color);
        }
        
        .nav-arrow:disabled:hover {
            transform: none;
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            box-shadow: var(--shadow-lg);
        }
        
        .nav-arrow:disabled:hover i {
            color: var(--text-secondary);
        }
        
        /* Modern Image Counter */
        .image-counter {
            position: absolute;
            bottom: var(--spacing-lg);
            left: 50%;
            transform: translateX(-50%);
            background: rgba(26, 26, 26, 0.9);
            color: var(--text-white);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-xl);
            font-size: var(--font-size-sm);
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            letter-spacing: 0.5px;
        }
        
        /* Modern Breadcrumb */
        .breadcrumb {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md) var(--spacing-lg);
            border: 1px solid var(--border-color);
            margin-bottom: var(--spacing-xl);
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-normal);
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        /* Modern Product Info Card */
        .product-info-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--border-light);
            overflow: hidden;
            transition: all var(--transition-normal);
        }
        
        .product-info-card:hover {
            box-shadow: var(--shadow-xl);
            border-color: var(--border-dark);
            transform: translateY(-2px);
        }
        
        .product-info-card .card-body {
            padding: var(--spacing-xl);
        }
        
        .product-title {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
            line-height: 1.2;
        }
        
        .product-category-badge {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-sm);
            font-weight: 500;
            border: 1px solid var(--border-color);
            display: inline-block;
            margin-bottom: var(--spacing-lg);
        }
        
        .product-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
        }
        
        .product-price-section {
            background: var(--bg-secondary);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            margin-bottom: var(--spacing-lg);
        }
        
        .product-price-current {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-xs);
        }
        
        .product-price-original {
            font-size: var(--font-size-lg);
            color: var(--text-muted);
            text-decoration: line-through;
            margin-left: var(--spacing-md);
        }
        
        .promotion-badge-large {
            background: var(--danger-color);
            color: var(--text-white);
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-sm);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-top: var(--spacing-sm);
            box-shadow: var(--shadow-sm);
        }
        
        .stock-status {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .stock-badge {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-sm);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stock-badge.in-stock {
            background: var(--success-color);
            color: var(--text-white);
        }
        
        .stock-badge.out-of-stock {
            background: var(--danger-color);
            color: var(--text-white);
        }
        
        .stock-badge.low-stock {
            background: var(--warning-color);
            color: var(--text-white);
        }
        
        .quantity-section {
            background: var(--bg-secondary);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            margin-bottom: var(--spacing-lg);
        }
        
        .quantity-control-modern {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            justify-content: center;
        }
        
        .quantity-btn-modern {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-secondary);
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-lg);
        }
        
        .quantity-btn-modern:hover {
            background: var(--primary-color);
            color: var(--text-white);
            border-color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .quantity-input-modern {
            width: 80px;
            text-align: center;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-sm);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-weight: 600;
            font-size: var(--font-size-base);
        }
        
        .quantity-input-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
            outline: none;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .btn-add-to-cart-modern {
            height: 60px;
            border-radius: var(--radius-lg);
            font-size: var(--font-size-lg);
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            border: none;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: var(--shadow-lg);
            color: white;
        }
        
        .btn-add-to-cart-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-add-to-cart-modern:hover::before {
            left: 100%;
        }
        
        .btn-add-to-cart-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }
        
        .btn-back-to-shop-modern {
            height: 50px;
            border-radius: var(--radius-lg);
            font-size: var(--font-size-base);
            font-weight: 600;
            transition: all var(--transition-normal);
            border: 2px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .btn-back-to-shop-modern:hover {
            background: var(--bg-tertiary);
            color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        /* Modern Product Details Section */
        .product-details-section {
            background: var(--bg-secondary);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            margin-top: var(--spacing-lg);
        }
        
        .detail-item {
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: var(--font-size-sm);
        }
        
        .detail-value {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
        }
        
        /* Modern Related Products */
        .related-products-section {
            margin-top: var(--spacing-2xl);
        }
        
        .section-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xl);
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: var(--radius-sm);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .product-image-large {
                height: 350px;
            }
            
            .magnify-hint {
                display: none;
            }
            
            .product-image-wrapper {
                cursor: default;
            }
            
            .product-title {
                font-size: var(--font-size-2xl);
            }
            
            .product-price-current {
                font-size: var(--font-size-2xl);
            }
            
            .nav-arrow {
                width: 40px;
                height: 40px;
            }
            
            .nav-arrow i {
                font-size: var(--font-size-base);
            }
            
            .quantity-control-modern {
                flex-direction: column;
                gap: var(--spacing-sm);
            }
            
            .action-buttons {
                gap: var(--spacing-sm);
            }
            
            .btn-add-to-cart-modern {
                height: 50px;
                font-size: var(--font-size-base);
            }
        }
    </style>
</body>
</html> 