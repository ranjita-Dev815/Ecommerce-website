<?php
/**
 * DATABASE DIAGNOSTIC TOOL
 * Run this file to check what's wrong with your database/orders
 * URL: http://yourdomain.com/admin/check-database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Database Diagnostic Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
</style>";

// Step 1: Check if db.php exists
echo "<div class='box'>";
echo "<h2>Step 1: Checking database connection file</h2>";

if (file_exists('../db.php')) {
    echo "<p class='success'>✅ db.php file found</p>";
    include('../db.php');
} elseif (file_exists('db.php')) {
    echo "<p class='success'>✅ db.php file found (in same directory)</p>";
    include('db.php');
} else {
    echo "<p class='error'>❌ db.php file NOT found!</p>";
    echo "<p class='info'>Create db.php with database connection details</p>";
    die();
}
echo "</div>";

// Step 2: Check database connection
echo "<div class='box'>";
echo "<h2>Step 2: Testing database connection</h2>";

if (isset($conn) && $conn) {
    echo "<p class='success'>✅ Database connected successfully!</p>";
    echo "<p class='info'>Database: " . mysqli_get_host_info($conn) . "</p>";
} else {
    echo "<p class='error'>❌ Database connection FAILED!</p>";
    echo "<p class='error'>Error: " . mysqli_connect_error() . "</p>";
    die();
}
echo "</div>";

// Step 3: Check if tables exist
echo "<div class='box'>";
echo "<h2>Step 3: Checking required tables</h2>";

$required_tables = ['orders', 'order_items', 'products', 'users', 'admins'];
$tables_status = [];

foreach ($required_tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        echo "<p class='success'>✅ Table '$table' exists</p>";
        $tables_status[$table] = true;
    } else {
        echo "<p class='error'>❌ Table '$table' NOT found!</p>";
        $tables_status[$table] = false;
    }
}
echo "</div>";

// Step 4: Check orders data
echo "<div class='box'>";
echo "<h2>Step 4: Checking orders data</h2>";

if ($tables_status['orders']) {
    $orders_query = "SELECT COUNT(*) as total FROM orders";
    $result = mysqli_query($conn, $orders_query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_orders = $row['total'];
        
        if ($total_orders > 0) {
            echo "<p class='success'>✅ Found $total_orders orders in database</p>";
            
            // Show sample orders
            $sample_orders = mysqli_query($conn, "SELECT * FROM orders LIMIT 5");
            echo "<h3>Sample Orders:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Total Amount</th><th>Status</th><th>Created At</th></tr>";
            while ($order = mysqli_fetch_assoc($sample_orders)) {
                echo "<tr>";
                echo "<td>" . $order['id'] . "</td>";
                echo "<td>" . $order['user_id'] . "</td>";
                echo "<td>₹" . $order['total_amount'] . "</td>";
                echo "<td>" . $order['status'] . "</td>";
                echo "<td>" . $order['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>⚠️ No orders found in database!</p>";
            echo "<p class='info'>This means users haven't placed any orders yet, or orders are not being saved properly.</p>";
            
            // Check if order insertion works
            echo "<h3>Testing order insertion:</h3>";
            $test_insert = "INSERT INTO orders (user_id, total_amount, status, email) 
                           VALUES (1, 999.00, 'pending', 'test@example.com')";
            if (mysqli_query($conn, $test_insert)) {
                $inserted_id = mysqli_insert_id($conn);
                echo "<p class='success'>✅ Test order inserted successfully! (ID: $inserted_id)</p>";
                // Delete test order
                mysqli_query($conn, "DELETE FROM orders WHERE id = $inserted_id");
                echo "<p class='info'>Test order deleted.</p>";
            } else {
                echo "<p class='error'>❌ Failed to insert test order: " . mysqli_error($conn) . "</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Error querying orders: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p class='error'>❌ Cannot check orders - table doesn't exist!</p>";
}
echo "</div>";

// Step 5: Check order_items data
echo "<div class='box'>";
echo "<h2>Step 5: Checking order items data</h2>";

if ($tables_status['order_items']) {
    $items_query = "SELECT COUNT(*) as total FROM order_items";
    $result = mysqli_query($conn, $items_query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_items = $row['total'];
        
        if ($total_items > 0) {
            echo "<p class='success'>✅ Found $total_items order items in database</p>";
        } else {
            echo "<p class='error'>⚠️ No order items found!</p>";
            echo "<p class='info'>Order items should be created when user places an order.</p>";
        }
    }
} else {
    echo "<p class='error'>❌ order_items table doesn't exist!</p>";
}
echo "</div>";

// Step 6: Check users data
echo "<div class='box'>";
echo "<h2>Step 6: Checking users data</h2>";

if ($tables_status['users']) {
    $users_query = "SELECT COUNT(*) as total FROM users";
    $result = mysqli_query($conn, $users_query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_users = $row['total'];
        echo "<p class='success'>✅ Found $total_users registered users</p>";
    }
} else {
    echo "<p class='error'>❌ users table doesn't exist!</p>";
}
echo "</div>";

// Step 7: Check admins data
echo "<div class='box'>";
echo "<h2>Step 7: Checking admin accounts</h2>";

if ($tables_status['admins']) {
    $admin_query = "SELECT COUNT(*) as total FROM admins";
    $result = mysqli_query($conn, $admin_query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_admins = $row['total'];
        
        if ($total_admins > 0) {
            echo "<p class='success'>✅ Found $total_admins admin accounts</p>";
            
            $admin_list = mysqli_query($conn, "SELECT id, username, email FROM admins");
            echo "<h3>Admin Accounts:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th></tr>";
            while ($admin = mysqli_fetch_assoc($admin_list)) {
                echo "<tr>";
                echo "<td>" . $admin['id'] . "</td>";
                echo "<td>" . $admin['username'] . "</td>";
                echo "<td>" . $admin['email'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>⚠️ No admin accounts found!</p>";
            echo "<p class='info'>You need to create an admin account to login.</p>";
            echo "<p>Run this SQL:</p>";
            echo "<pre>INSERT INTO admins (username, password, email) VALUES 
('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@shop.com');</pre>";
            echo "<p class='info'>Login: admin / admin123</p>";
        }
    }
} else {
    echo "<p class='error'>❌ admins table doesn't exist!</p>";
}
echo "</div>";

// Step 8: Test the orders query used in view-orders.php
echo "<div class='box'>";
echo "<h2>Step 8: Testing view-orders.php query</h2>";

$test_query = "SELECT o.*, u.username, u.email, u.phone 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               ORDER BY o.created_at DESC";

$test_result = mysqli_query($conn, $test_query);

if ($test_result) {
    $count = mysqli_num_rows($test_result);
    echo "<p class='success'>✅ Query executed successfully!</p>";
    echo "<p class='info'>Found $count orders</p>";
    
    if ($count > 0) {
        echo "<h3>Query Results Preview:</h3>";
        echo "<table>";
        echo "<tr><th>Order ID</th><th>Username</th><th>Email</th><th>Total</th><th>Status</th></tr>";
        $i = 0;
        while ($order = mysqli_fetch_assoc($test_result) && $i < 5) {
            echo "<tr>";
            echo "<td>#" . $order['id'] . "</td>";
            echo "<td>" . ($order['username'] ?? 'Guest') . "</td>";
            echo "<td>" . ($order['email'] ?? 'N/A') . "</td>";
            echo "<td>₹" . $order['total_amount'] . "</td>";
            echo "<td>" . $order['status'] . "</td>";
            echo "</tr>";
            $i++;
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>❌ Query FAILED!</p>";
    echo "<p class='error'>Error: " . mysqli_error($conn) . "</p>";
}
echo "</div>";

// Summary
echo "<div class='box' style='background: #e8f5e9;'>";
echo "<h2>📋 Summary & Recommendations</h2>";

$issues = [];

if (!$tables_status['orders']) {
    $issues[] = "Orders table missing - Run database_setup.sql";
}
if (!$tables_status['order_items']) {
    $issues[] = "Order_items table missing - Run database_setup.sql";
}
if (!$tables_status['admins']) {
    $issues[] = "Admins table missing - Run database_setup.sql";
}

$orders_count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
if ($orders_count_result) {
    $orders_count = mysqli_fetch_assoc($orders_count_result)['total'];
    if ($orders_count == 0) {
        $issues[] = "No orders in database - Users need to place orders first";
    }
}

if (empty($issues)) {
    echo "<p class='success'><strong>✅ Everything looks good!</strong></p>";
    echo "<p>Your database is properly set up. If orders still not showing in view-orders.php:</p>";
    echo "<ul>";
    echo "<li>Check session authentication in view-orders.php</li>";
    echo "<li>Clear browser cache and cookies</li>";
    echo "<li>Make sure you're accessing the correct URL</li>";
    echo "</ul>";
} else {
    echo "<p class='error'><strong>⚠️ Issues Found:</strong></p>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ol>";
    
    echo "<p><strong>Fix these issues:</strong></p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin</li>";
    echo "<li>Select your database</li>";
    echo "<li>Go to SQL tab</li>";
    echo "<li>Copy and paste all code from <code>database_setup.sql</code></li>";
    echo "<li>Click 'Go' to execute</li>";
    echo "</ol>";
}

echo "</div>";

mysqli_close($conn);

echo "<div class='box' style='background: #fff3cd;'>";
echo "<h3>🔒 SECURITY WARNING</h3>";
echo "<p class='error'>DELETE THIS FILE after diagnosis!</p>";
echo "<p>This file reveals database structure and should not be accessible publicly.</p>";
echo "</div>";
?>