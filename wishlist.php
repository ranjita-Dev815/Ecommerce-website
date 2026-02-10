<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('db.php');

$user_id = $_SESSION['user_id'];

// Handle remove from wishlist
if (isset($_GET['remove_id'])) {
    $product_id = mysqli_real_escape_string($conn, $_GET['remove_id']);
    $delete_query = "DELETE FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'";
    mysqli_query($conn, $delete_query);
    header("Location: wishlist.php");
    exit();
}

// Handle add to cart from wishlist
if (isset($_GET['add_to_cart'])) {
    $product_id = mysqli_real_escape_string($conn, $_GET['add_to_cart']);
    
    // Check if already in cart
    $check_cart = "SELECT * FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $cart_result = mysqli_query($conn, $check_cart);
    
    if (mysqli_num_rows($cart_result) > 0) {
        // Update quantity
        $update_cart = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = '$user_id' AND product_id = '$product_id'";
        mysqli_query($conn, $update_cart);
    } else {
        // Add to cart
        $add_cart = "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', 1)";
        mysqli_query($conn, $add_cart);
    }
    
    $success_msg = "Product added to cart!";
}

// Get wishlist items
$wishlist_query = "SELECT w.*, p.name, p.price, p.image, p.description, p.stock 
                   FROM wishlist w 
                   LEFT JOIN products p ON w.product_id = p.id 
                   WHERE w.user_id = '$user_id' 
                   ORDER BY w.added_date DESC";
$wishlist_result = mysqli_query($conn, $wishlist_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Online Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-link {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #667eea;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .wishlist-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            position: relative;
        }

        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #f0f0f0;
        }

        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
            background: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s;
        }

        .remove-btn:hover {
            background: #f44336;
            color: white;
            transform: scale(1.1);
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1d29;
        }

        .product-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
        }

        .stock-status {
            font-size: 13px;
            margin-bottom: 15px;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .stock-badge.in-stock {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .stock-badge.out-of-stock {
            background: #FFEBEE;
            color: #C62828;
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #f5f7fa;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e4ea;
        }

        /* Empty State */
        .empty-wishlist {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-wishlist h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1a1d29;
        }

        .empty-wishlist p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .btn-large {
            display: inline-block;
            padding: 15px 40px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-large:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="shop.php" class="logo">🛒 Online Store</a>
            <nav class="nav-links">
                <a href="shop.php" class="nav-link">🏠 Shop</a>
                <a href="cart.php" class="nav-link">🛍️ Cart</a>
                <a href="wishlist.php" class="nav-link">❤️ Wishlist</a>
                <a href="my-orders.php" class="nav-link">📦 Orders</a>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1>❤️ My Wishlist</h1>
            <p>Your favorite products saved for later</p>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert">✅ <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($wishlist_result) > 0): ?>
            <div class="wishlist-grid">
                <?php while ($item = mysqli_fetch_assoc($wishlist_result)): ?>
                    <div class="wishlist-card">
                        <button class="remove-btn" onclick="if(confirm('Remove from wishlist?')) window.location.href='?remove_id=<?php echo $item['product_id']; ?>'">
                            ❌
                        </button>
                        
                        <?php if (!empty($item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center; font-size: 60px;">
                                📦
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            
                            <?php if (!empty($item['description'])): ?>
                                <div class="product-description"><?php echo htmlspecialchars($item['description']); ?></div>
                            <?php endif; ?>
                            
                            <div class="product-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            
                            <div class="stock-status">
                                <?php if ($item['stock'] > 0): ?>
                                    <span class="stock-badge in-stock">✅ In Stock</span>
                                <?php else: ?>
                                    <span class="stock-badge out-of-stock">❌ Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-actions">
                                <?php if ($item['stock'] > 0): ?>
                                    <a href="?add_to_cart=<?php echo $item['product_id']; ?>" class="btn btn-primary">
                                        🛍️ Add to Cart
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Out of Stock</button>
                                <?php endif; ?>
                                <a href="get_product_detail.php?id=<?php echo $item['product_id']; ?>" class="btn btn-secondary">
                                    👁️ View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <div class="empty-icon">💔</div>
                <h2>Your Wishlist is Empty</h2>
                <p>Start adding products you love to your wishlist!</p>
                <a href="shop.php" class="btn-large">🛍️ Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>