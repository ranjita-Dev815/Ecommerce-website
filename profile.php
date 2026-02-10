<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get user stats
$orders_count_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = '$user_id'";
$orders_count = mysqli_fetch_assoc(mysqli_query($conn, $orders_count_query))['count'];

$wishlist_count_query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = '$user_id'";
$wishlist_count = mysqli_fetch_assoc(mysqli_query($conn, $wishlist_count_query))['count'];

// Get cart count
$cart_count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = '$user_id'";
$cart_count = mysqli_fetch_assoc(mysqli_query($conn, $cart_count_query))['count'];

// Get recent orders - Using ACTUAL columns from your database
$recent_orders_query = "SELECT o.id, o.user_id, o.total_amount, o.payment_method, o.order_date, o.order_status, 
                        o.address, o.city, o.state, o.pincode, o.customer_name, o.email, o.phone,
                        COUNT(oi.id) as items_count 
                        FROM orders o 
                        LEFT JOIN order_items oi ON o.id = oi.order_id 
                        WHERE o.user_id = '$user_id' 
                        GROUP BY o.id 
                        ORDER BY o.id DESC 
                        LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_query);

// Handle profile update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
    
    $update_query = "UPDATE users SET 
                     name = '$name', 
                     phone = '$phone',
                     address = '$address',
                     city = '$city',
                     state = '$state',
                     pincode = '$pincode'
                     WHERE id = '$user_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $message = "✅ Profile updated successfully!";
        $messageType = "success";
        $_SESSION['user_name'] = $name;
        // Refresh user data
        $user_result = mysqli_query($conn, $user_query);
        $user = mysqli_fetch_assoc($user_result);
    } else {
        $message = "❌ Error updating profile!";
        $messageType = "error";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";
                
                if (mysqli_query($conn, $update_pass)) {
                    $message = "✅ Password changed successfully!";
                    $messageType = "success";
                } else {
                    $message = "❌ Error changing password!";
                    $messageType = "error";
                }
            } else {
                $message = "❌ Password must be at least 6 characters!";
                $messageType = "error";
            }
        } else {
            $message = "❌ Passwords do not match!";
            $messageType = "error";
        }
    } else {
        $message = "❌ Current password is incorrect!";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Online Store</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
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

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .breadcrumbs {
            color: white;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .breadcrumbs a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
        }

        .breadcrumbs a:hover {
            opacity: 1;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 15px;
            font-weight: bold;
        }

        .profile-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .profile-email {
            font-size: 14px;
            color: #666;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .menu-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .main-content {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            min-height: 600px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .order-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-shipped {
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

        .view-btn {
            background: #667eea;
            color: white;
            padding: 6px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .view-btn:hover {
            background: #764ba2;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-box {
            padding: 30px;
            border-radius: 15px;
            color: white;
        }

        .stat-box-number {
            font-size: 40px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-box-label {
            font-size: 16px;
            opacity: 0.9;
        }

        @media (max-width: 968px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: static;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="shop.php" class="logo">🛒 Online Store</a>
            <div class="nav-buttons">
                <a href="shop.php" class="icon-btn">🏠 Shop</a>
                <a href="wishlist.php" class="icon-btn">❤️ Wishlist</a>
                <a href="cart.php" class="icon-btn">🛒 Cart</a>
                <a href="logout.php" class="icon-btn" style="background: #ff4757;">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="breadcrumbs">
            <a href="shop.php">Home</a> / <span>My Profile</span>
        </div>

        <div class="profile-layout">
            <div class="sidebar">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>

                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $orders_count; ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $wishlist_count; ?></div>
                        <div class="stat-label">Wishlist</div>
                    </div>
                </div>

                <div class="menu-item active" onclick="showSection('dashboard')">
                    <span>📊</span> Dashboard
                </div>
                <div class="menu-item" onclick="showSection('orders')">
                    <span>📦</span> My Orders
                </div>
                <div class="menu-item" onclick="showSection('profile')">
                    <span>👤</span> Profile Settings
                </div>
                <div class="menu-item" onclick="showSection('password')">
                    <span>🔒</span> Change Password
                </div>
                <div class="menu-item" onclick="location.href='wishlist.php'">
                    <span>❤️</span> My Wishlist
                </div>
                <div class="menu-item" onclick="location.href='cart.php'">
                    <span>🛒</span> Shopping Cart
                </div>
            </div>

            <div class="main-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div id="dashboard" class="section active">
                    <h2 class="section-title">📊 Dashboard</h2>
                    <p class="section-subtitle">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>

                    <div class="dashboard-stats">
                        <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="stat-box-number"><?php echo $orders_count; ?></div>
                            <div class="stat-box-label">Total Orders</div>
                        </div>
                        <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="stat-box-number"><?php echo $wishlist_count; ?></div>
                            <div class="stat-box-label">Wishlist Items</div>
                        </div>
                        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="stat-box-number"><?php echo $cart_count; ?></div>
                            <div class="stat-box-label">Cart Items</div>
                        </div>
                    </div>

                    <h3 style="font-size: 22px; margin-bottom: 20px; font-weight: 600;">📦 Recent Orders</h3>
                    
                    <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo $order['items_count']; ?> items</td>
                                        <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="view-btn">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📦</div>
                            <h3>No Orders Yet</h3>
                            <p>Start shopping to see your orders here!</p>
                            <a href="shop.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="orders" class="section">
                    <h2 class="section-title">📦 My Orders</h2>
                    <p class="section-subtitle">Track and manage all your orders</p>

                    <?php 
                    $all_orders_query = "SELECT o.id, o.user_id, o.total_amount, o.payment_method, o.order_date, o.order_status,
                                        o.address, o.city, o.state, o.pincode, o.customer_name, o.email, o.phone,
                                        COUNT(oi.id) as items_count 
                                        FROM orders o 
                                        LEFT JOIN order_items oi ON o.id = oi.order_id 
                                        WHERE o.user_id = '$user_id' 
                                        GROUP BY o.id 
                                        ORDER BY o.id DESC";
                    $all_orders = mysqli_query($conn, $all_orders_query);
                    
                    if (mysqli_num_rows($all_orders) > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($all_orders)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo $order['items_count']; ?> items</td>
                                        <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td><?php echo ucfirst($order['payment_method']); ?></td>
                                        <td>
                                            <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="view-btn">View Details</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📦</div>
                            <h3>No Orders Yet</h3>
                            <p>You haven't placed any orders yet. Start shopping now!</p>
                            <a href="shop.php" class="btn btn-primary">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="profile" class="section">
                    <h2 class="section-title">👤 Profile Settings</h2>
                    <p class="section-subtitle">Update your personal information</p>

                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f0f0f0;">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter phone number">
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="Enter city">
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" placeholder="Enter state">
                            </div>
                            <div class="form-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode" value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" placeholder="Enter pincode">
                            </div>
                            <div class="form-group full-width">
                                <label>Address</label>
                                <textarea name="address" placeholder="Enter complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
                    </form>
                </div>

                <div id="password" class="section">
                    <h2 class="section-title">🔒 Change Password</h2>
                    <p class="section-subtitle">Update your password to keep your account secure</p>

                    <form method="POST" action="" style="max-width: 500px;">
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="current_password" required placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" required placeholder="Enter new password (min 6 characters)">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" required placeholder="Re-enter new password">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">🔒 Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            document.getElementById(sectionId).classList.add('active');
            event.target.closest('.menu-item').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>