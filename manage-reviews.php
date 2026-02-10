<?php
session_start();
include 'check-database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$message = '';
$success = false;

// Handle review status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $review_id = intval($_POST['review_id']);
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        if ($action == 'delete') {
            $query = "DELETE FROM product_reviews WHERE id = ?";
            $success_message = "Review deleted successfully!";
        } else {
            $status = $action == 'approve' ? 'approved' : 'rejected';
            $query = "UPDATE product_reviews SET status = ? WHERE id = ?";
            $success_message = "Review " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully!";
        }
        
        $stmt = mysqli_prepare($conn, $query);
        if ($action == 'delete') {
            mysqli_stmt_bind_param($stmt, "i", $review_id);
        } else {
            mysqli_stmt_bind_param($stmt, "si", $status, $review_id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            $message = $success_message;
        } else {
            $message = "Error updating review status.";
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_condition = '';
if ($filter != 'all') {
    $filter_condition = "WHERE r.status = '" . mysqli_real_escape_string($conn, $filter) . "'";
}

// Get all reviews with product information
$reviews_query = "SELECT r.*, p.product_name, p.product_image, o.order_id
                  FROM product_reviews r
                  JOIN products p ON r.product_id = p.product_id
                  JOIN orders o ON r.order_id = o.order_id
                  $filter_condition
                  ORDER BY r.review_date DESC";
$reviews_result = mysqli_query($conn, $reviews_query);

// Get counts for each status
$counts_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                 FROM product_reviews";
$counts_result = mysqli_query($conn, $counts_query);
$counts = mysqli_fetch_assoc($counts_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Product Reviews - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.8em;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
        }
        
        .stat-icon.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-icon.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-icon.approved {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-icon.rejected {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-content h3 {
            font-size: 2em;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-content p {
            color: #666;
            font-size: 0.95em;
        }
        
        .filter-tabs {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-tab:hover {
            background: #e0e0e0;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .reviews-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody td {
            padding: 15px;
            vertical-align: top;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .product-name {
            font-weight: 500;
            color: #333;
        }
        
        .order-id {
            color: #666;
            font-size: 0.85em;
        }
        
        .reviewer-info {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .review-date {
            color: #999;
            font-size: 0.85em;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2em;
        }
        
        .review-content {
            max-width: 400px;
        }
        
        .review-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .review-text {
            color: #666;
            font-size: 0.9em;
            line-height: 1.5;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background: #ffc107;
            color: #333;
        }
        
        .btn-reject:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .no-reviews {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-reviews div {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        @media (max-width: 1200px) {
            .reviews-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 1000px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📝 Manage Product Reviews</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">📊</div>
                <div class="stat-content">
                    <h3><?php echo $counts['total']; ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">⏳</div>
                <div class="stat-content">
                    <h3><?php echo $counts['pending']; ?></h3>
                    <p>Pending Reviews</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">✅</div>
                <div class="stat-content">
                    <h3><?php echo $counts['approved']; ?></h3>
                    <p>Approved Reviews</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">❌</div>
                <div class="stat-content">
                    <h3><?php echo $counts['rejected']; ?></h3>
                    <p>Rejected Reviews</p>
                </div>
            </div>
        </div>
        
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                All Reviews (<?php echo $counts['total']; ?>)
            </a>
            <a href="?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                Pending (<?php echo $counts['pending']; ?>)
            </a>
            <a href="?filter=approved" class="filter-tab <?php echo $filter == 'approved' ? 'active' : ''; ?>">
                Approved (<?php echo $counts['approved']; ?>)
            </a>
            <a href="?filter=rejected" class="filter-tab <?php echo $filter == 'rejected' ? 'active' : ''; ?>">
                Rejected (<?php echo $counts['rejected']; ?>)
            </a>
        </div>
        
        <div class="reviews-table">
            <?php if (mysqli_num_rows($reviews_result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Reviewer</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <img src="uploads/<?php echo htmlspecialchars($review['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                     class="product-image">
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></div>
                                    <div class="order-id">Order #<?php echo str_pad($review['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="reviewer-info"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['review_date'])); ?></div>
                        </td>
                        <td>
                            <div class="rating-stars">
                                <?php 
                                for ($i = 0; $i < $review['rating']; $i++) echo '★';
                                for ($i = $review['rating']; $i < 5; $i++) echo '☆';
                                ?>
                            </div>
                        </td>
                        <td>
                            <div class="review-content">
                                <?php if (!empty($review['review_title'])): ?>
                                    <div class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($review['review_text'])): ?>
                                    <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $review['status']; ?>">
                                <?php echo ucfirst($review['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($review['status'] != 'approved'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="action-btn btn-approve" 
                                            onclick="return confirm('Approve this review?')">
                                        ✓ Approve
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($review['status'] != 'rejected'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="action-btn btn-reject"
                                            onclick="return confirm('Reject this review?')">
                                        ✗ Reject
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="action-btn btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">
                                        🗑 Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-reviews">
                <div>📝</div>
                <p>No reviews found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>