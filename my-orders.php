<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all orders for this user
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Online Store</title>
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
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .navbar h2 {
            color: #667eea;
            font-size: 28px;
        }

        .back-btn {
            background: #667eea;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            color: white;
            font-size: 42px;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .orders-container {
            display: grid;
            gap: 25px;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .order-id {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .order-status {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .order-items-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .items-header {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .item-details {
            font-size: 14px;
            color: #666;
        }

        .item-price {
            font-weight: bold;
            color: #667eea;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #667eea;
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
        }

        .view-details-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        .view-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .no-orders {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .no-orders-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .no-orders-text {
            font-size: 24px;
            color: #666;
            margin-bottom: 30px;
        }

        .shop-now-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
        }

        .shop-now-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .order-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div>
            <h2>📋 My Orders</h2>
        </div>
        <div>
            <a href="shop.php" class="back-btn">← Back to Shop</a>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">Order History</h1>

        <div class="orders-container">
            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                    <?php
                    // Get order items
                    $order_id = $order['id'];
                    $items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
                    $items_result = mysqli_query($conn, $items_query);
                    
                    // Determine status class
                    $status_class = 'status-' . strtolower(str_replace(' ', '-', $order['order_status']));
                    ?>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <div class="order-status <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </div>
                        </div>

                        <div class="order-info">
                            <div class="info-item">
                                <div class="info-label">Order Date</div>
                                <div class="info-value"><?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Payment Method</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Delivery To</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Contact</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </div>
                        </div>

                        <div class="order-items-section">
                            <div class="items-header">📦 Items Ordered</div>
                            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                <div class="order-item">
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-details">Qty: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['product_price'], 2); ?></div>
                                    </div>
                                    <div class="item-price">₹<?php echo number_format($item['subtotal'], 2); ?></div>
                                </div>
                            <?php endwhile; ?>

                            <div class="order-total">
                                <span>Total Amount:</span>
                                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-icon">📦</div>
                    <div class="no-orders-text">You haven't placed any orders yet!</div>
                    <a href="shop.php" class="shop-now-btn">🛍️ Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

<?php
mysqli_close($conn);
?>