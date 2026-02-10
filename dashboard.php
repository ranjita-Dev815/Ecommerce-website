<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('../db.php'); // Database connection

// Get statistics
$total_products_query = "SELECT COUNT(*) as total FROM products";
$total_products_result = mysqli_query($conn, $total_products_query);
$total_products = mysqli_fetch_assoc($total_products_result)['total'];

$total_orders_query = "SELECT COUNT(*) as total FROM orders";
$total_orders_result = mysqli_query($conn, $total_orders_query);
$total_orders = mysqli_fetch_assoc($total_orders_result)['total'];

$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_users_result = mysqli_query($conn, $total_users_query);
$total_users = mysqli_fetch_assoc($total_users_result)['total'];

$total_revenue_query = "SELECT SUM(total_amount) as revenue FROM orders WHERE order_status != 'Cancelled'";
$total_revenue_result = mysqli_query($conn, $total_revenue_query);
$total_revenue = mysqli_fetch_assoc($total_revenue_result)['revenue'] ?? 0;

// Recent orders
$recent_orders_query = "SELECT o.*, u.name, u.email 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        ORDER BY o.order_date DESC 
                        LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Shop</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .logout-btn:hover {
            background: #d32f2f;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #1a1d29;
        }

        .stat-card.products { border-left: 4px solid #2196F3; }
        .stat-card.orders { border-left: 4px solid #4CAF50; }
        .stat-card.users { border-left: 4px solid #FF9800; }
        .stat-card.revenue { border-left: 4px solid #9C27B0; }

        /* Recent Orders Table */
        .recent-orders {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .recent-orders h2 {
            margin-bottom: 20px;
            color: #1a1d29;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f5f7fa;
            font-weight: 600;
            color: #666;
        }

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status.pending { background: #FFF3E0; color: #F57C00; }
        .status.processing { background: #E3F2FD; color: #1976D2; }
        .status.completed { background: #E8F5E9; color: #388E3C; }
        .status.cancelled { background: #FFEBEE; color: #D32F2F; }

        .view-all {
            display: inline-block;
            margin-top: 15px;
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
        }

        .view-all:hover {
            text-decoration: underline;
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
                        <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
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
                        <a href="get-order-details.php" class="nav-link">📋 Order Details</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <span>👤 Admin</span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card products">
                    <h3>Total Products</h3>
                    <div class="number"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-card orders">
                    <h3>Total Orders</h3>
                    <div class="number"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-card users">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card revenue">
                    <h3>Total Revenue</h3>
                    <div class="number">₹<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent_orders_result) > 0): ?>
                            <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['name'] ?? 'Guest'); ?></td>
                                    <td><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="status <?php echo strtolower($order['order_status']); ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px; color: #999;">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <a href="view-orders.php" class="view-all">View All Orders →</a>
            </div>
        </main>
    </div>
</body>
</html>