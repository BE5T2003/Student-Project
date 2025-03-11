<?php
// Initialize session
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        header("Location: index.php?error=กรุณากรอกชื่อผู้ใช้และรหัสผ่าน");
        exit();
    }
    
    try {
        // Prepare SQL statement
        $sql = "SELECT id_account, username_account, password_account, Role_account, status FROM account WHERE username_account = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $row['password_account'])) {
                
                // Only check if account is inactive
                if ($row['status'] == 'inactive') {
                    header("Location: index.php?error=บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ");
                    exit();
                }
                
                // Set session variables with explicit casting to ensure proper types
                $_SESSION['user_id'] = (int)$row['id_account'];
                $_SESSION['username'] = (string)$row['username_account'];
                $_SESSION['role'] = (string)$row['Role_account'];
                
                // Log successful login
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO logs (id_account, action, details, ip_address) VALUES (?, 'login', 'User logged in', ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bindParam(1, $row['id_account']);
                $log_stmt->bindParam(2, $ip_address);
                $log_stmt->execute();
                
                // Update last login time
                $update_sql = "UPDATE account SET last_login = NOW() WHERE id_account = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(1, $row['id_account']);
                $update_stmt->execute();
                
                // Debug session
                // echo "<pre>SESSION after login: "; print_r($_SESSION); echo "</pre>"; exit;
                
                // Redirect based on role
                switch ($_SESSION['role']) {
                    case 'student':
                        header("Location: student_dashboard");
                        break;
                    case 'teacher':
                        header("Location: teacher_dashboard.php");
                        break;
                    case 'academic':
                        header("Location: academic_dashboard.php");
                        break;
                    default:
                        header("Location: index.php");
                        break;
                }
                header("Location: check_login.php");
                exit();
            } else {
                header("Location: index.php?error=ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง");
                exit();
            }
        } else {
            header("Location: index.php?error=ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: index.php?error=เกิดข้อผิดพลาดในการเข้าสู่ระบบ: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>