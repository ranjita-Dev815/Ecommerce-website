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
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Get current cart item
    $query = "SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = $cart_id AND c.user_id = $user_id";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    $item = mysqli_fetch_assoc($result);
    $current_qty = $item['quantity'];
    $price = $item['price'];

    if ($action === 'increase') {
        $new_qty = $current_qty + 1;
        $update = "UPDATE cart SET quantity = $new_qty WHERE id = $cart_id";
        mysqli_query($conn, $update);
        
        echo json_encode([
            'success' => true,
            'quantity' => $new_qty,
            'subtotal' => $new_qty * $price
        ]);
    } elseif ($action === 'decrease') {
        if ($current_qty > 1) {
            $new_qty = $current_qty - 1;
            $update = "UPDATE cart SET quantity = $new_qty WHERE id = $cart_id";
            mysqli_query($conn, $update);
            
            echo json_encode([
                'success' => true,
                'quantity' => $new_qty,
                'subtotal' => $new_qty * $price
            ]);
        } else {
            // Remove item if quantity becomes 0
            $delete = "DELETE FROM cart WHERE id = $cart_id";
            mysqli_query($conn, $delete);
            
            echo json_encode([
                'success' => true,
                'removed' => true
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>