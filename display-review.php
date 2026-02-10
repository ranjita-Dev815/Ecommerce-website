<?php
// Include this file in your product detail page (e.g., product-detail.php)
// Make sure $product_id is set before including this file

// Get approved reviews for this product
$reviews_query = "SELECT * FROM product_reviews 
                  WHERE product_id = ? AND status = 'approved' 
                  ORDER BY review_date DESC";
$stmt = mysqli_prepare($conn, $reviews_query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$reviews_result = mysqli_stmt_get_result($stmt);
$reviews = mysqli_fetch_all($reviews_result, MYSQLI_ASSOC);

// Calculate average rating and rating distribution
$rating_stats_query = "SELECT 
                        COUNT(*) as total_reviews,
                        AVG(rating) as avg_rating,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                       FROM product_reviews 
                       WHERE product_id = ? AND status = 'approved'";
$stmt = mysqli_prepare($conn, $rating_stats_query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$rating_stats = mysqli_fetch_assoc($stats_result);

$total_reviews = $rating_stats['total_reviews'] ?? 0;
$avg_rating = $rating_stats['avg_rating'] ?? 0;
?>

<style>
.reviews-section {
    margin-top: 50px;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.reviews-header {
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.reviews-header h2 {
    font-size: 2em;
    color: #333;
    margin-bottom: 15px;
}

.rating-summary {
    display: flex;
    gap: 40px;
    align-items: center;
    flex-wrap: wrap;
}

.average-rating {
    text-align: center;
}

.average-rating .big-rating {
    font-size: 3.5em;
    font-weight: bold;
    color: #333;
    line-height: 1;
}

.average-rating .stars {
    color: #ffc107;
    font-size: 1.5em;
    margin: 10px 0;
}

.average-rating .total-reviews {
    color: #666;
    font-size: 0.95em;
}

.rating-distribution {
    flex: 1;
    min-width: 300px;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.rating-bar .label {
    width: 60px;
    font-size: 0.9em;
    color: #666;
}

.rating-bar .bar-container {
    flex: 1;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.rating-bar .bar-fill {
    height: 100%;
    background: #ffc107;
    transition: width 0.3s ease;
}

.rating-bar .count {
    width: 40px;
    text-align: right;
    font-size: 0.9em;
    color: #666;
}

.reviews-list {
    margin-top: 30px;
}

.review-item {
    padding: 25px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 20px;
    transition: box-shadow 0.3s;
}

.review-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.reviewer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3em;
    font-weight: bold;
}

.reviewer-details h4 {
    font-size: 1.1em;
    color: #333;
    margin-bottom: 5px;
}

.review-rating {
    color: #ffc107;
    font-size: 1.1em;
}

.review-date {
    color: #999;
    font-size: 0.85em;
}

.review-title {
    font-size: 1.2em;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.review-text {
    color: #666;
    line-height: 1.6;
    font-size: 0.95em;
}

.no-reviews {
    text-align: center;
    padding: 50px 20px;
    color: #999;
}

.no-reviews i {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.3;
}

.no-reviews p {
    font-size: 1.1em;
}

.verified-purchase {
    display: inline-block;
    background: #4CAF50;
    color: white;
    font-size: 0.75em;
    padding: 3px 8px;
    border-radius: 3px;
    margin-left: 10px;
    font-weight: 500;
}

@media (max-width: 768px) {
    .rating-summary {
        flex-direction: column;
        gap: 30px;
    }
    
    .rating-distribution {
        width: 100%;
    }
}
</style>

<div class="reviews-section">
    <div class="reviews-header">
        <h2>Customer Reviews</h2>
        
        <?php if ($total_reviews > 0): ?>
        <div class="rating-summary">
            <div class="average-rating">
                <div class="big-rating"><?php echo number_format($avg_rating, 1); ?></div>
                <div class="stars">
                    <?php 
                    $full_stars = floor($avg_rating);
                    $half_star = ($avg_rating - $full_stars) >= 0.5;
                    
                    for ($i = 0; $i < $full_stars; $i++) echo '★';
                    if ($half_star) echo '☆';
                    for ($i = 0; $i < (5 - $full_stars - ($half_star ? 1 : 0)); $i++) echo '☆';
                    ?>
                </div>
                <div class="total-reviews"><?php echo $total_reviews; ?> <?php echo $total_reviews == 1 ? 'review' : 'reviews'; ?></div>
            </div>
            
            <div class="rating-distribution">
                <?php 
                for ($i = 5; $i >= 1; $i--):
                    $star_count = $rating_stats[match($i) {
                        5 => 'five_star',
                        4 => 'four_star',
                        3 => 'three_star',
                        2 => 'two_star',
                        1 => 'one_star'
                    }];
                    $percentage = $total_reviews > 0 ? ($star_count / $total_reviews) * 100 : 0;
                ?>
                <div class="rating-bar">
                    <div class="label"><?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?></div>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="count"><?php echo $star_count; ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="reviews-list">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-item">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar">
                            <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                        </div>
                        <div class="reviewer-details">
                            <h4>
                                <?php echo htmlspecialchars($review['customer_name']); ?>
                                <span class="verified-purchase">Verified Purchase</span>
                            </h4>
                            <div class="review-rating">
                                <?php 
                                for ($i = 0; $i < $review['rating']; $i++) echo '★';
                                for ($i = $review['rating']; $i < 5; $i++) echo '☆';
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="review-date">
                        <?php echo date('F d, Y', strtotime($review['review_date'])); ?>
                    </div>
                </div>
                
                <?php if (!empty($review['review_title'])): ?>
                <div class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($review['review_text'])): ?>
                <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-reviews">
                <div style="font-size: 4em; margin-bottom: 20px;">📝</div>
                <p>No reviews yet. Be the first to review this product!</p>
            </div>
        <?php endif; ?>
    </div>
</div>