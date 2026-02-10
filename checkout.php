<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details - FIXED QUERY
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get the correct name field (could be 'name', 'username', or 'user_name')
$user_name = '';
if (isset($user['name'])) {
    $user_name = $user['name'];
} elseif (isset($user['username'])) {
    $user_name = $user['username'];
} elseif (isset($user['user_name'])) {
    $user_name = $user['user_name'];
} else {
    $user_name = 'Customer';
}

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price, p.image 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_query);

// Calculate total
$total = 0;
$items = [];
while ($item = mysqli_fetch_assoc($cart_result)) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    $items[] = $item;
}

// If cart is empty, redirect
if (empty($items)) {
    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Store</title>
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
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #764ba2;
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

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }

        .checkout-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
        }

        .payment-option label {
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            flex: 1;
        }

        /* Order Summary */
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .order-item-qty {
            font-size: 14px;
            color: #666;
        }

        .order-item-price {
            font-weight: bold;
            color: #667eea;
        }

        .order-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 16px;
        }

        .order-total {
            border-top: 2px solid #667eea;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .place-order-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .place-order-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        .required {
            color: #ff4757;
        }

        @media (max-width: 968px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div>
            <h2>🎯 Checkout</h2>
        </div>
        <a href="cart.php" class="back-btn">← Back to Cart</a>
    </div>

    <div class="container">
        <h1 class="page-title">Complete Your Order</h1>

        <form id="checkoutForm" method="POST" action="place-order.php">
            <div class="checkout-grid">
                <!-- Left Side - Delivery & Payment -->
                <div>
                    <!-- Delivery Information -->
                    <div class="checkout-section">
                        <div class="section-title">
                            📦 Delivery Information
                        </div>

                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="customer_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="tel" name="phone" placeholder="+91 1234567890" required>
                        </div>

                        <div class="form-group">
                            <label>Full Address <span class="required">*</span></label>
                            <textarea name="address" rows="3" placeholder="House No., Street, Area" required></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>City <span class="required">*</span></label>
                                <input type="text" name="city" required>
                            </div>
                            <div class="form-group">
                                <label>State <span class="required">*</span></label>
                                <input type="text" name="state" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Pincode <span class="required">*</span></label>
                            <input type="text" name="pincode" placeholder="123456" maxlength="6" required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section" style="margin-top: 30px;">
                        <div class="section-title">
                            💳 Payment Method
                        </div>

                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" name="payment_method" value="COD" id="cod" checked>
                                <label for="cod">💵 Cash on Delivery (COD)</label>
                            </div>

                            <div class="payment-option">
                                <input type="radio" name="payment_method" value="UPI" id="upi">
                                <label for="upi">📱 UPI Payment</label>
                            </div>

                            <div class="payment-option">
                                <input type="radio" name="payment_method" value="Card" id="card">
                                <label for="card">💳 Credit/Debit Card</label>
                            </div>

                            <div class="payment-option">
                                <input type="radio" name="payment_method" value="Net Banking" id="netbanking">
                                <label for="netbanking">🏦 Net Banking</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Order Summary -->
                <div>
                    <div class="checkout-section">
                        <div class="section-title">
                            🛒 Order Summary
                        </div>

                        <?php foreach ($items as $item): 
                            // Handle image path
                            $imagePath = "admin/uploads/" . $item['image'];
                            if (!file_exists($imagePath)) {
                                $imagePath = "https://via.placeholder.com/60x60/667eea/ffffff?text=Product";
                            }
                        ?>
                            <div class="order-item">
                                <img src="<?php echo $imagePath; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="order-item-image"
                                     onerror="this.src='https://via.placeholder.com/60x60/667eea/ffffff?text=Product'">
                                <div class="order-item-details">
                                    <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="order-item-qty">Quantity: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="order-item-price">
                                    ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="order-summary-row">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>

                        <div class="order-summary-row">
                            <span>Delivery Charges:</span>
                            <span style="color: #4caf50;">FREE</span>
                        </div>

                        <div class="order-summary-row order-total">
                            <span>Total Amount:</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button type="submit" class="place-order-btn">
                            ✅ Place Order (₹<?php echo number_format($total, 2); ?>)
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            const btn = document.querySelector('.place-order-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Processing...';
            btn.disabled = true;

            // Submit form
            const formData = new FormData(this);
            
            fetch('place-order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'order-confirmation.php?order_id=' + data.order_id;
                } else {
                    alert('❌ ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Something went wrong!');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

        // Phone number validation
        document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9+\s-]/g, '');
        });

        // Pincode validation
        document.querySelector('input[name="pincode"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>

</body>
</html>

<?php
mysqli_close($conn);
?>