<?php
// Initialize session
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Check if the account exists and is active
        $check_account_sql = "SELECT id_account, username_account, Role_account, status FROM account WHERE id_account = ?";
        $check_account_stmt = $conn->prepare($check_account_sql);
        $check_account_stmt->bindParam(1, $_SESSION['user_id']);
        $check_account_stmt->execute();
        
        if ($check_account_stmt->rowCount() == 1) {
            $account = $check_account_stmt->fetch();
            
            // Check if account is active
            if ($account['status'] != 'active') {
                // Account is not active, destroy session and redirect to login
                session_unset();
                session_destroy();
                header("Location: index.php?error=บัญชีของคุณยังไม่ถูกเปิดใช้งาน");
                exit();
            }
            
            // Check if profile is completed for any role
            $check_profile_sql = "SELECT * FROM user_profiles WHERE id_account = ? AND 
                                  (first_name IS NOT NULL AND last_name IS NOT NULL AND 
                                   thai_first_name IS NOT NULL AND thai_last_name IS NOT NULL)";
            $check_profile_stmt = $conn->prepare($check_profile_sql);
            $check_profile_stmt->bindParam(1, $_SESSION['user_id']);
            $check_profile_stmt->execute();
            
            // If profile is not completed, redirect to profile completion page
            if ($check_profile_stmt->rowCount() == 0) {
                header("Location: profile_completion.php");
                exit();
            }
            
            // If role is different from what's in session, update session
            if ($account['Role_account'] != $_SESSION['role']) {
                $_SESSION['role'] = $account['Role_account'];
            }
            
            // Redirect based on role
            switch ($_SESSION['role']) {
                case 'student':
                    header("Location: student_dashboard.php");
                    break;
                case 'teacher':
                    header("Location: teacher_dashboard.php");
                    break;
                case 'academic':
                    header("Location: academic_dashboard.php");
                    break;
                default:
                    // If role is not recognized, destroy session and reload login page
                    session_unset();
                    session_destroy();
                    header("Location: index.php?error=บัญชีของคุณมีบทบาทไม่ถูกต้อง");
                    break;
            }
            exit();
            
        } else {
            // User account not found, destroy session and redirect to login
            session_unset();
            session_destroy();
            header("Location: index.php?error=บัญชีของคุณไม่พบในระบบ");
            exit();
        }
        
    } catch (PDOException $e) {
        // Database error, log and redirect to login
        error_log("Check Login Error: " . $e->getMessage());
        session_unset();
        session_destroy();
        header("Location: index.php?error=เกิดข้อผิดพลาดในการตรวจสอบการเข้าสู่ระบบ");
        exit();
    }
} else {
    // No session, redirect to login page
    header("Location: index.php");
    exit();
}
?>