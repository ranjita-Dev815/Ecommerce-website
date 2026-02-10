<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Get form data
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    // Get cart items
    $cart_query = "SELECT c.*, p.name, p.price 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.user_id = $user_id";
    $cart_result = mysqli_query($conn, $cart_query);

    if (mysqli_num_rows($cart_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    // Calculate total
    $total_amount = 0;
    $cart_items = [];
    while ($item = mysqli_fetch_assoc($cart_result)) {
        $subtotal = $item['price'] * $item['quantity'];
        $total_amount += $subtotal;
        $cart_items[] = $item;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert into orders table
        $order_query = "INSERT INTO orders (
            user_id, customer_name, email, phone, address, city, state, pincode, 
            payment_method, total_amount, order_status, order_date
        ) VALUES (
            $user_id, '$customer_name', '$email', '$phone', '$address', '$city', 
            '$state', '$pincode', '$payment_method', $total_amount, 'Pending', NOW()
        )";

        if (!mysqli_query($conn, $order_query)) {
            throw new Exception('Failed to create order: ' . mysqli_error($conn));
        }

        $order_id = mysqli_insert_id($conn);

        // Insert order items
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $product_name = mysqli_real_escape_string($conn, $item['name']);
            $product_price = $item['price'];
            $quantity = $item['quantity'];
            $subtotal = $product_price * $quantity;

            $item_query = "INSERT INTO order_items (
                order_id, product_id, product_name, price, quantity, subtotal
            ) VALUES (
                $order_id, $product_id, '$product_name', $product_price, $quantity, $subtotal
            )";

            if (!mysqli_query($conn, $item_query)) {
                throw new Exception('Failed to add order items: ' . mysqli_error($conn));
            }
            
            // Update product stock
            $update_stock = "UPDATE products SET stock = stock - $quantity WHERE id = $product_id";
            if (!mysqli_query($conn, $update_stock)) {
                throw new Exception('Failed to update stock: ' . mysqli_error($conn));
            }
        }

        // Clear cart
        $clear_cart = "DELETE FROM cart WHERE user_id = $user_id";
        if (!mysqli_query($conn, $clear_cart)) {
            throw new Exception('Failed to clear cart');
        }

        // Commit transaction
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order_id
        ]);

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>