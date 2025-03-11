<?php
// Include database connection
require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $faculty = isset($_POST['faculty']) ? trim($_POST['faculty']) : null;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        header("Location: index.php?error=กรุณากรอกข้อมูลให้ครบถ้วน");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=รูปแบบอีเมลไม่ถูกต้อง");
        exit();
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Check if username already exists
        $check_sql = "SELECT id_account FROM account WHERE username_account = ? OR email_account = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(1, $username);
        $check_stmt->bindParam(2, $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            header("Location: index.php?error=ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว");
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new account with active status instead of pending
        $sql = "INSERT INTO account (username_account, email_account, password_account, Role_account, status) 
                VALUES (?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $username);
        $stmt->bindParam(2, $email);
        $stmt->bindParam(3, $hashed_password);
        $stmt->bindParam(4, $role);
        $stmt->execute();
        
        // Get the new account ID
        $account_id = $conn->lastInsertId();
        
        // Create user profile
        $profile_sql = "INSERT INTO user_profiles (id_account) VALUES (?)";
        $profile_stmt = $conn->prepare($profile_sql);
        $profile_stmt->bindParam(1, $account_id);
        $profile_stmt->execute();
        
        // If student role, create student details
        if ($role === 'student') {
            // Generate a student code (current year + random 5 digits)
            $current_year = date('Y');
            $random_digits = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $student_code = $current_year . $random_digits;
            
            $student_sql = "INSERT INTO student_details (id_account, student_code, entry_year, entry_semester, study_year) 
                           VALUES (?, ?, ?, 1, 1)";
            $student_stmt = $conn->prepare($student_sql);
            $student_stmt->bindParam(1, $account_id);
            $student_stmt->bindParam(2, $student_code);
            $student_stmt->bindParam(3, $current_year);
            $student_stmt->execute();
        }
        
        // If teacher role, create teacher details
        if ($role === 'teacher') {
            // Generate a teacher code (T + current year + random 3 digits)
            $current_year = date('Y');
            $random_digits = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $teacher_code = 'T' . $current_year . $random_digits;
            
            $teacher_sql = "INSERT INTO teacher_details (id_account, teacher_code) VALUES (?, ?)";
            $teacher_stmt = $conn->prepare($teacher_sql);
            $teacher_stmt->bindParam(1, $account_id);
            $teacher_stmt->bindParam(2, $teacher_code);
            $teacher_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to login page with success message
        header("Location: index.php?success=สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่านของคุณ");
        exit();
        
    } catch (PDOException $e) {
        // Rollback transaction if error occurs
        $conn->rollBack();
        header("Location: index.php?error=เกิดข้อผิดพลาดในการสมัครสมาชิก: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>