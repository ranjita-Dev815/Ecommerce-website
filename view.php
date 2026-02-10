<?php
session_start();
include '../db.php'; // Going up one level to access db.php

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id == 0) {
    header('Location: ../shop.php');
    exit();
}

// Fetch product details with average rating
$query = "SELECT p.*, 
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as review_count
          FROM products p
          LEFT JOIN reviews r ON p.id = r.product_id
          WHERE p.id = $product_id
          GROUP BY p.id";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: ../shop.php');
    exit();
}

$product = mysqli_fetch_assoc($result);

// Check if product is in wishlist
$is_in_wishlist = false;
if ($is_logged_in) {
    $wishlist_query = "SELECT * FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $wishlist_result = mysqli_query($conn, $wishlist_query);
    $is_in_wishlist = mysqli_num_rows($wishlist_result) > 0;
}

// Fetch reviews
$reviews_query = "SELECT r.*, u.email as username, u.email 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = $product_id 
                  ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_query);

// Calculate rating distribution
$rating_dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$rating_query = "SELECT rating, COUNT(*) as count FROM reviews WHERE product_id = $product_id GROUP BY rating";
$rating_result = mysqli_query($conn, $rating_query);
while ($row = mysqli_fetch_assoc($rating_result)) {
    $rating_dist[$row['rating']] = $row['count'];
}

// Fetch similar products (same category)
$similar_query = "SELECT p.*, 
                  COALESCE(AVG(r.rating), 0) as avg_rating,
                  COUNT(DISTINCT r.id) as review_count
                  FROM products p
                  LEFT JOIN reviews r ON p.id = r.product_id
                  WHERE p.category = '{$product['category']}' 
                  AND p.id != $product_id
                  GROUP BY p.id
                  LIMIT 6";
$similar_result = mysqli_query($conn, $similar_query);

// Function to fix image paths
function getImagePath($image_path) {
    if (empty($image_path)) {
        return 'https://via.placeholder.com/500x500?text=No+Image';
    }
    if (strpos($image_path, 'uploads/') === 0) {
        return '../' . $image_path;
    }
    if (strpos($image_path, '/') === false) {
        return '../uploads/' . $image_path;
    }
    if (strpos($image_path, 'admin/uploads/') !== false) {
        return '../' . str_replace('admin/uploads/', 'uploads/', $image_path);
    }
    return '../' . $image_path;
}

$image_path = getImagePath($product['image']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Online Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f3f6;
            color: #212121;
        }

        /* Header - Same as shop.php */
        header {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .icon-btn {
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

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px 50px;
        }

        /* Product Section */
        .product-section {
            background: white;
            border-radius: 2px;
            padding: 30px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 450px 1fr;
            gap: 40px;
        }

        /* Image Gallery */
        .image-gallery {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .main-image-container {
            position: relative;
            margin-bottom: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
            background: white;
        }

        .main-image {
            width: 100%;
            height: 500px;
            object-fit: contain;
            cursor: zoom-in;
        }

        .wishlist-btn-large {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wishlist-btn-large:hover {
            transform: scale(1.1);
        }

        .wishlist-btn-large.active {
            color: #ff4757;
        }

        .thumbnail-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #667eea;
        }

        /* Product Info */
        .product-info-section {
            padding: 10px 0;
        }

        .product-category-badge {
            display: inline-block;
            background: #f0f0f0;
            padding: 5px 12px;
            border-radius: 3px;
            font-size: 12px;
            color: #878787;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .product-title {
            font-size: 24px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        /* Rating Section */
        .rating-section {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 20px;
        }

        .rating-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #388e3c;
            color: white;
            padding: 5px 12px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 14px;
        }

        .rating-count {
            color: #878787;
            font-size: 14px;
        }

        /* Price Section */
        .price-section {
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 20px;
        }

        .price-container {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-bottom: 10px;
        }

        .current-price {
            font-size: 32px;
            font-weight: 500;
            color: #212121;
        }

        .original-price {
            font-size: 20px;
            color: #878787;
            text-decoration: line-through;
        }

        .discount-badge {
            color: #388e3c;
            font-weight: 600;
            font-size: 16px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 30px 0;
        }

        .btn-large {
            flex: 1;
            padding: 18px;
            border-radius: 2px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-add-cart {
            background: white;
            color: #667eea;
            border: 1px solid #667eea;
        }

        .btn-add-cart:hover {
            background: #667eea;
            color: white;
        }

        .btn-buy-now {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-buy-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        /* Specifications */
        .specifications-section {
            background: white;
            border-radius: 2px;
            padding: 30px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #212121;
        }

        .spec-table {
            width: 100%;
        }

        .spec-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .spec-row:last-child {
            border-bottom: none;
        }

        .spec-label {
            color: #878787;
            font-size: 14px;
        }

        .spec-value {
            color: #212121;
            font-size: 14px;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            border-radius: 2px;
            padding: 30px;
            margin-bottom: 20px;
        }

        .rating-overview {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 30px;
        }

        .overall-rating {
            text-align: center;
        }

        .rating-number {
            font-size: 56px;
            font-weight: 600;
            color: #212121;
        }

        .rating-stars-large {
            font-size: 24px;
            color: #ffc107;
            margin: 10px 0;
        }

        .total-reviews {
            color: #878787;
            font-size: 14px;
        }

        .rating-bars {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rating-bar-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .rating-label {
            width: 60px;
            font-size: 14px;
            color: #878787;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .bar-container {
            flex: 1;
            height: 8px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: #388e3c;
            transition: width 0.3s;
        }

        .rating-count-small {
            width: 50px;
            text-align: right;
            font-size: 13px;
            color: #878787;
        }

        /* Review Form */
        .review-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #212121;
        }

        .star-rating-input {
            display: flex;
            gap: 5px;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .star-input {
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s;
        }

        .star-input:hover,
        .star-input.active {
            color: #ffc107;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }

        .submit-review-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-review-btn:hover {
            background: #764ba2;
        }

        /* Review Item */
        .review-item {
            padding: 25px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .reviewer-name {
            font-weight: 600;
            color: #212121;
        }

        .review-rating {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #388e3c;
            color: white;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }

        .review-date {
            color: #878787;
            font-size: 13px;
        }

        .review-text {
            color: #212121;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .review-helpful {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .helpful-btn {
            padding: 6px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            color: #878787;
            transition: all 0.3s;
        }

        .helpful-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        /* Similar Products */
        .similar-products-section {
            background: white;
            border-radius: 2px;
            padding: 30px;
        }

        .products-scroll {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card-small {
            border: 1px solid #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .product-card-small:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }

        .product-image-small {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .product-info-small {
            padding: 15px;
        }

        .product-name-small {
            font-size: 14px;
            color: #212121;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price-small {
            font-size: 18px;
            font-weight: 600;
            color: #212121;
        }

        .product-rating-small {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #388e3c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-top: 8px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .product-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .image-gallery {
                position: static;
            }

            .main-image {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .nav-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .product-section {
                padding: 20px;
            }

            .main-image {
                height: 300px;
            }

            .product-title {
                font-size: 20px;
            }

            .current-price {
                font-size: 26px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .rating-overview {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .spec-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }

            .products-scroll {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .main-image {
                height: 250px;
            }

            .thumbnail {
                width: 60px;
                height: 60px;
            }

            .product-title {
                font-size: 18px;
            }

            .current-price {
                font-size: 24px;
            }

            .btn-large {
                padding: 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="../index.php" class="logo">🛒 Online Store</a>
            
            <div class="nav-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="../wishlist.php" class="icon-btn">
                        ❤️ Wishlist
                        <span class="badge" id="wishlistCount">0</span>
                    </a>
                    <a href="../cart.php" class="icon-btn">
                        🛒 Cart
                        <span class="badge" id="cartCount">0</span>
                    </a>
                    <a href="../profile.php" class="icon-btn">👤</a>
                    <a href="../logout.php" class="icon-btn" style="background: #ff4757;">Logout</a>
                <?php else: ?>
                    <a href="../login.php" class="icon-btn">Login</a>
                    <a href="../register.php" class="icon-btn" style="background: #764ba2;">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Product Section -->
        <div class="product-section">
            <!-- Image Gallery -->
            <div class="image-gallery">
                <div class="main-image-container">
                    <?php if ($is_logged_in): ?>
                        <button class="wishlist-btn-large <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                onclick="toggleWishlist(<?php echo $product_id; ?>, this)">
                            <?php echo $is_in_wishlist ? '❤️' : '🤍'; ?>
                        </button>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="main-image" id="mainImage"
                         onerror="this.src='https://via.placeholder.com/500x500?text=No+Image'">
                </div>
                
                <div class="thumbnail-container">
                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                         class="thumbnail active" 
                         onclick="changeImage(this)"
                         onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-info-section">
                <?php if (!empty($product['category'])): ?>
                    <span class="product-category-badge">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </span>
                <?php endif; ?>
                
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <!-- Rating Section -->
                <div class="rating-section">
                    <div class="rating-badge">
                        <?php echo round($product['avg_rating'], 1); ?> ⭐
                    </div>
                    <span class="rating-count">
                        <?php echo number_format($product['review_count']); ?> Ratings & <?php echo number_format($product['review_count']); ?> Reviews
                    </span>
                </div>

                <!-- Price Section -->
                <div class="price-section">
                    <div class="price-container">
                        <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                        <span class="original-price">₹<?php echo number_format($product['price'] * 1.3, 2); ?></span>
                        <span class="discount-badge">23% off</span>
                    </div>
                </div>

                <!-- Product Description -->
                <div style="margin-bottom: 20px; line-height: 1.6; color: #212121;">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($is_logged_in): ?>
                        <button class="btn-large btn-add-cart" onclick="addToCart(<?php echo $product_id; ?>)">
                            🛒 Add to Cart
                        </button>
                        <button class="btn-large btn-buy-now" onclick="buyNow(<?php echo $product_id; ?>)">
                            ⚡ Buy Now
                        </button>
                    <?php else: ?>
                        <a href="../login.php" class="btn-large btn-add-cart" style="text-align: center; text-decoration: none;">
                            Login to Purchase
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Delivery Info -->
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                    <h3 style="font-size: 16px; margin-bottom: 15px;">Delivery Options</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px;">
                        <div>📦 Free Delivery on orders above ₹499</div>
                        <div>🚚 Standard Delivery in 3-5 business days</div>
                        <div>💰 Cash on Delivery Available</div>
                        <div>↩️ 7 Days Return Policy</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Specifications -->
        <div class="specifications-section">
            <h2 class="section-title">Product Specifications</h2>
            <div class="spec-table">
                <div class="spec-row">
                    <div class="spec-label">Product ID</div>
                    <div class="spec-value"><?php echo $product['id']; ?></div>
                </div>
                <div class="spec-row">
                    <div class="spec-label">Category</div>
                    <div class="spec-value"><?php echo htmlspecialchars($product['category'] ?: 'N/A'); ?></div>
                </div>
                <div class="spec-row">
                    <div class="spec-label">Stock Status</div>
                    <div class="spec-value">
                        <span style="color: #388e3c; font-weight: 600;">In Stock</span>
                    </div>
                </div>
                <div class="spec-row">
                    <div class="spec-label">Brand</div>
                    <div class="spec-value">Premium Brand</div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 class="section-title">Ratings & Reviews</h2>

            <!-- Rating Overview -->
            <div class="rating-overview">
                <div class="overall-rating">
                    <div class="rating-number"><?php echo round($product['avg_rating'], 1); ?></div>
                    <div class="rating-stars-large">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= round($product['avg_rating']) ? '⭐' : '☆';
                        }
                        ?>
                    </div>
                    <div class="total-reviews"><?php echo number_format($product['review_count']); ?> Reviews</div>
                </div>

                <div class="rating-bars">
                    <?php 
                    $total = array_sum($rating_dist);
                    for ($i = 5; $i >= 1; $i--): 
                        $count = $rating_dist[$i];
                        $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                    ?>
                        <div class="rating-bar-row">
                            <div class="rating-label"><?php echo $i; ?> ⭐</div>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="rating-count-small"><?php echo $count; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Review Form -->
            <?php if ($is_logged_in): ?>
                <div class="review-form">
                    <h3 style="margin-bottom: 15px;">Write a Review</h3>
                    <form id="reviewForm" onsubmit="submitReview(event)">
                        <div class="form-group">
                            <label>Your Rating</label>
                            <div class="star-rating-input" id="starRating">
                                <span class="star-input" data-rating="1" onclick="setRating(1)">☆</span>
                                <span class="star-input" data-rating="2" onclick="setRating(2)">☆</span>
                                <span class="star-input" data-rating="3" onclick="setRating(3)">☆</span>
                                <span class="star-input" data-rating="4" onclick="setRating(4)">☆</span>
                                <span class="star-input" data-rating="5" onclick="setRating(5)">☆</span>
                            </div>
                            <input type="hidden" name="rating" id="ratingInput" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Your Review</label>
                            <textarea name="review_text" placeholder="Share your experience with this product..." required></textarea>
                        </div>

                        <button type="submit" class="submit-review-btn">Submit Review</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="review-form">
                    <p style="text-align: center; color: #878787;">
                        <a href="../login.php" style="color: #667eea; font-weight: 600;">Login</a> to write a review
                    </p>
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <div id="reviewsList">
                <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                    <?php while($review = mysqli_fetch_assoc($reviews_result)): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                        <div class="review-rating">
                                            <?php echo $review['rating']; ?> ⭐
                                        </div>
                                    </div>
                                </div>
                                <div class="review-date">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                            </div>

                            <div class="review-helpful">
                                <span style="color: #878787; font-size: 13px;">Was this helpful?</span>
                                <button class="helpful-btn">👍 Yes (0)</button>
                                <button class="helpful-btn">👎 No (0)</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #878787; padding: 40px 0;">
                        No reviews yet. Be the first to review this product!
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Similar Products -->
        <?php if (mysqli_num_rows($similar_result) > 0): ?>
            <div class="similar-products-section">
                <h2 class="section-title">Similar Products</h2>
                <div class="products-scroll">
                    <?php while($similar = mysqli_fetch_assoc($similar_result)): 
                        $similar_image = getImagePath($similar['image']);
                    ?>
                        <a href="view.php?id=<?php echo $similar['id']; ?>" class="product-card-small">
                            <img src="<?php echo htmlspecialchars($similar_image); ?>" 
                                 alt="<?php echo htmlspecialchars($similar['name']); ?>" 
                                 class="product-image-small"
                                 onerror="this.src='https://via.placeholder.com/200x180?text=No+Image'">
                            <div class="product-info-small">
                                <div class="product-name-small"><?php echo htmlspecialchars($similar['name']); ?></div>
                                <div class="product-price-small">₹<?php echo number_format($similar['price'], 2); ?></div>
                                <?php if ($similar['avg_rating'] > 0): ?>
                                    <span class="product-rating-small">
                                        <?php echo round($similar['avg_rating'], 1); ?> ⭐
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Update cart and wishlist counts
        function updateCounts() {
            <?php if ($is_logged_in): ?>
                // Cart count
                fetch('../get-cart-count.php')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('cartCount').textContent = data.count || 0;
                    });

                // Wishlist count
                fetch('../get-wishlist-count.php')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('wishlistCount').textContent = data.count || 0;
                    });
            <?php endif; ?>
        }

        // Change main image
        function changeImage(thumbnail) {
            document.getElementById('mainImage').src = thumbnail.src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        // Add to cart
        function addToCart(productId) {
            fetch('../add-to-cart.php', {
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
                    updateCounts();
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
            fetch('../add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../checkout.php';
                } else {
                    alert('❌ ' + (data.message || 'Failed to process order'));
                }
            });
        }

        // Toggle Wishlist
        function toggleWishlist(productId, btn) {
            const isActive = btn.classList.contains('active');
            const action = isActive ? 'remove' : 'add';

            fetch('../toggle-wishlist.php', {
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
                    } else {
                        btn.classList.remove('active');
                        btn.textContent = '🤍';
                    }
                    updateCounts();
                } else {
                    alert('❌ ' + (data.message || 'Failed to update wishlist'));
                }
            });
        }

        // Rating system
        let selectedRating = 0;

        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('ratingInput').value = rating;
            
            const stars = document.querySelectorAll('.star-input');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                    star.textContent = '⭐';
                } else {
                    star.classList.remove('active');
                    star.textContent = '☆';
                }
            });
        }

        // Submit review
        function submitReview(event) {
            event.preventDefault();
            
            if (selectedRating === 0) {
                alert('Please select a rating');
                return;
            }

            const formData = new FormData(event.target);
            formData.append('product_id', <?php echo $product_id; ?>);

            fetch('../submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Review submitted successfully!');
                    location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Failed to submit review'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred');
            });
        }

        // Load counts on page load
        window.addEventListener('load', updateCounts);
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>