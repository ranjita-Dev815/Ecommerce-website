<?php
session_start();
include 'db.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get search query
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Get category filter
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';

// Get price range filter
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;

// Get rating filter
$min_rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Get sort option
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT p.*, 
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as review_count
          FROM products p
          LEFT JOIN reviews r ON p.id = r.product_id
          WHERE 1=1";

// Apply search filter
if (!empty($search)) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

// Apply category filter
if (!empty($category_filter)) {
    $query .= " AND p.category = '$category_filter'";
}

// Apply price filter
$query .= " AND p.price BETWEEN $min_price AND $max_price";

// Group by product
$query .= " GROUP BY p.id";

// Apply rating filter
if ($min_rating > 0) {
    $query .= " HAVING avg_rating >= $min_rating";
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $query .= " ORDER BY review_count DESC";
        break;
    case 'rating':
        $query .= " ORDER BY avg_rating DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.id DESC";
        break;
}

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching products: " . mysqli_error($conn));
}

// Get all categories for filter
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);

// Get wishlist items if user is logged in
$wishlist_items = [];
if ($is_logged_in) {
    $wishlist_query = "SELECT product_id FROM wishlist WHERE user_id = '$user_id'";
    $wishlist_result = mysqli_query($conn, $wishlist_query);
    while ($row = mysqli_fetch_assoc($wishlist_result)) {
        $wishlist_items[] = $row['product_id'];
    }
}

// ✅ IMPORTANT: Function to fix image paths
function getImagePath($image_path) {
    // If empty, return placeholder
    if (empty($image_path)) {
        return 'https://via.placeholder.com/280x220?text=No+Image';
    }
    
    // If it already starts with uploads/, use it as is
    if (strpos($image_path, 'uploads/') === 0) {
        return $image_path;
    }
    
    // If it's just a filename, add uploads/ prefix
    if (strpos($image_path, '/') === false) {
        return 'uploads/' . $image_path;
    }
    
    // If it has admin/uploads/, remove admin/
    if (strpos($image_path, 'admin/uploads/') !== false) {
        return str_replace('admin/uploads/', 'uploads/', $image_path);
    }
    
    // Otherwise return as is
    return $image_path;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Online Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.98);
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }

        /* Search Bar */
        .search-container {
            flex: 1;
            max-width: 500px;
            margin: 0 30px;
            position: relative;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .icon-btn {
            position: relative;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        /* Admin Button Style */
        .admin-btn {
            background: linear-gradient(135deg, #1a1d29 0%, #2d3142 100%);
        }

        .admin-btn:hover {
            background: linear-gradient(135deg, #2d3142 0%, #1a1d29 100%);
        }

        .badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* Category Navigation */
        .category-nav {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            border-top: 1px solid #e0e0e0;
        }

        .category-nav::-webkit-scrollbar {
            height: 5px;
        }

        .category-nav::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .category-nav::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .category-link {
            padding: 8px 20px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s;
        }

        .category-link:hover,
        .category-link.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* 🔥 Offer Banner Section */
        .offer-banner-section {
            position: relative;
            max-width: 100%;
            height: 400px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .offer-slider {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .offer-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: none;
        }

        .offer-slide.active {
            opacity: 1;
            display: block;
        }

        .offer-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .offer-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            z-index: 10;
            width: 80%;
        }

        .offer-content h2 {
            font-size: 48px;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease;
        }

        .offer-content p {
            font-size: 20px;
            margin-bottom: 25px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            animation: fadeInUp 1.2s ease;
        }

        .offer-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            transition: all 0.3s;
            animation: fadeInUp 1.4s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .offer-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.6);
        }

        /* Slider Controls */
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 15px 20px;
            cursor: pointer;
            font-size: 24px;
            z-index: 20;
            transition: all 0.3s;
            border-radius: 5px;
        }

        .slider-btn:hover {
            background: rgba(255, 255, 255, 0.6);
        }

        .slider-btn.prev {
            left: 20px;
        }

        .slider-btn.next {
            right: 20px;
        }

        /* Slider Dots */
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 20;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot:hover,
        .dot.active {
            background: white;
            transform: scale(1.2);
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.9;
        }

        /* 🔥 NEW: Category Cards Section */
        .category-cards-section {
            margin-bottom: 40px;
        }

        .section-title {
            color: white;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }

        .category-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 25px 15px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .category-card.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: 3px solid #ffd700;
        }

        .category-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .category-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .category-count {
            font-size: 13px;
            opacity: 0.8;
        }

        .category-card:hover .category-count,
        .category-card.active .category-count {
            opacity: 1;
        }

        /* Filters Section */
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .filters-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #666;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-input {
            width: 100px;
        }

        .filter-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
        }

        .clear-filters {
            background: #ff4757;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }

        /* Results Info */
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: white;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        /* Wishlist Heart */
        .wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 10;
        }

        .wishlist-btn:hover {
            transform: scale(1.1);
        }

        .wishlist-btn.active {
            color: #ff4757;
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            cursor: pointer;
            background: #f5f5f5;
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            cursor: pointer;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-name:hover {
            color: #667eea;
        }

        /* Star Rating */
        .rating-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .stars {
            color: #ffc107;
            font-size: 16px;
        }

        .rating-text {
            font-size: 13px;
            color: #666;
        }

        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 13px;
        }

        .btn-cart {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-cart:hover {
            background: #667eea;
            color: white;
        }

        .btn-buy {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .no-products {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            grid-column: 1 / -1;
        }

        .no-products-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        /* Modal for Product Detail */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #ff4757;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            z-index: 10;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }

        .modal-image {
            width: 100%;
            border-radius: 15px;
        }

        .reviews-section {
            padding: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .review-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .review-item {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .review-votes {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .vote-btn {
            padding: 5px 15px;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }

        /* 🔥 Responsive Design for Mobile & Laptop */
        @media (max-width: 1024px) {
            .offer-banner-section {
                height: 350px;
            }

            .offer-content h2 {
                font-size: 36px;
            }

            .offer-content p {
                font-size: 18px;
            }

            .offer-btn {
                padding: 12px 30px;
                font-size: 16px;
            }

            .category-cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .category-icon {
                font-size: 40px;
            }

            .category-name {
                font-size: 16px;
            }
        }

        @media (max-width: 768px) {
            .offer-banner-section {
                height: 300px;
            }

            .offer-content h2 {
                font-size: 28px;
            }

            .offer-content p {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .offer-btn {
                padding: 10px 25px;
                font-size: 15px;
            }

            .slider-btn {
                padding: 10px 15px;
                font-size: 20px;
            }

            .slider-btn.prev {
                left: 10px;
            }

            .slider-btn.next {
                right: 10px;
            }
            .header-top {
                flex-direction: column;
                gap: 15px;
            }

            .search-container {
                max-width: 100%;
                margin: 0;
            }

            .logo {
                font-size: 22px;
            }

            .nav-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .icon-btn {
                padding: 8px 15px;
                font-size: 13px;
            }

            .page-header h1 {
                font-size: 32px;
            }

            .page-header p {
                font-size: 16px;
            }

            .section-title {
                font-size: 22px;
            }

            .category-cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .category-card {
                padding: 20px 10px;
            }

            .category-icon {
                font-size: 36px;
            }

            .category-name {
                font-size: 14px;
            }

            .category-count {
                font-size: 11px;
            }

            .filters-container {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .price-range {
                width: 100%;
            }

            .price-input {
                flex: 1;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }

            .product-image {
                height: 180px;
            }

            .product-info {
                padding: 15px;
            }

            .product-name {
                font-size: 15px;
            }

            .product-price {
                font-size: 20px;
            }

            .modal-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .results-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .offer-banner-section {
                height: 250px;
            }

            .offer-content h2 {
                font-size: 22px;
                margin-bottom: 10px;
            }

            .offer-content p {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .offer-btn {
                padding: 8px 20px;
                font-size: 14px;
            }

            .slider-btn {
                padding: 8px 12px;
                font-size: 18px;
            }

            .slider-dots {
                bottom: 15px;
            }

            .dot {
                width: 10px;
                height: 10px;
            }

            .category-cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .category-card {
                padding: 15px 8px;
            }

            .category-icon {
                font-size: 32px;
            }

            .category-name {
                font-size: 13px;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .product-card {
                border-radius: 10px;
            }

            .product-image {
                height: 150px;
            }

            .wishlist-btn {
                width: 35px;
                height: 35px;
                font-size: 16px;
                top: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-top">
                <a href="index.php" class="logo">
                    🛒 ShopSphere
                </a>

                <!-- Search Bar -->
                <div class="search-container">
                    <form method="GET" action="shop.php" class="search-form">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">🔍 Search</button>
                    </form>
                </div>

                <div class="nav-buttons">
                    <?php if ($is_logged_in): ?>
                        <a href="wishlist.php" class="icon-btn">
                            ❤️ Wishlist
                            <span class="badge" id="wishlistCount"><?php echo count($wishlist_items); ?></span>
                        </a>
                        <a href="cart.php" class="icon-btn">
                            🛒 Cart
                            <span class="badge" id="cartCount">0</span>
                        </a>
                        <a href="profile.php" class="icon-btn">👤</a>
                        <a href="logout.php" class="icon-btn" style="background: #ff4757;">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="icon-btn">Login</a>
                        <a href="register.php" class="icon-btn" style="background: #764ba2;">Register</a>
                    <?php endif; ?>
                    
                    <!-- 🔥 ADMIN BUTTON -->
                    <a href="admin/login.php" class="icon-btn admin-btn">
                        ⚙️ Admin
                    </a>
                </div>
            </div>

            <!-- Category Navigation -->
            <div class="category-nav">
                <a href="shop.php" class="category-link <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    🏠 All Products
                </a>
                <?php 
                mysqli_data_seek($categories_result, 0); // Reset pointer
                while ($cat = mysqli_fetch_assoc($categories_result)): 
                ?>
                    <a href="shop.php?category=<?php echo urlencode($cat['category']); ?>" 
                       class="category-link <?php echo $category_filter === $cat['category'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </header>

    <!-- 🔥 Offer Banner Slider -->
    <div class="offer-banner-section">
        <div class="offer-slider">
            <div class="offer-slide active">
                <img src="https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=1200&h=400&fit=crop" alt="Mega Sale">
                <div class="offer-content">
                    <h2>🎉 Mega Sale - Up to 70% OFF</h2>
                    <p>Shop now and save big on all categories!</p>
                    <a href="#products" class="offer-btn">Shop Now</a>
                </div>
            </div>
            <div class="offer-slide">
                <img src="https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=1200&h=400&fit=crop" alt="Flash Deal">
                <div class="offer-content">
                    <h2>⚡ Flash Deal - Limited Time Only</h2>
                    <p>Grab the best deals before they're gone!</p>
                    <a href="#products" class="offer-btn">Grab Deal</a>
                </div>
            </div>
            <div class="offer-slide">
                <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=400&fit=crop" alt="New Arrivals">
                <div class="offer-content">
                    <h2>🆕 New Arrivals - Fresh Stock</h2>
                    <p>Check out the latest products in our store!</p>
                    <a href="#products" class="offer-btn">Explore Now</a>
                </div>
            </div>
        </div>
        
        <!-- Slider Controls -->
        <button class="slider-btn prev" onclick="changeSlide(-1)">❮</button>
        <button class="slider-btn next" onclick="changeSlide(1)">❯</button>
        
        <!-- Slider Dots -->
        <div class="slider-dots">
            <span class="dot active" onclick="currentSlide(0)"></span>
            <span class="dot" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>🛍️ Our Products</h1>
            <p>Discover amazing products at great prices!</p>
        </div>

        <!-- 🔥 NEW: Category Cards Section -->
        <div class="category-cards-section">
            <h2 class="section-title">Browse by Category</h2>
            <div class="category-cards-grid">
                <a href="shop.php" class="category-card <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    <div class="category-icon">🏠</div>
                    <div class="category-name">All Products</div>
                    <div class="category-count">
                        <?php 
                        $all_count_query = "SELECT COUNT(*) as count FROM products";
                        $all_count_result = mysqli_query($conn, $all_count_query);
                        $all_count = mysqli_fetch_assoc($all_count_result)['count'];
                        echo $all_count . ' items';
                        ?>
                    </div>
                </a>

                <a href="shop.php?category=Electronics" class="category-card <?php echo $category_filter === 'Electronics' ? 'active' : ''; ?>">
                    <div class="category-icon">📱</div>
                    <div class="category-name">Electronics</div>
                    <div class="category-count">
                        <?php 
                        $elec_count_query = "SELECT COUNT(*) as count FROM products WHERE category = 'Electronics'";
                        $elec_count_result = mysqli_query($conn, $elec_count_query);
                        $elec_count = mysqli_fetch_assoc($elec_count_result)['count'];
                        echo $elec_count . ' items';
                        ?>
                    </div>
                </a>

                <a href="shop.php?category=Clothing" class="category-card <?php echo $category_filter === 'Clothing' ? 'active' : ''; ?>">
                    <div class="category-icon">👕</div>
                    <div class="category-name">Clothing</div>
                    <div class="category-count">
                        <?php 
                        $cloth_count_query = "SELECT COUNT(*) as count FROM products WHERE category = 'Clothing'";
                        $cloth_count_result = mysqli_query($conn, $cloth_count_query);
                        $cloth_count = mysqli_fetch_assoc($cloth_count_result)['count'];
                        echo $cloth_count . ' items';
                        ?>
                    </div>
                </a>

                <a href="shop.php?category=Books" class="category-card <?php echo $category_filter === 'Books' ? 'active' : ''; ?>">
                    <div class="category-icon">📚</div>
                    <div class="category-name">Books</div>
                    <div class="category-count">
                        <?php 
                        $books_count_query = "SELECT COUNT(*) as count FROM products WHERE category = 'Books'";
                        $books_count_result = mysqli_query($conn, $books_count_query);
                        $books_count = mysqli_fetch_assoc($books_count_result)['count'];
                        echo $books_count . ' items';
                        ?>
                    </div>
                </a>

                <a href="shop.php?category=Home & Garden" class="category-card <?php echo $category_filter === 'Home & Garden' ? 'active' : ''; ?>">
                    <div class="category-icon">🏡</div>
                    <div class="category-name">Home & Garden</div>
                    <div class="category-count">
                        <?php 
                        $home_count_query = "SELECT COUNT(*) as count FROM products WHERE category = 'Home & Garden'";
                        $home_count_result = mysqli_query($conn, $home_count_query);
                        $home_count = mysqli_fetch_assoc($home_count_result)['count'];
                        echo $home_count . ' items';
                        ?>
                    </div>
                </a>

                <a href="shop.php?category=Sports" class="category-card <?php echo $category_filter === 'Sports' ? 'active' : ''; ?>">
                    <div class="category-icon">⚽</div>
                    <div class="category-name">Sports</div>
                    <div class="category-count">
                        <?php 
                        $sports_count_query = "SELECT COUNT(*) as count FROM products WHERE category = 'Sports'";
                        $sports_count_result = mysqli_query($conn, $sports_count_query);
                        $sports_count = mysqli_fetch_assoc($sports_count_result)['count'];
                        echo $sports_count . ' items';
                        ?>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="shop.php" class="filters-container">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">

                <!-- Price Range -->
                <div class="filter-group">
                    <label>💰 Price Range</label>
                    <div class="price-range">
                        <input type="number" name="min_price" class="price-input" 
                               placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                        <span>to</span>
                        <input type="number" name="max_price" class="price-input" 
                               placeholder="Max" value="<?php echo $max_price < 999999 ? $max_price : ''; ?>">
                    </div>
                </div>

                <!-- Rating Filter -->
                <div class="filter-group">
                    <label>⭐ Minimum Rating</label>
                    <select name="rating">
                        <option value="0" <?php echo $min_rating == 0 ? 'selected' : ''; ?>>All Ratings</option>
                        <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>>4★ & above</option>
                        <option value="3" <?php echo $min_rating == 3 ? 'selected' : ''; ?>>3★ & above</option>
                        <option value="2" <?php echo $min_rating == 2 ? 'selected' : ''; ?>>2★ & above</option>
                        <option value="1" <?php echo $min_rating == 1 ? 'selected' : ''; ?>>1★ & above</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div class="filter-group">
                    <label>🔽 Sort By</label>
                    <select name="sort">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn">Apply Filters</button>
                <a href="shop.php" class="clear-filters">Clear All</a>
            </form>
        </div>

        <!-- Results Info -->
        <div class="results-info">
            <span><?php echo mysqli_num_rows($result); ?> Products Found</span>
            <?php if (!empty($search)): ?>
                <span>Search results for: "<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
        </div>

        <!-- Products Grid -->
        <div class="products-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($product = mysqli_fetch_assoc($result)): 
                    $is_in_wishlist = in_array($product['id'], $wishlist_items);
                    $avg_rating = round($product['avg_rating'], 1);
                    $review_count = $product['review_count'];
                    // ✅ USE THE FUNCTION HERE
                    $image_path = getImagePath($product['image']);
                ?>
                    <div class="product-card">
                        <!-- Wishlist Button -->
                        <?php if ($is_logged_in): ?>
                            <button class="wishlist-btn <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                    onclick="toggleWishlist(<?php echo $product['id']; ?>, this)">
                                <?php echo $is_in_wishlist ? '❤️' : '🤍'; ?>
                            </button>
                        <?php endif; ?>

                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image"
                             onclick="window.location.href='products/view.php?id=<?php echo $product['id']; ?>'"
                             onerror="this.src='https://via.placeholder.com/280x220?text=No+Image'">
                        
                        <div class="product-info">
                            <?php if (!empty($product['category'])): ?>
                                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                            <?php endif; ?>
                            
                            <div class="product-name" onclick="window.location.href='products/view.php?id=<?php echo $product['id']; ?>'">
                                 <?php echo htmlspecialchars($product['name']); ?>
                            </div>

                            <!-- Rating -->
                            <div class="rating-container">
                                <div class="stars">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $avg_rating) {
                                            echo '⭐';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="rating-text">
                                    <?php echo $avg_rating; ?> (<?php echo $review_count; ?> reviews)
                                </span>
                            </div>

                            <div class="product-price">
                                ₹<?php echo number_format($product['price'], 2); ?>
                            </div>

                            <div class="product-actions">
                                <?php if ($is_logged_in): ?>
                                    <button class="btn btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        🛒 Cart
                                    </button>
                                    <button class="btn btn-buy" onclick="buyNow(<?php echo $product['id']; ?>)">
                                        ⚡ Buy
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-cart" style="text-align: center; padding: 10px;">
                                        Login to Buy
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-products">
                    <div class="no-products-icon">📦</div>
                    <h2>No Products Found</h2>
                    <p>Try adjusting your filters or search terms</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">×</button>
            <div id="modalBody">
                <!-- Product details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Update cart count
        function updateCartCount() {
            <?php if ($is_logged_in): ?>
                fetch('get-cart-count.php')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('cartCount').textContent = data.count || 0;
                    })
                    .catch(error => console.error('Error:', error));
            <?php endif; ?>
        }

        // Add to cart
        function addToCart(productId) {
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Product added to cart!');
                    updateCartCount();
                } else {
                    alert('❌ ' + (data.message || 'Failed to add product'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred');
            });
        }

        // Buy Now
        function buyNow(productId) {
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'checkout.php';
                } else {
                    alert('❌ ' + (data.message || 'Failed to process order'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred');
            });
        }

        // Toggle Wishlist
        function toggleWishlist(productId, btn) {
            const isActive = btn.classList.contains('active');
            const action = isActive ? 'remove' : 'add';

            fetch('toggle-wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&action=' + action
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'add') {
                        btn.classList.add('active');
                        btn.textContent = '❤️';
                        updateWishlistCount(1);
                    } else {
                        btn.classList.remove('active');
                        btn.textContent = '🤍';
                        updateWishlistCount(-1);
                    }
                } else {
                    alert('❌ ' + (data.message || 'Failed to update wishlist'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred');
            });
        }

        // Update wishlist count
        function updateWishlistCount(change) {
            const countElem = document.getElementById('wishlistCount');
            const currentCount = parseInt(countElem.textContent);
            countElem.textContent = Math.max(0, currentCount + change);
        }

        // View Product Detail
        function viewProduct(productId) {
            fetch('get-product-detail.php?id=' + productId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalBody').innerHTML = html;
                    document.getElementById('productModal').classList.add('active');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Failed to load product details');
                });
        }

        // Close Modal
        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Load cart count on page load
        window.addEventListener('load', updateCartCount);

        // 🔥 Offer Banner Slider JavaScript
        let slideIndex = 0;
        let slideTimer;

        // Auto slide every 5 seconds
        function autoSlide() {
            slideTimer = setInterval(() => {
                changeSlide(1);
            }, 5000);
        }

        // Change slide
        function changeSlide(n) {
            clearInterval(slideTimer);
            const slides = document.querySelectorAll('.offer-slide');
            const dots = document.querySelectorAll('.dot');
            
            slideIndex += n;
            
            if (slideIndex >= slides.length) {
                slideIndex = 0;
            }
            if (slideIndex < 0) {
                slideIndex = slides.length - 1;
            }
            
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            slides[slideIndex].classList.add('active');
            dots[slideIndex].classList.add('active');
            
            autoSlide();
        }

        // Go to specific slide
        function currentSlide(n) {
            clearInterval(slideTimer);
            slideIndex = n;
            
            const slides = document.querySelectorAll('.offer-slide');
            const dots = document.querySelectorAll('.dot');
            
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            slides[slideIndex].classList.add('active');
            dots[slideIndex].classList.add('active');
            
            autoSlide();
        }

        // Start auto slide on page load
        window.addEventListener('load', () => {
            autoSlide();
        });
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>