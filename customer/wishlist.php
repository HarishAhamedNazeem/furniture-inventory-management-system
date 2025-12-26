<?php
session_start();

// Define necessary constants for database connection
define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT.DS);

require_once('../includes/config.php');
require_once('../includes/database.php');
require_once('../includes/product_image_functions.php');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch wishlist products
$sql = "SELECT w.id AS wishlist_id, p.*
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.customer_id = $customer_id";
$result = $db->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
    <style>
        /* Wishlist Hero Section - Modern Dashboard Style */
        .wishlist-hero {
            background: #b7bdbb;
            color: var(--text-white);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--border-dark);
            background-image: url('../libs/images/h7.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .wishlist-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.2) 100%);
            opacity: 0.7;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: var(--font-size-4xl);
            font-weight: 700;
            color: var(--text-white);
            margin-bottom: var(--spacing-sm);
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: var(--font-size-lg);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
            max-width: 600px;
        }
        
        .wishlist-actions {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition-normal);
            border: 2px solid transparent;
        }
        
        .action-btn.primary {
            background: var(--primary-color);
            color: var(--text-white);
            border-color: var(--primary-color);
        }
        
        .action-btn.primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--text-white);
        }
    </style>
</head>
<body class="customer-portal">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Enhanced Page Header -->
    <section class="wishlist-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <h1 class="hero-title">My Wishlist</h1>
                        <p class="hero-subtitle">Save products for future purchase and never miss out on your favorites</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="wishlist-actions">
                        <a href="shop.php" class="action-btn primary">
                            <i class="fas fa-store"></i>
                            <span>Continue Shopping</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Wishlist Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($db->num_rows($result) > 0): ?>
                <div class="row">
                    <?php while ($item = $db->fetch_assoc($result)): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="product-card">
                                <div class="product-image" style="background-image: url('<?php
                                    // Get primary image from new system
                                    $primary_image = get_primary_product_image($item['id']);
                                    if ($primary_image) {
                                        $filename = $primary_image['image_filename'];
                                        $image_path = "../uploads/products/" . $filename;
                                        if (!file_exists($image_path)) {
                                            $image_path = "../uploads/products/default.jpg";
                                        }
                                    } else {
                                        $image_path = "../uploads/products/default.jpg";
                                    }
                                    echo $image_path;
                                ?>')"></div>
                                <div class="product-body">
                                    <h6 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <div class="product-price">LKR <?php echo number_format($item['sale_price'], 2); ?></div>
                                    <div class="mt-3 d-flex justify-content-between">
                                        <button class="btn btn-sm btn-primary add-to-cart-btn" data-product-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-shopping-cart"></i>Add to Cart
                                        </button>
                                        <a href="remove_wishlist.php?id=<?php echo $item['wishlist_id']; ?>" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-modern">
                    <div class="empty-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h6>Your Wishlist is Empty</h6>
                    <p>Start adding products to your wishlist to save them for later</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i>Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $('.add-to-cart-btn').click(function () {
        var productId = $(this).data('product-id');

        $.ajax({
            url: 'ajax/add_to_cart.php',
            type: 'POST',
            data: { product_id: productId },
            success: function (response) {
                var data = JSON.parse(response);
                if (data.success) {
                    alert('Product added to cart successfully!');
                    // Optional: update cart badge
                    if ($('.badge.bg-danger').length) {
                        $('.badge.bg-danger').text(data.cart_count);
                    } else {
                        $('.fa-shopping-cart')
                            .parent()
                            .append('<span class="badge bg-danger ms-1">' + data.cart_count + '</span>');
                    }
                } else {
                    alert(data.message);
                }
            },
            error: function () {
                alert('Something went wrong while adding to cart.');
            }
        });
    });
});
</script>
</body>
</html> 