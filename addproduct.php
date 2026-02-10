<?php
    // Database connection
    $host = 'localhost';
    $dbname = 'onlineshopdb';
    $username = 'root';
    $password = '';

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $message = '';
    $messageType = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = mysqli_real_escape_string($conn, $_POST['product_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $stock = mysqli_real_escape_string($conn, $_POST['stock']);
        $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

        // Image upload handling
        $image_path = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            
            // Get the current directory path and go one level up
            $current_dir = dirname(__FILE__);
            $parent_dir = dirname($current_dir);
            $target_dir = $parent_dir . "/uploads/";
            
            // Create uploads directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
                chmod($target_dir, 0777);
            }

            // Generate unique filename
            $image_name = time() . '_' . basename($_FILES["product_image"]["name"]);
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validate image
            $check = getimagesize($_FILES["product_image"]["tmp_name"]);
            if ($check !== false) {
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if ($_FILES["product_image"]["size"] <= 5000000) {
                        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                            // Store relative path for database
                            $image_path = "uploads/" . $image_name;
                            $message = "✅ Image uploaded successfully to: " . $target_dir;
                            $messageType = "success";
                        } else {
                            $message = "❌ Sorry, there was an error uploading your file. Check folder permissions!";
                            $messageType = "error";
                            // Debug info
                            $message .= "<br>Target: " . $target_file;
                            $message .= "<br>Temp: " . $_FILES["product_image"]["tmp_name"];
                        }
                    } else {
                        $message = "❌ File too large. Max 5MB allowed.";
                        $messageType = "error";
                    }
                } else {
                    $message = "❌ Only JPG, JPEG, PNG & GIF files allowed.";
                    $messageType = "error";
                }
            } else {
                $message = "❌ File is not an image.";
                $messageType = "error";
            }
        } else {
            $message = "❌ Please select an image to upload.";
            if (isset($_FILES['product_image']['error'])) {
                $message .= " Error code: " . $_FILES['product_image']['error'];
            }
            $messageType = "error";
        }

        // Insert into database only if image uploaded successfully
        if (!empty($image_path)) {
            $sql = "INSERT INTO products (name, description, price, stock, image, category_id, category) 
                    VALUES ('$name', '$description', '$price', '$stock', '$image_path', '$category_id', '$category_name')";

            if ($conn->query($sql) === TRUE) {
                $message = "✅ Product added successfully! Image saved at: $image_path";
                $messageType = "success";
                echo "<script>
                    setTimeout(function() {
                        document.getElementById('productForm').reset();
                        document.getElementById('imagePreview').style.display = 'none';
                    }, 2000);
                </script>";
            } else {
                $message = "❌ Database Error: " . $conn->error;
                $messageType = "error";
            }
        }
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Add Product</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-x: hidden;
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #008B8B 0%, #006666 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: transform 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar-header {
            padding: 30px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            padding: 30px 20px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            margin-bottom: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 16px;
            border-radius: 10px;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            width: calc(100% - 280px);
            padding: 40px;
        }

        .dashboard-main {
            max-width: 900px;
            margin: 0 auto;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #008B8B;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 139, 139, 0.1);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        ::placeholder {
            color: #999;
            font-weight: 400;
        }

        .upload-section {
            text-align: center;
            margin: 40px 0;
            padding: 40px;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-radius: 16px;
            border: 2px dashed #008B8B;
            transition: all 0.3s;
        }

        .upload-section:hover {
            border-color: #006666;
            background: linear-gradient(135deg, #667eea25 0%, #764ba225 100%);
            transform: scale(1.02);
        }

        .upload-section h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #008B8B;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .file-input-wrapper {
            display: inline-block;
            margin-bottom: 10px;
        }

        .file-input-wrapper input[type="file"] {
            padding: 12px 24px;
            border: 2px solid #008B8B;
            border-radius: 10px;
            cursor: pointer;
            background: white;
            transition: all 0.3s;
            font-weight: 500;
        }

        .file-input-wrapper input[type="file"]:hover {
            background: #008B8B;
            color: white;
        }

        #imagePreview {
            margin-top: 25px;
            max-width: 100%;
            max-height: 350px;
            border-radius: 12px;
            display: none;
            border: 3px solid #008B8B;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: zoomIn 0.4s ease;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .category-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .category-section select {
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .category-section select:focus {
            outline: none;
            border-color: #008B8B;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 139, 139, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #008B8B 0%, #006666 100%);
            color: white;
            padding: 16px 60px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
            margin: 40px auto 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 30px rgba(0, 139, 139, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 139, 139, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .menu-toggle {
            display: none;
            background: white;
            border: none;
            cursor: pointer;
            padding: 12px;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .menu-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background: #008B8B;
            margin: 5px 0;
            transition: 0.3s;
            border-radius: 3px;
        }

        @media (max-width: 968px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .menu-toggle {
                display: block;
            }

            .form-container {
                padding: 30px 20px;
            }

            .category-section {
                grid-template-columns: 1fr;
            }

            .dashboard-main {
                margin-top: 40px;
            }
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            backdrop-filter: blur(5px);
        }

        .overlay.active {
            display: block;
        }

        .alert {
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-weight: 600;
            animation: slideDown 0.5s ease;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 3px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 3px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>Add Product</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="addproduct.php" class="nav-item active">📦 Add Product</a>
                <a href="displayproduct.php" class="nav-item">👁️ View order</a>
                <a href="../logout.php" class="nav-item">🚪 Log Out</a>
            </nav>
        </aside>

        <button class="menu-toggle" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="overlay" id="overlay"></div>

        <div class="main-content">
            <main class="dashboard-main">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form id="productForm" method="POST" action="addproduct.php" enctype="multipart/form-data">
                        
                        <div class="form-group">
                            <input type="text" id="productName" name="product_name" placeholder="🏷️ Enter product name" required>
                        </div>

                        <div class="form-group">
                            <textarea id="description" name="description" placeholder="📝 Enter product description" required></textarea>
                        </div>

                        <div class="form-group">
                            <input type="number" id="price" name="price" placeholder="💰 Enter Price here!" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <input type="number" id="stock" name="stock" placeholder="📊 Enter Stock number" required>
                        </div>

                        <div class="upload-section">
                            <h3>📸 Upload Image Here!</h3>
                            <div class="file-input-wrapper">
                                <input type="file" id="productImage" name="product_image" accept="image/*" required>
                            </div>
                            <img id="imagePreview" alt="Preview">
                        </div>

                        <div class="category-section">
                            <select id="categoryId" name="category_id" required>
                                <option value="">📋 Category ID</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>

                            <select id="categoryName" name="category_name" required>
                                <option value="">🏪 Category Name</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Clothing">Clothing</option>
                                <option value="Books">Books</option>
                                <option value="Home & Garden">Home & Garden</option>
                                <option value="Sports">Sports</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-submit">✨ Add Product</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        const imageInput = document.getElementById('productImage');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>