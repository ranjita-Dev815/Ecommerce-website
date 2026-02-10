<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('../db.php'); // Database connection

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? mysqli_real_escape_string($conn, $_GET['order_id']) : null;

if (!$order_id) {
    header("Location: view-orders.php");
    exit();
}

// Get order details
$order_query = "SELECT o.*, u.name, u.email, u.phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = '$order_id'";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    die("Order not found!");
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$order_items_query = "SELECT oi.*, p.name as product_name, p.image, p.price 
                      FROM order_items oi 
                      LEFT JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = '$order_id'";
$order_items_result = mysqli_query($conn, $order_items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?> - Admin Panel</title>
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

        .back-btn {
            padding: 10px 20px;
            background: #666;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .back-btn:hover {
            background: #555;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        /* Order Details Cards */
        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .detail-card h3 {
            color: #1a1d29;
            margin-bottom: 15px;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5f7fa;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f7fa;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #1a1d29;
            font-weight: 600;
        }

        .status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }

        .status.pending { background: #FFF3E0; color: #F57C00; }
        .status.processing { background: #E3F2FD; color: #1976D2; }
        .status.completed { background: #E8F5E9; color: #388E3C; }
        .status.cancelled { background: #FFEBEE; color: #D32F2F; }
        .status.shipped { background: #E1F5FE; color: #0277BD; }

        /* Order Items */
        .items-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .items-section h3 {
            color: #1a1d29;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .item-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #1a1d29;
            margin-bottom: 5px;
        }

        .item-price {
            color: #666;
            font-size: 14px;
        }

        .item-quantity {
            background: #f5f7fa;
            padding: 5px 12px;
            border-radius: 5px;
            font-weight: 600;
        }

        .item-total {
            font-weight: 700;
            color: #4CAF50;
            font-size: 18px;
        }

        .order-summary {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .summary-row.total {
            border-top: 2px solid #ddd;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
            color: #1a1d29;
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
                        <a href="displayproduct.php" class="nav-link">🏷️ Products</a>
                    </li>
                    <li class="nav-item">
                        <a href="addproduct.php" class="nav-link">➕ Add Product</a>
                    </li>
                    <li class="nav-item">
                        <a href="get-order-details.php" class="nav-link active">📋 Order Details</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>📋 Order Details #<?php echo $order_id; ?></h1>
                <div>
                    <a href="view-orders.php" class="back-btn">← Back to Orders</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Order Information -->
            <div class="order-details-grid">
                <div class="detail-card">
                    <h3>📦 Order Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="status <?php echo strtolower($order['order_status']); ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3>👤 Customer Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['name'] ?? 'Guest User'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3>📍 Shipping Address</h3>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">City:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['city'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Pincode:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['pincode'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="items-section">
                <h3>🛒 Ordered Items</h3>
                
                <?php if (mysqli_num_rows($order_items_result) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($order_items_result)): ?>
                        <div class="item-card">
                            <?php if (!empty($item['image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="item-image">
                            <?php else: ?>
                                <div class="item-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">📦</div>
                            <?php endif; ?>
                            
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></div>
                                <div class="item-price">Price: ₹<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></div>
                            </div>
                            
                            <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                            
                            <div class="item-total">
                                ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount:</span>
                            <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No items found for this order.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>