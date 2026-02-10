<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
$vote_type = isset($_POST['vote_type']) ? $_POST['vote_type'] : '';

if ($review_id <= 0 || !in_array($vote_type, ['helpful', 'not_helpful'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$column = $vote_type === 'helpful' ? 'helpful_count' : 'not_helpful_count';

// Update vote count
$update_query = "UPDATE reviews SET $column = $column + 1 WHERE id = '$review_id'";

if (mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record vote']);
}

mysqli_close($conn);
?>