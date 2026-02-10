<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review_text = isset($_POST['review_text']) ? mysqli_real_escape_string($conn, trim($_POST['review_text'])) : '';

// Validate inputs
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit();
}

if (empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Please write a review']);
    exit();
}

// Check if user already reviewed this product
$check_query = "SELECT id FROM reviews WHERE user_id = '$user_id' AND product_id = '$product_id'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    // Update existing review
    $update_query = "UPDATE reviews 
                     SET rating = '$rating', 
                         review_text = '$review_text', 
                         created_at = NOW() 
                     WHERE user_id = '$user_id' AND product_id = '$product_id'";
    
    if (mysqli_query($conn, $update_query)) {
        echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update review']);
    }
} else {
    // Insert new review
    $insert_query = "INSERT INTO reviews (user_id, product_id, rating, review_text, created_at) 
                     VALUES ('$user_id', '$product_id', '$rating', '$review_text', NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    }
}

mysqli_close($conn);
?>