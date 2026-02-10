<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    // Check if product exists
    $check_product = "SELECT * FROM products WHERE id = $product_id";
    $product_result = mysqli_query($conn, $check_product);

    if (mysqli_num_rows($product_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Check if product already in cart
    $check_cart = "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id";
    $cart_result = mysqli_query($conn, $check_cart);

    if (mysqli_num_rows($cart_result) > 0) {
        // Update quantity
        $update = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $product_id";
        if (mysqli_query($conn, $update)) {
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
        }
    } else {
        // Add new item to cart
        $insert = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, 1)";
        if (mysqli_query($conn, $insert)) {
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>