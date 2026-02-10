<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Get order details - FIXED COLUMN NAMES
$order_query = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header('Location: index.php');
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .confirmation-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #4caf50;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .order-id {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 600;
        }

        .order-details {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            text-align: right;
            max-width: 60%;
        }

        .items-section {
            margin: 30px 0;
            padding: 20px;
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }

        .items-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        @media (max-width: 600px) {
            .confirmation-container {
                padding: 30px 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            h1 {
                font-size: 24px;
            }

            .detail-value {
                max-width: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-icon">✅</div>
        <h1>Order Placed Successfully!</h1>
        <p style="color: #666; margin-bottom: 20px;">Thank you for your purchase. Your order has been confirmed.</p>

        <div class="order-id">
            Order ID: <strong>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong>
        </div>

        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Delivery Address:</span>
                <span class="detail-value">
                    <?php 
                    echo htmlspecialchars($order['address']) . ', ' . 
                         htmlspecialchars($order['city']) . ', ' . 
                         htmlspecialchars($order['state']) . ' - ' . 
                         htmlspecialchars($order['pincode']); 
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Status:</span>
                <span class="detail-value" style="color: #ff9800; font-weight: 600; text-transform: capitalize;">
                    <?php echo htmlspecialchars($order['order_status']); ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
            </div>
        </div>

        <div class="items-section">
            <div class="items-title">📦 Order Items</div>
            <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                <div class="item-row">
                    <span><?php echo htmlspecialchars($item['product_name']); ?> × <?php echo $item['quantity']; ?></span>
                    <span style="font-weight: 600;">₹<?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
            <?php endwhile; ?>
            
            <div class="item-row" style="border-top: 2px solid #c7d1fd; margin-top: 10px; padding-top: 15px; font-size: 20px;">
                <span style="font-weight: bold; color: #333;">Total Amount:</span>
                <span style="color: #4caf50; font-weight: bold;">₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <p style="color: #666; font-size: 14px; margin: 20px 0;">
            📧 Order confirmation has been sent to your email address.
        </p>

        <div class="action-buttons">
            <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
            <a href="shop.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>

<?php mysqli_close($conn); ?>