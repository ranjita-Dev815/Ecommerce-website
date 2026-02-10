<?php
session_start();

// Optional: Check if admin is logged in
// if (!isset($_SESSION['admin_logged_in'])) {
//     header("Location: login.php");
//     exit();
// }

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "onlineshopdb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // First, get the image filename to delete from folder
    $sql_get_image = "SELECT image FROM products WHERE id = ?";
    $stmt_get = $conn->prepare($sql_get_image);
    $stmt_get->bind_param("i", $product_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_filename = $row['image'];
        
        // Delete product from database
        $sql_delete = "DELETE FROM products WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $product_id);
        
        if ($stmt_delete->execute()) {
            // If deletion successful, try to delete image file from folder
            if (!empty($image_filename)) {
                // Adjust the path according to your folder structure
                $image_path = "../image/" . $image_filename;
                
                // Check if file exists and delete it
                if (file_exists($image_path)) {
                    unlink($image_path); // Delete the image file
                }
            }
            
            // Success message
            $_SESSION['success_message'] = "Product deleted successfully!";
            header("Location: displayproduct.php");
            exit();
        } else {
            // Error in deletion
            $_SESSION['error_message'] = "Error deleting product: " . $conn->error;
            header("Location: displayproduct.php");
            exit();
        }
        
        $stmt_delete->close();
    } else {
        // Product not found
        $_SESSION['error_message'] = "Product not found!";
        header("Location: displayproduct.php");
        exit();
    }
    
    $stmt_get->close();
    
} else {
    // No ID provided
    $_SESSION['error_message'] = "Invalid product ID!";
    header("Location: displayproduct.php");
    exit();
}

$conn->close();
?>