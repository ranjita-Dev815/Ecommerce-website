<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$count = $row['total'] ?? 0;

echo json_encode(['success' => true, 'count' => $count]);

mysqli_close($conn);
?>