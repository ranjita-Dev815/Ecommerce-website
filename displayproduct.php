<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('../db.php'); // Database connection

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $delete_query = "DELETE FROM products WHERE id = '$delete_id'";
    
    if (mysqli_query($conn, $delete_query)) {
        $success_msg = "Product deleted successfully!";
    } else {
        $error_msg = "Error deleting product: " . mysqli_error($conn);
    }
}

// Get all products
$products_query = "SELECT * FROM products ORDER BY id DESC";
$products_result = mysqli_query($conn, $products_query);

if (!$products_result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin Panel</title>
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

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #1a1d29;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            padding: 0 20px 20px;
            font-size: 24px;
            font-weight: 700;
            color: #4CAF50;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin: 5px 0;
        }

        .nav-link {
            display: block;
            padding: 12px 20px;
            color: #b8bcc8;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border-left: 3px solid #4CAF50;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #1a1d29;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background: #388E3C;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
            padding: 8px 15px;
        }

        .btn-edit:hover {
            background: #1976D2;
        }

        .logout-btn {
            background: #f44336;
            color: white;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        .alert.error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #f44336;
        }

        /* Products Section */
        .products-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .products-section h2 {
            margin-bottom: 20px;
            color: #1a1d29;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .product-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f5f5f5;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
            color: #1a1d29;
        }

        .product-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .product-stock {
            font-size: 13px;
            color: #666;
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

        .stock-badge.low-stock {
            background: #FFF3E0;
            color: #F57C00;
        }

        .stock-badge.out-of-stock {
            background: #FFEBEE;
            color: #C62828;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-products-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }

        /* Table View (Alternative) */
        .table-wrapper {
            overflow-x: auto;
            display: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f5f7fa;
            font-weight: 600;
            color: #666;
        }

        tr:hover {
            background: #f9fafb;
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .view-btn {
            padding: 8px 15px;
            background: #f5f7fa;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .view-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">🛍️ Admin Panel</div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="view-orders.php" class="nav-link">📦 View Orders</a>
                    </li>
                    <li class="nav-item">
                        <a href="displayproduct.php" class="nav-link active">🏷️ Products</a>
                    </li>
                    <li class="nav-item">
                        <a href="addproduct.php" class="nav-link">➕ Add Product</a>
                    </li>
                    <li class="nav-item">
                        <a href="get-order-details.php" class="nav-link">📋 Order Details</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>🏷️ Products Management</h1>
                <div class="header-actions">
                    <a href="addproduct.php" class="btn btn-primary">➕ Add New Product</a>
                    <a href="logout.php" class="btn logout-btn">Logout</a>
                </div>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="products-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>All Products (<?php echo mysqli_num_rows($products_result); ?>)</h2>
                    
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('grid')">🎴 Grid View</button>
                        <button class="view-btn" onclick="toggleView('table')">📋 Table View</button>
                    </div>
                </div>

                <?php if (mysqli_num_rows($products_result) > 0): ?>
                    
                    <!-- Grid View -->
                    <div class="products-grid" id="grid-view">
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                            <div class="product-card">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
                                        <span style="font-size: 48px;">📦</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    
                                    <?php if (!empty($product['description'])): ?>
                                        <div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                    
                                    <div class="product-stock">
                                        Stock: 
                                        <?php 
                                        $stock = isset($product['stock']) ? $product['stock'] : 0;
                                        if ($stock > 10): 
                                        ?>
                                            <span class="stock-badge in-stock">In Stock (<?php echo $stock; ?>)</span>
                                        <?php elseif ($stock > 0): ?>
                                            <span class="stock-badge low-stock">Low Stock (<?php echo $stock; ?>)</span>
                                        <?php else: ?>
                                            <span class="stock-badge out-of-stock">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="updateproduct.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">✏️ Edit</a>
                                        <a href="?delete_id=<?php echo $product['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this product?')">🗑️ Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Table View -->
                    <div class="table-wrapper" id="table-view">
                        <?php mysqli_data_seek($products_result, 0); // Reset pointer ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-thumb">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px;">📦</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <?php if (!empty($product['description'])): ?>
                                                <br><small style="color: #666;"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>₹<?php echo number_format($product['price'], 2); ?></strong></td>
                                        <td>
                                            <?php 
                                            $stock = isset($product['stock']) ? $product['stock'] : 0;
                                            if ($stock > 10): 
                                            ?>
                                                <span class="stock-badge in-stock"><?php echo $stock; ?></span>
                                            <?php elseif ($stock > 0): ?>
                                                <span class="stock-badge low-stock"><?php echo $stock; ?></span>
                                            <?php else: ?>
                                                <span class="stock-badge out-of-stock">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="updateproduct.php?id=<?php echo $product['id']; ?>" class="btn btn-edit" style="font-size: 12px; padding: 6px 12px;">Edit</a>
                                                <a href="?delete_id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   style="font-size: 12px; padding: 6px 12px;"
                                                   onclick="return confirm('Are you sure?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <div class="no-products">
                        <div class="no-products-icon">📦</div>
                        <h3>No Products Found</h3>
                        <p>Start by adding your first product!</p>
                        <br>
                        <a href="addproduct.php" class="btn btn-primary">➕ Add Product</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleView(view) {
            const gridView = document.getElementById('grid-view');
            const tableView = document.getElementById('table-view');
            const buttons = document.querySelectorAll('.view-btn');
            
            if (view === 'grid') {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        }
    </script>
</body>
</html>