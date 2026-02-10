<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
// Removed $user_name = $_SESSION['username']; since username is not set in session

// Get cart items with product details
$query = "SELECT c.*, p.name, p.price, p.image, p.description 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching cart: " . mysqli_error($conn));
}

// Calculate total
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Online Store</title>
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

        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
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

        .cart-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 18px;
            color: #667eea;
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f5f5f5;
            padding: 8px 15px;
            border-radius: 25px;
        }

        .qty-btn {
            background: #667eea;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #764ba2;
            transform: scale(1.1);
        }

        .qty-display {
            font-size: 18px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .remove-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .remove-btn:hover {
            background: #ff3838;
            transform: scale(1.05);
        }

        .item-subtotal {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            min-width: 100px;
            text-align: right;
        }

        .cart-summary {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .summary-total {
            font-size: 28px;
            font-weight: bold;
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 15px;
            margin-top: 15px;
        }

        .checkout-btn {
            width: 100%;
            padding: 18px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 12px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.3);
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-cart-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-cart-text {
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
            .navbar {
                padding: 15px 20px;
            }

            .cart-item {
                flex-direction: column;
                text-align: center;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }

            .item-actions {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
            }

            .item-subtotal {
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <div>
            <h2>🛒 My Cart</h2>
        </div>
        <div class="navbar-right">
            <a href="shop.php" class="back-btn">← Back to Shop</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <h1 class="page-title">Shopping Cart</h1>

        <div class="cart-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($result)): ?>
                    <?php 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                    ?>
                    <div class="cart-item" id="item-<?php echo $item['id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="item-image"
                             onerror="this.src='https://via.placeholder.com/120?text=No+Image'">
                        
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">₹<?php echo number_format($item['price'], 2); ?> each</div>
                        </div>

                        <div class="item-actions">
                            <div class="quantity-control">
                                <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                <span class="qty-display" id="qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span>
                                <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">🗑️ Remove</button>
                        </div>

                        <div class="item-subtotal" id="subtotal-<?php echo $item['id']; ?>">
                            ₹<?php echo number_format($subtotal, 2); ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Items:</span>
                        <span id="totalItems"><?php echo mysqli_num_rows($result); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total Amount:</span>
                        <span id="totalAmount">₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                        🎯 Proceed to Checkout
                    </button>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <div class="empty-cart-text">Your cart is empty!</div>
                    <a href="shop.php" class="shop-now-btn">🛍️ Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateQuantity(cartId, action) {
            fetch('update-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.removed) {
                        document.getElementById(`item-${cartId}`).remove();
                        checkEmptyCart();
                    } else {
                        document.getElementById(`qty-${cartId}`).textContent = data.quantity;
                        document.getElementById(`subtotal-${cartId}`).textContent = '₹' + data.subtotal.toFixed(2);
                    }
                    updateTotal();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function removeItem(cartId) {
            if (confirm('Are you sure you want to remove this item?')) {
                fetch('remove-from-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`item-${cartId}`).remove();
                        updateTotal();
                        checkEmptyCart();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function updateTotal() {
            let total = 0;
            let count = 0;
            
            document.querySelectorAll('.cart-item').forEach(item => {
                const subtotalText = item.querySelector('.item-subtotal').textContent;
                const subtotal = parseFloat(subtotalText.replace('₹', '').replace(',', ''));
                total += subtotal;
                count++;
            });

            document.getElementById('totalAmount').textContent = '₹' + total.toFixed(2);
            document.getElementById('totalItems').textContent = count;
        }

        function checkEmptyCart() {
            if (document.querySelectorAll('.cart-item').length === 0) {
                location.reload();
            }
        }
    </script>

</body>
</html>

<?php
mysqli_close($conn);
?>