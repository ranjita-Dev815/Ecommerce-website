<?php
$conn = mysqli_connect("localhost", "root", "", "onlineshopdb");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>