<?php
// Database configuration
$host = 'localhost';
$dbname = 'student_data_tracking';
$username = 'root';  // Change to your database username
$password = '';      // Change to your database password

// Connect to database using PDO
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
?>