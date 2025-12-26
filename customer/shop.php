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

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build the query
$where_conditions = ["p.quantity > 0"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "p.name LIKE '%" . $db->escape($search) . "%'";
}

if ($category > 0) {
    $where_conditions[] = "p.categorie_id = " . $category;
}

if ($min_price > 0) {
    $where_conditions[] = "p.sale_price >= " . $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "p.sale_price <= " . $max_price;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p 
              LEFT JOIN categories c ON p.categorie_id = c.id 
              WHERE " . $where_clause;
$count_result = $db->query($count_sql);
$total_products = $db->fetch_assoc($count_result)['total'];

// Pagination
$per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_products / $per_page);

// Get products
switch($sort) {
    case 'price_low':
        $order_by = 'p.sale_price ASC';
        break;
    case 'price_high':
        $order_by = 'p.sale_price DESC';
        break;
    case 'name':
        $order_by = 'p.name ASC';
        break;
    case 'newest':
        $order_by = 'p.date DESC';
        break;
    default:
        $order_by = 'p.name ASC';
        break;
}

$products_sql = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.categorie_id = c.id 
                 WHERE " . $where_clause . " 
                 ORDER BY " . $order_by . " 
                 LIMIT " . $per_page . " OFFSET " . $offset;
$products_result = $db->query($products_sql);

// Get categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $db->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Swisswood Works</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/modern-customer.css" rel="stylesheet">
    
    <style>
        .product-card {
            background-color: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #8B0000 !important;
            overflow: hidden;
            height: 100%;
            position: relative;
            transition: all var(--transition-normal);
        }
        
        /* More specific selector to ensure border visibility */
        .container .row .col-lg-3 .product-card,
        .container .row .col-md-4 .product-card,
        .container .row .col-sm-6 .product-card {
            border: 1px solid #8B0000 !important;
            outline: 2px solid #8B0000 !important;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border: 6px solid #660000 !important;
        }
        
        .product-image {
            height: 280px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        
        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .product-card:hover .product-overlay {
            opacity: 1;
        }
        
        .product-body {
            padding: var(--spacing-xl);
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .product-title {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-lg);
            line-height: 1.4;
        }
        
        .product-price {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-sm);
        }
        
        .product-category {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-md);
            font-weight: 500;
        }
        
        .promotion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }
        
        .wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.9);
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .wishlist-btn:hover, .wishlist-btn.active {
            background: #dc3545;
            color: white;
            transform: scale(1.1);
        }
        
        .add-to-cart-btn {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md) var(--spacing-lg);
            font-weight: 700;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            color: var(--text-white);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .add-to-cart-btn:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--text-white);
        }
        
        .add-to-cart-btn:disabled {
            background: var(--ash-400);
            border-color: var(--ash-400);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .search-filter {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 26, 26, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-outline-secondary {
            border: 2px solid #666;
            color: #666;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background: #666;
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: 2px solid #e0e0e0;
            color: #666;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }
        
        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--border-dark);
            color: white;
        }
        
        .pagination .page-link:hover {
            border-color: var(--border-dark);
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #28a745;
            min-width: 300px;
        }
        
        .toast.error {
            border-left-color: #dc3545;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            display: none;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .cart-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 10000;
            text-align: center;
            display: none;
            max-width: 400px;
            width: 90%;
        }
        
        .cart-notification.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translate(-50%, -60%); opacity: 0; }
            to { transform: translate(-50%, -50%); opacity: 1; }
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 16px 0;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #1a1a1a;
            background: white;
            color: #1a1a1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        
        .quantity-btn:hover {
            background: #1a1a1a;
            color: white;
            transform: scale(1.1);
        }
        
        .quantity-input {
            width: 70px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        /* Shop Hero Section - Modern Dashboard Style */
        .shop-hero {
            background: #b7bdbb;
            color: var(--text-white);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--border-dark);
            background-image: url('../libs/images/h5.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .shop-hero::before {
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
        
        .breadcrumb-nav {
            margin-bottom: var(--spacing-lg);
        }
        
        .breadcrumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: var(--spacing-sm) var(--spacing-lg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb-item.active {
            color: var(--text-white);
            font-weight: 600;
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
        
        .hero-stats {
            display: flex;
            gap: var(--spacing-xl);
            flex-wrap: wrap;
        }
        
        .hero-stat {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-sm);
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-xl);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hero-stat i {
            color: var(--text-white);
            font-size: var(--font-size-base);
        }
        
        .hero-rating {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .rating-card {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #1a1a1a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .rating-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-color: #1a1a1a;
        }
        
        .rating-card i {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        /* Modern Filter Card */
        .modern-filter-card {
            background-color: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--border-light);
            overflow: hidden;
            margin-bottom: var(--spacing-xl);
            transition: all var(--transition-normal);
        }
        
        .modern-filter-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
            border-color: var(--border-dark);
        }
        
        .filter-header {
            background-color: var(--bg-ash);
            padding: var(--spacing-xl);
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .filter-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .filter-title i {
            color: var(--primary-color);
        }
        
        .filter-actions {
            display: flex;
            gap: var(--spacing-sm);
        }
        
        .filter-body {
            padding: var(--spacing-xl);
        }
        
        .search-section {
            margin-bottom: 1.5rem;
        }
        
        .search-label {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-label i {
            color: #666;
        }
        
        .modern-input, .modern-select {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md) var(--spacing-lg);
            font-size: var(--font-size-sm);
            transition: all var(--transition-normal);
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .modern-input:focus, .modern-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
            outline: none;
        }
        
        .search-btn {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md) var(--spacing-lg);
            color: var(--text-white);
            font-weight: 600;
            transition: all var(--transition-normal);
        }
        
        .search-btn:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .filter-footer {
            padding-top: var(--spacing-lg);
            border-top: 2px solid var(--border-light);
            margin-top: var(--spacing-lg);
        }
        
        .filter-results {
            margin-left: auto;
        }
        
        .filter-results .badge {
            background: var(--info-color) !important;
            color: var(--text-white) !important;
            font-weight: 600;
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-xl);
            font-size: var(--font-size-sm);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .hero-title {
                font-size: var(--font-size-3xl);
            }
            
            .product-image {
                height: 250px;
            }
        }
        
        @media (max-width: 992px) {
            .hero-title {
                font-size: var(--font-size-2xl);
            }
            
            .hero-subtitle {
                font-size: var(--font-size-base);
            }
            
            .product-image {
                height: 220px;
            }
            
            .product-body {
                padding: var(--spacing-lg);
            }
            
            .product-title {
                font-size: var(--font-size-base);
            }
            
            .product-price {
                font-size: var(--font-size-lg);
            }
        }
        
        @media (max-width: 768px) {
            .shop-hero {
                padding: var(--spacing-xl) 0;
                background-attachment: scroll;
            }
            
            .hero-title {
                font-size: var(--font-size-2xl);
            }
            
            .hero-subtitle {
                font-size: var(--font-size-base);
            }
            
            .hero-stats {
                flex-direction: column;
                gap: var(--spacing-md);
            }
            
            .hero-rating {
                justify-content: center;
                margin-top: var(--spacing-xl);
            }
            
            .filter-header {
                flex-direction: column;
                gap: var(--spacing-md);
                text-align: center;
            }
            
            .filter-body {
                padding: var(--spacing-lg);
            }
            
            .search-section .row {
                flex-direction: column;
            }
            
            .search-section .col-lg-4,
            .search-section .col-lg-2 {
                width: 100%;
                margin-bottom: var(--spacing-md);
            }
            
            .product-image {
                height: 200px;
            }
            
            .product-body {
                padding: var(--spacing-md);
            }
            
            .product-title {
                font-size: var(--font-size-base);
            }
            
            .product-price {
                font-size: var(--font-size-lg);
            }
            
            .add-to-cart-btn {
                padding: var(--spacing-sm) var(--spacing-md);
                font-size: var(--font-size-xs);
            }
            
            .quantity-btn {
                width: 35px;
                height: 35px;
                font-size: var(--font-size-base);
            }
            
            .quantity-input {
                width: 60px;
                padding: var(--spacing-sm);
                font-size: var(--font-size-sm);
            }
        }
        
        @media (max-width: 576px) {
            .shop-hero {
                padding: var(--spacing-lg) 0;
                background-attachment: scroll;
            }
            
            .hero-title {
                font-size: var(--font-size-xl);
            }
            
            .hero-subtitle {
                font-size: var(--font-size-sm);
            }
            
            .filter-body {
                padding: var(--spacing-md);
            }
            
            .modern-input, .modern-select {
                padding: var(--spacing-sm) var(--spacing-md);
                font-size: var(--font-size-xs);
            }
            
            .search-btn {
                padding: var(--spacing-sm) var(--spacing-md);
            }
            
            .product-image {
                height: 180px;
            }
            
            .product-body {
                padding: var(--spacing-sm);
            }
            
            .product-title {
                font-size: var(--font-size-sm);
            }
            
            .product-price {
                font-size: var(--font-size-base);
            }
            
            .add-to-cart-btn {
                padding: var(--spacing-xs) var(--spacing-sm);
                font-size: var(--font-size-xs);
            }
            
            .quantity-btn {
                width: 32px;
                height: 32px;
                font-size: var(--font-size-sm);
            }
            
            .quantity-input {
                width: 50px;
                padding: var(--spacing-xs);
                font-size: var(--font-size-xs);
            }
        }
    </style>
</head>
<body class="customer-portal">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Enhanced Page Header -->
    <section class="shop-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <h1 class="hero-title">Discover Premium Products</h1>
                        <p class="hero-subtitle">Handcrafted furniture and premium products crafted with precision and care, designed for modern living</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Search and Filters -->
    <section class="py-4">
        <div class="container">
            <div class="modern-filter-card">
                <div class="filter-header">
                    <h5 class="filter-title">
                        <i class="fas fa-filter me-2"></i>Filter & Search
                    </h5>
                    <!-- <div class="filter-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="toggleFilters">
                            <i class="fas fa-sliders-h me-1"></i>Advanced
                        </button>
                    </div> -->
                </div>
                <div class="filter-body">
                    <form method="GET" action="" class="filter-form">
                        <div class="search-section">
                            <div class="search-label">
                                <i class="fas fa-search me-1"></i>Search Products
                            </div>
                            <div class="row g-3">
                                <!-- Search Bar -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control modern-input" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                                        <button type="submit" class="btn btn-primary search-btn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Category Filter -->
                                <div class="col-lg-2 col-md-6">
                                    <select class="form-select modern-select" id="category" name="category">
                                        <option value="0">All Categories</option>
                                        <?php while ($cat = $db->fetch_assoc($categories_result)): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <!-- Price Range -->
                                <div class="col-lg-2 col-md-6">
                                    <input type="number" class="form-control modern-input" id="min_price" name="min_price" 
                                           value="<?php echo $min_price; ?>" min="0" step="0.01" placeholder="$ Min Price">
                                </div>
                                
                                <div class="col-lg-2 col-md-6">
                                    <input type="number" class="form-control modern-input" id="max_price" name="max_price" 
                                           value="<?php echo $max_price; ?>" min="0" step="0.01" placeholder="$ Max Price">
                                </div>
                                
                                <!-- Sort Options -->
                                <div class="col-lg-2 col-md-6">
                                    <select class="form-select modern-select" id="sort" name="sort">
                                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="filter-footer">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                                <a href="shop.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear All
                                </a>
                                <div class="filter-results">
                                    <span class="badge bg-info"><?php echo $total_products; ?> products found</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="py-4">
        <div class="container">
            <?php if ($db->num_rows($products_result) > 0): ?>
                <div class="row">
                    <?php while ($product = $db->fetch_assoc($products_result)): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                                <div class="product-image" style="background-image: url('<?php
                                    // Get primary image from new system
                                    $primary_image = get_primary_product_image($product['id']);
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
                                ?>')">
                                    
                                    <!-- Wishlist Button -->
                                    <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    
                                    <!-- Stock Badge -->
                                    <?php if ($product['quantity'] <= 10 && $product['quantity'] > 0): ?>
                                        <div class="stock-badge">
                                            <span class="badge bg-warning">Only <?php echo $product['quantity']; ?> left</span>
                                        </div>
                                    <?php elseif ($product['quantity'] == 0): ?>
                                        <div class="stock-badge">
                                            <span class="badge bg-danger">Out of Stock</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Promotion Badge -->
                                    <?php 
                                    $promotions = getProductPromotions($product['id'], $product['categorie_id']);
                                    $promotion_info = getPromotionInfo($product['sale_price'], $promotions);
                                    
                                    if ($promotion_info): ?>
                                        <div class="promotion-badge">
                                            <span class="badge bg-danger"><?php echo $promotion_info['promotion']['discount_value']; ?>% OFF</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Product Overlay -->
                                    <div class="product-overlay">
                                        <div class="d-flex gap-2">
                                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-light btn-sm">
                                                <i class="fas fa-eye me-1"></i>Quick View
                                            </a>
                                            <?php if ($product['quantity'] > 0): ?>
                                                <button class="btn btn-success btn-sm quick-add-btn" 
                                                        data-product-id="<?php echo $product['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-max-quantity="<?php echo $product['quantity']; ?>">
                                                    <i class="fas fa-plus me-1"></i>Quick Add
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="product-body">
                                    <h6 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    
                                    <?php if (!empty($product['description'])): ?>
                                    <p class="product-description text-muted small mb-2">
                                        <?php 
                                        $description = htmlspecialchars($product['description']);
                                        echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                                        ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <!-- Price Display -->
                                    <div class="product-price">
                                        <?php if ($promotion_info): ?>
                                            <span class="text-decoration-line-through text-muted me-2">LKR <?php echo number_format($promotion_info['original_price'], 2); ?></span>
                                            <span class="text-danger fw-bold">LKR <?php echo number_format($promotion_info['discounted_price'], 2); ?></span>
                                        <?php else: ?>
                                            LKR <?php echo number_format($product['sale_price'], 2); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <small class="product-category">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['category_name']); ?>
                                    </small>
                                    
                                    <!-- Stock Info -->
                                    <div class="stock-info mt-2">
                                        <?php if ($product['quantity'] > 10): ?>
                                            <small class="text-success"><i class="fas fa-check-circle me-1"></i>In Stock (<?php echo $product['quantity']; ?> available)</small>
                                        <?php elseif ($product['quantity'] > 0): ?>
                                            <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Limited Stock (<?php echo $product['quantity']; ?> left)</small>
                                        <?php else: ?>
                                            <small class="text-danger"><i class="fas fa-times-circle me-1"></i>Out of Stock</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Add to Cart Section -->
                                    <?php if ($product['quantity'] > 0): ?>
                                        <div class="mt-3">
                                            <div class="quantity-selector">
                                                <button class="quantity-btn minus-btn" type="button">-</button>
                                                <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                                                <button class="quantity-btn plus-btn" type="button">+</button>
                                            </div>
                                            <button class="btn add-to-cart-btn w-100" 
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                    data-max-quantity="<?php echo $product['quantity']; ?>">
                                                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3">
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-times me-2"></i>Out of Stock
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm w-100 mt-2 notify-btn" 
                                                    data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-bell me-1"></i>Notify When Available
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Product pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h5>No Products Found</h5>
                    <p>Try adjusting your search criteria or browse all products</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Browse All Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Cart Notification Modal -->
    <div class="cart-notification" id="cartNotification">
        <div class="cart-notification-content">
            <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h5>Added to Cart!</h5>
            <p class="mb-3" id="cartNotificationMessage">Product has been added to your cart.</p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-outline-primary btn-sm" onclick="closeCartNotification()">Continue Shopping</button>
                <a href="cart.php" class="btn btn-primary btn-sm">View Cart</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Global variables
        let cartCount = 0;
        
        // Utility functions
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
        
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type} show`;
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
        
        function showCartNotification(productName, quantity) {
            const notification = document.getElementById('cartNotification');
            const message = document.getElementById('cartNotificationMessage');
            message.textContent = `${productName} (${quantity}) has been added to your cart.`;
            notification.classList.add('show');
        }
        
        function closeCartNotification() {
            document.getElementById('cartNotification').classList.remove('show');
        }
        
        function updateCartCount(count) {
            cartCount = count;
            const cartBadges = document.querySelectorAll('.cart-count, .badge');
            cartBadges.forEach(badge => {
                if (badge) badge.textContent = count;
            });
        }
        
        // Quantity selector functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('plus-btn')) {
                const input = e.target.parentElement.querySelector('.quantity-input');
                const max = parseInt(input.getAttribute('max'));
                const current = parseInt(input.value);
                if (current < max) {
                    input.value = current + 1;
                }
            }
            
            if (e.target.classList.contains('minus-btn')) {
                const input = e.target.parentElement.querySelector('.quantity-input');
                const current = parseInt(input.value);
                if (current > 1) {
                    input.value = current - 1;
                }
            }
        });
        
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const maxQuantity = parseInt(this.dataset.maxQuantity);
                const quantityInput = this.closest('.product-card').querySelector('.quantity-input');
                const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                
                if (quantity > maxQuantity) {
                    showToast(`Only ${maxQuantity} items available in stock`, 'error');
                    return;
                }
                
                // Show loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
                this.disabled = true;
                
                // Make AJAX request
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${quantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success animation
                        this.innerHTML = '<i class="fas fa-check me-2"></i>Added!';
                        this.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
                        
                        // Update cart count
                        updateCartCount(data.cart_count);
                        
                        // Show cart notification
                        showCartNotification(productName, quantity);
                        
                        // Reset quantity to 1
                        if (quantityInput) quantityInput.value = 1;
                        
                        // Reset button after animation
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.style.background = '';
                            this.disabled = false;
                        }, 2000);
                        
                    } else {
                        showToast(data.message, 'error');
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                    this.innerHTML = originalContent;
                    this.disabled = false;
                });
            });
        });
        
        // Quick add functionality
        document.querySelectorAll('.quick-add-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                
                // Show loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
                this.disabled = true;
                
                // Make AJAX request with quantity 1
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = '<i class="fas fa-check me-1"></i>Added!';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-info');
                        
                        updateCartCount(data.cart_count);
                        showToast(`${productName} added to cart!`, 'success');
                        
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.classList.remove('btn-info');
                            this.classList.add('btn-success');
                            this.disabled = false;
                        }, 2000);
                        
                    } else {
                        showToast(data.message, 'error');
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                    this.innerHTML = originalContent;
                    this.disabled = false;
                });
            });
        });
        
        // Wishlist functionality
        document.querySelectorAll('.wishlist-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = this.dataset.productId;
                const icon = this.querySelector('i');
                const isInWishlist = icon.classList.contains('fas');
                
                // Optimistic UI update
                if (isInWishlist) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.classList.remove('active');
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.classList.add('active');
                }
                
                // Make AJAX request
                fetch('ajax/toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const action = data.action === 'added' ? 'added to' : 'removed from';
                        showToast(`Product ${action} wishlist`, 'success');
                    } else {
                        // Revert UI changes on error
                        if (isInWishlist) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            this.classList.add('active');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            this.classList.remove('active');
                        }
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert UI changes on error
                    if (isInWishlist) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.classList.add('active');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.classList.remove('active');
                    }
                    showToast('An error occurred. Please try again.', 'error');
                });
            });
        });
        
        // Notify when available functionality
        document.querySelectorAll('.notify-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                
                // Show loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Setting up...';
                this.disabled = true;
                
                // Simulate API call (implement actual notification system)
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-check me-1"></i>Notification Set';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-success');
                    
                    showToast('We\'ll notify you when this product is available!', 'success');
                    
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                        this.disabled = false;
                    }, 3000);
                }, 1000);
            });
        });
        
        // Auto-close cart notification after 5 seconds
        setTimeout(() => {
            const notification = document.getElementById('cartNotification');
            if (notification.classList.contains('show')) {
                closeCartNotification();
            }
        }, 5000);
        
        // Initialize wishlist states (check existing wishlist items)
        document.addEventListener('DOMContentLoaded', function() {
            // This would typically load from server
            // For now, we'll just set up the UI
            
            // Load existing cart count
            fetch('ajax/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount(data.count);
                    }
                })
                .catch(error => console.error('Error loading cart count:', error));
        });
    </script>
</body>
</html> 