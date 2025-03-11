<?php
// Initialize session
session_start();

// Include database connection if needed for logging
require_once 'db_connect.php';

// Log logout action if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $log_sql = "INSERT INTO logs (id_account, action, details, ip_address) VALUES (?, 'logout', 'User logged out', ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bindParam(1, $user_id);
        $log_stmt->bindParam(2, $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();
    } catch (PDOException $e) {
        // Silently fail - don't disrupt logout process
    }
}

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>