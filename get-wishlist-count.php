<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['count' => intval($row['count'])]);
} else {
    echo json_encode(['count' => 0]);
}

mysqli_close($conn);
?>