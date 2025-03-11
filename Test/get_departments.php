<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Check if faculty_id is provided
if (!isset($_GET['faculty_id']) || empty($_GET['faculty_id'])) {
    echo json_encode([]);
    exit();
}

try {
    // Prepare SQL to fetch departments for the given faculty
    $sql = "SELECT department_id, department_name, thai_department_name 
            FROM department 
            WHERE faculty_id = ? 
            ORDER BY department_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $_GET['faculty_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch departments
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($departments);
} catch (PDOException $e) {
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
exit();
?>