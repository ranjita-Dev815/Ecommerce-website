<?php
// Add this code to your order confirmation page and order history page
// to display "Write Review" button for completed orders

// Example usage in order-confirmation.php:
// After displaying order details, add this code to show review buttons for each product

// For order confirmation page (after order is placed):
?>

<!-- Add this CSS in your order confirmation page -->
<style>
.review-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 2px dashed #ccc;
}

.review-section h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.3em;
}

.review-section p {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

.review-btn {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    margin: 5px;
}

.review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.review-btn.disabled {
    background: #ccc;
    cursor: not-allowed;
    pointer-events: none;
}

.order-items-list {
    margin-top: 20px;
}

.order-item-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.item-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}

.item-details h4 {
    color: #333;
    margin-bottom: 5px;
}

.item-details p {
    color: #666;
    font-size: 0.9em;
    margin: 0;
}

.review-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
}

.review-status.pending {
    background: #fff3cd;
    color: #856404;
}

.review-status.completed {
    background: #d4edda;
    color: #155724;
}
</style>

<?php
// Example code to get order items and check review status
// Add this PHP code where you want to display review options

/*
// Get order items
$order_items_query = "SELECT oi.*, p.product_name, p.product_image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $order_items_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Check if order is delivered or completed (you might have different status names)
$can_review = in_array($order_status, ['delivered', 'completed']);
*/
?>

<!-- HTML to display review section -->
<div class="review-section">
    <h3>📝 Share Your Experience</h3>
    <p>Your feedback helps other customers make informed decisions. Please take a moment to review the products from your order.</p>
    
    <div class="order-items-list">
        <?php 
        // Example: Loop through order items
        // Replace this with your actual order items query
        /*
        foreach ($order_items as $item):
            // Check if review exists for this product and order
            $review_check = "SELECT * FROM product_reviews 
                           WHERE order_id = ? AND product_id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $review_check);
            mysqli_stmt_bind_param($stmt, "iii", $order_id, $item['product_id'], $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $review_exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        ?>
        
        <div class="order-item-card">
            <div class="item-info">
                <img src="uploads/<?php echo htmlspecialchars($item['product_image']); ?>" 
                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                     class="item-image">
                <div class="item-details">
                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                    <p>Quantity: <?php echo $item['quantity']; ?> | Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                </div>
            </div>
            
            <div>
                <?php if ($review_exists): ?>
                    <span class="review-status <?php echo $review_exists['status']; ?>">
                        Review <?php echo ucfirst($review_exists['status']); ?>
                    </span>
                    <a href="submit-review.php?order_id=<?php echo $order_id; ?>&product_id=<?php echo $item['product_id']; ?>" 
                       class="review-btn">
                        Edit Review
                    </a>
                <?php elseif ($can_review): ?>
                    <a href="submit-review.php?order_id=<?php echo $order_id; ?>&product_id=<?php echo $item['product_id']; ?>" 
                       class="review-btn">
                        Write Review
                    </a>
                <?php else: ?>
                    <span class="review-status pending">Order must be delivered</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endforeach; */
        ?>
    </div>
</div>

<?php
// ========================================
// FOR MY ORDERS PAGE (order history)
// ========================================
// Add this in your my-orders.php or order history page
// Inside the order card/row, add a review button
?>

<!-- Example button to add in order list -->
<!--
<a href="order-confirmation.php?order_id=<?php echo $order['order_id']; ?>#reviews" 
   class="review-btn" 
   style="font-size: 0.9em; padding: 8px 15px;">
    Write Reviews
</a>
-->

<?php
// ========================================
// ALTERNATIVE: Simple implementation
// ========================================
// If you want a simpler approach, just add this after order confirmation message:
?>

<div style="text-align: center; margin-top: 30px; padding: 30px; background: #f8f9fa; border-radius: 10px;">
    <h3 style="color: #333; margin-bottom: 15px;">📝 How was your experience?</h3>
    <p style="color: #666; margin-bottom: 20px;">
        Once your order is delivered, you can share your feedback about the products.
    </p>
    <a href="my-orders.php" 
       style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
        View My Orders
    </a>
</div>