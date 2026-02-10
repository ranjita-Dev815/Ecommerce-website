<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Get order details
    $order_query = "SELECT * FROM orders WHERE id = $order_id";
    $order_result = mysqli_query($conn, $order_query);

    if (mysqli_num_rows($order_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $order = mysqli_fetch_assoc($order_result);

    // Get order items
    $items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
    $items_result = mysqli_query($conn, $items_query);

    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
}

mysqli_close($conn);
?>