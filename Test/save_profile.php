<?php
// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=โปรดเข้าสู่ระบบก่อนใช้งาน");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get form data with validation
        $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : "";
        $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : "";
        $thai_first_name = isset($_POST['thai_first_name']) ? trim($_POST['thai_first_name']) : "";
        $thai_last_name = isset($_POST['thai_last_name']) ? trim($_POST['thai_last_name']) : "";
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : "";
        $address = isset($_POST['address']) ? trim($_POST['address']) : "";
        $faculty_id = isset($_POST['faculty_id']) ? intval($_POST['faculty_id']) : null;
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : null;
        
        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($thai_first_name) || empty($thai_last_name) || empty($phone) || empty($address)) {
            throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
        }
        
        // Validate phone number format
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            throw new Exception("เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก");
        }
        
        // Validate faculty, department, major
        if (!$faculty_id || !$department_id || !$major_id) {
            throw new Exception("กรุณาเลือกคณะ ภาควิชา และสาขาวิชา");
        }
        
        // Process profile image upload
        $profile_image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Validate file type and size
            if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                throw new Exception("ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะ JPEG, PNG, GIF");
            }
            
            if ($_FILES['profile_image']['size'] > $max_size) {
                throw new Exception("ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)");
            }
            
            // Create upload directory if not exists
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = $_SESSION['user_id'] . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
            $target_file = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image_path = $target_file;
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ");
            }
        }
        
        // Update user profile
        $update_profile_sql = "UPDATE user_profiles SET 
                              first_name = ?, last_name = ?, 
                              thai_first_name = ?, thai_last_name = ?,
                              phone = ?, address = ?,
                              faculty_id = ?, department_id = ?";
        
        // Add profile image to query if uploaded
        if ($profile_image_path) {
            $update_profile_sql .= ", profile_image = ?";
        }
        
        $update_profile_sql .= " WHERE id_account = ?";
        
        $update_profile_stmt = $conn->prepare($update_profile_sql);
        
        // Bind parameters
        $param_index = 1;
        $update_profile_stmt->bindParam($param_index++, $first_name);
        $update_profile_stmt->bindParam($param_index++, $last_name);
        $update_profile_stmt->bindParam($param_index++, $thai_first_name);
        $update_profile_stmt->bindParam($param_index++, $thai_last_name);
        $update_profile_stmt->bindParam($param_index++, $phone);
        $update_profile_stmt->bindParam($param_index++, $address);
        $update_profile_stmt->bindParam($param_index++, $faculty_id);
        $update_profile_stmt->bindParam($param_index++, $department_id);
        
        if ($profile_image_path) {
            $update_profile_stmt->bindParam($param_index++, $profile_image_path);
        }
        
        $update_profile_stmt->bindParam($param_index, $_SESSION['user_id']);
        $update_profile_stmt->execute();
        
        // Handle role-specific updates
        if ($_SESSION['role'] === 'student') {
            // Update student details with major
            $update_student_sql = "UPDATE student_details SET major_id = ? WHERE id_account = ?";
            $update_student_stmt = $conn->prepare($update_student_sql);
            $update_student_stmt->bindParam(1, $major_id);
            $update_student_stmt->bindParam(2, $_SESSION['user_id']);
            $update_student_stmt->execute();
        } elseif ($_SESSION['role'] === 'teacher') {
            // Get teacher form data
            $position = isset($_POST['position']) ? trim($_POST['position']) : "";
            $expertise = isset($_POST['expertise']) ? trim($_POST['expertise']) : "";
            $office_location = isset($_POST['office_location']) ? trim($_POST['office_location']) : "";
            $office_hours = isset($_POST['office_hours']) ? trim($_POST['office_hours']) : "";
            
            // Validate required teacher fields
            if (empty($position)) {
                throw new Exception("กรุณาระบุตำแหน่งของอาจารย์");
            }
            
            // Update teacher details
            $update_teacher_sql = "UPDATE teacher_details SET 
                                  position = ?, expertise = ?, 
                                  office_location = ?, office_hours = ? 
                                  WHERE id_account = ?";
            $update_teacher_stmt = $conn->prepare($update_teacher_sql);
            $update_teacher_stmt->bindParam(1, $position);
            $update_teacher_stmt->bindParam(2, $expertise);
            $update_teacher_stmt->bindParam(3, $office_location);
            $update_teacher_stmt->bindParam(4, $office_hours);
            $update_teacher_stmt->bindParam(5, $_SESSION['user_id']);
            $update_teacher_stmt->execute();
        }
        
        // Log the profile update
        $log_sql = "INSERT INTO logs (id_account, action, details, ip_address) 
                   VALUES (?, 'profile_update', 'User completed profile information', ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bindParam(1, $_SESSION['user_id']);
        $log_stmt->bindParam(2, $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();
        
        // Update user account status to active
        $update_status_sql = "UPDATE account SET status = 'active' WHERE id_account = ?";
        $update_status_stmt = $conn->prepare($update_status_sql);
        $update_status_stmt->bindParam(1, $_SESSION['user_id']);
        $update_status_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'student':
                header("Location: student_dashboard.php?success=บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว");
                break;
            case 'teacher':
                header("Location: teacher_dashboard.php?success=บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว");
                break;
            case 'academic':
                header("Location: academic_dashboard.php?success=บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว");
                break;
            default:
                header("Location: index.php?success=บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว");
                break;
        }
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Redirect back with error
        header("Location: profile_completion.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If not POST request, redirect to profile completion form
    header("Location: profile_completion.php");
    exit();
}
?>