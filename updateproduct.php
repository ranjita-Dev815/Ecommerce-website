<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "onlineshopdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// Get product ID from URL
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // Fetch product details
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        header("Location: displayproduct.php");
        exit();
    }
} else {
    header("Location: displayproduct.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_name = $_POST['category_name'];
    $old_image = $_POST['old_image'];
    
    // Handle image upload
    $image_name = $old_image; // Keep old image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../image/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "webp");
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Generate unique filename
            $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image if exists
                if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                    unlink($target_dir . $old_image);
                }
                $image_name = $new_filename;
            } else {
                $message = "Sorry, there was an error uploading your file.";
                $messageType = "error";
            }
        } else {
            $message = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            $messageType = "error";
        }
    }
    
    // Update product in database
    if ($messageType != "error") {
        $update_sql = "UPDATE products SET name=?, description=?, price=?, stock=?, image=?, category_name=? WHERE id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssdissi", $name, $description, $price, $stock, $image_name, $category_name, $product_id);
        
        if ($update_stmt->execute()) {
            $message = "Product updated successfully!";
            $messageType = "success";
            
            // Refresh product data
            $sql = "SELECT * FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
        } else {
            $message = "Error updating product: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Get image path
$imagePath = "";
if (!empty($product["image"])) {
    if (strpos($product["image"], '/') !== false || strpos($product["image"], '\\') !== false) {
        $imagePath = $product["image"];
    } else {
        $imagePath = "../image/" . $product["image"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product - <?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .btn-back {
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-back:hover {
            background: #7f8c8d;
        }

        .form-container {
            padding: 30px;
        }

        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .image-preview {
            margin-top: 10px;
            text-align: center;
        }

        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .image-upload-box {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .image-upload-box:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }

        .image-upload-box input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            background: #3498db;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .header h1 {
                font-size: 20px;
            }

            .form-container {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Update Product</h1>
            <a href="displayproduct.php" class="btn-back">← Back to Products</a>
        </div>

        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($product['image']); ?>">

                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (₹) *</label>
                        <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" value="<?php echo $product['stock']; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category_name">Category Name *</label>
                    <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($product['category_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Current Product Image</label>
                    <?php if (!empty($imagePath) && file_exists($imagePath)): ?>
                        <div class="image-preview">
                            <img src="<?php echo $imagePath; ?>" alt="Current Product Image">
                        </div>
                    <?php else: ?>
                        <p style="color: #999;">No image uploaded</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Upload New Image (Optional)</label>
                    <div class="image-upload-box" onclick="document.getElementById('image').click()">
                        <div class="upload-icon">📷</div>
                        <p>Click to select a new image</p>
                        <small style="color: #999;">JPG, JPEG, PNG, GIF or WEBP (Max 5MB)</small>
                        <input type="file" id="image" name="image" accept="image/*" onchange="previewNewImage(this)">
                    </div>
                    <div id="newImagePreview" class="image-preview" style="display: none;">
                        <p style="margin: 10px 0; font-weight: 600; color: #27ae60;">New Image Preview:</p>
                        <img id="previewImg" src="" alt="New Image Preview">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        function previewNewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('newImagePreview').style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

    <?php $conn->close(); ?>
</body>
</html>