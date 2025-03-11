<?php
// Initialize session
session_start();

// Check if user is logged in and has academic role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'academic') {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$errors = [];
$success_message = '';
$faculties = [];
$departments = [];
$majors = [];
$curriculums = [];

// Fetch faculties for dropdown
try {
    $faculty_sql = "SELECT id, faculty_name, thai_faculty_name FROM faculty ORDER BY thai_faculty_name";
    $faculty_stmt = $conn->prepare($faculty_sql);
    $faculty_stmt->execute();
    $faculties = $faculty_stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "การดึงข้อมูลคณะล้มเหลว: " . $e->getMessage();
}

// Fetch departments for dropdown
try {
    $department_sql = "SELECT department_id, faculty_id, department_name, thai_department_name FROM department ORDER BY thai_department_name";
    $department_stmt = $conn->prepare($department_sql);
    $department_stmt->execute();
    $departments = $department_stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "การดึงข้อมูลภาควิชาล้มเหลว: " . $e->getMessage();
}

// Fetch majors for dropdown
try {
    $major_sql = "SELECT major_id, department_id, major_name, thai_major_name FROM major ORDER BY thai_major_name";
    $major_stmt = $conn->prepare($major_sql);
    $major_stmt->execute();
    $majors = $major_stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "การดึงข้อมูลสาขาล้มเหลว: " . $e->getMessage();
}

// Fetch curriculums for dropdown
try {
    $curriculum_sql = "SELECT Curriculum_ID, Curriculum_Name FROM curriculum ORDER BY Curriculum_Name";
    $curriculum_stmt = $conn->prepare($curriculum_sql);
    $curriculum_stmt->execute();
    $curriculums = $curriculum_stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "การดึงข้อมูลหลักสูตรล้มเหลว: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $thai_first_name = trim($_POST['thai_first_name'] ?? '');
    $thai_last_name = trim($_POST['thai_last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $faculty_id = intval($_POST['faculty_id'] ?? 0);
    $department_id = intval($_POST['department_id'] ?? 0);
    $major_id = intval($_POST['major_id'] ?? 0);
    $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
    $entry_year = intval($_POST['entry_year'] ?? date('Y'));
    $entry_semester = intval($_POST['entry_semester'] ?? 1);
    $study_year = intval($_POST['study_year'] ?? 1);
    $student_code = trim($_POST['student_code'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    // Basic validation
    if (empty($username)) {
        $errors[] = "กรุณากรอกชื่อผู้ใช้";
    }
    if (empty($email)) {
        $errors[] = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    }
    if (empty($password)) {
        $errors[] = "กรุณากรอกรหัสผ่าน";
    } elseif (strlen($password) < 6) {
        $errors[] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    }
    if ($password !== $confirm_password) {
        $errors[] = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    }
    if (empty($student_code)) {
        $errors[] = "กรุณากรอกรหัสนักศึกษา";
    }
    if (empty($thai_first_name) || empty($thai_last_name)) {
        $errors[] = "กรุณากรอกชื่อ-นามสกุลภาษาไทย";
    }

    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $check_sql = "SELECT id_account FROM account WHERE username_account = ? OR email_account = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว";
            }
        } catch (PDOException $e) {
            $errors[] = "เกิดข้อผิดพลาดในการตรวจสอบข้อมูล: " . $e->getMessage();
        }
    }

    // Check if student code already exists
    if (empty($errors)) {
        try {
            $check_student_sql = "SELECT student_detail_id FROM student_details WHERE student_code = ?";
            $check_student_stmt = $conn->prepare($check_student_sql);
            $check_student_stmt->execute([$student_code]);
            
            if ($check_student_stmt->rowCount() > 0) {
                $errors[] = "รหัสนักศึกษานี้มีอยู่ในระบบแล้ว";
            }
        } catch (PDOException $e) {
            $errors[] = "เกิดข้อผิดพลาดในการตรวจสอบรหัสนักศึกษา: " . $e->getMessage();
        }
    }

    // If no errors, insert data
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert account
            $account_sql = "INSERT INTO account (username_account, email_account, password_account, Role_account, status) 
                           VALUES (?, ?, ?, 'student', 'active')";
            $account_stmt = $conn->prepare($account_sql);
            $account_stmt->execute([$username, $email, $hashed_password]);
            
            $account_id = $conn->lastInsertId();
            
            // Insert user profile
            $profile_sql = "INSERT INTO user_profiles (id_account, first_name, last_name, thai_first_name, thai_last_name, phone, address, faculty_id, department_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $profile_stmt = $conn->prepare($profile_sql);
            $profile_stmt->execute([$account_id, $first_name, $last_name, $thai_first_name, $thai_last_name, $phone, $address, $faculty_id, $department_id]);
            
            // Insert student details
            $student_sql = "INSERT INTO student_details (id_account, student_code, major_id, Curriculum_ID, entry_year, entry_semester, study_year, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $student_stmt = $conn->prepare($student_sql);
            $student_stmt->execute([$account_id, $student_code, $major_id, $curriculum_id, $entry_year, $entry_semester, $study_year, $status]);
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "เพิ่มข้อมูลนักศึกษาเรียบร้อยแล้ว";
            
            // Add log
            $log_sql = "INSERT INTO logs (id_account, action, details, ip_address) VALUES (?, 'add_student', ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->execute([$_SESSION['user_id'], "เพิ่มนักศึกษาใหม่: $student_code", $_SERVER['REMOTE_ADDR']]);
            
            // Reset form fields after successful submission
            $_POST = [];
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
    }
}

// Get user information
try {
    $sql = "SELECT a.*, up.* FROM account a 
            JOIN user_profiles up ON a.id_account = up.id_account
            WHERE a.id_account = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $errors[] = "การดึงข้อมูลผู้ใช้ล้มเหลว: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลนักศึกษา - Suan Dusit University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            background: #1b1e21;
            color: white;
            padding-top: 15px;
            padding-left: 0;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar.hidden {
            width: 0;
            overflow: hidden;
            padding: 0;
        }

        .sidebar .logo-container {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar img {
            max-width: 40px;
            margin-right: 10px;
        }

        .sidebar h3 {
            font-size: 1.1rem;
            color: #00c6ff;
            margin-bottom: 0;
            white-space: nowrap;
        }

        .sidebar a {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            color: #e0e0e0;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background: rgba(0, 123, 255, 0.1);
            color: white;
            border-left: 3px solid rgba(0, 123, 255, 0.5);
        }
        
        .sidebar a.active {
            background: rgba(0, 123, 255, 0.2);
            color: white;
            border-left: 3px solid #007bff;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .content {
            margin-left: 260px;
            padding: 20px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }

        .content.expanded {
            margin-left: 0;
        }

        .topbar {
            background: linear-gradient(45deg, #3871c1, #3871d3);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .topbar .menu-toggle {
            font-size: 22px;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            margin-right: 15px;
            transition: all 0.2s;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .topbar .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .topbar .dashboard-title {
            font-size: 20px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 18px;
            border-radius: 8px;
            margin: 0;
            line-height: 1.5;
        }

        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 15px;
            border-radius: 30px;
        }

        .topbar .user-info img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        .topbar .user-info div {
            line-height: 1.2;
        }

        .form-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .form-card:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .form-card h4 {
            color: #3871c1;
            font-weight: 600;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .section-header {
            color: #ffffff;
            border: 2px solid #1377db;
            padding: 12px;
            background-color: #1939c5;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .form-floating {
            position: relative;
        }
        
        .form-floating > .form-control,
        .form-floating > .form-select {
            height: calc(3.8rem + 2px);
            padding: 1.625rem 0.75rem 0.625rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 1rem;
            line-height: 1.25;
        }
        
        .form-floating > .form-control:focus,
        .form-floating > .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out, transform .1s ease-in-out;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label,
        .form-floating > .form-select ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
            background-color: #fff;
            height: auto;
            padding: 0 5px;
            margin-left: 5px;
            color: #3871c1;
            font-weight: 500;
        }
        
        /* แก้ไขปัญหาตัวอักษรทับกันใน dropdown */
        .form-select option {
            padding: 8px 12px;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        /* เพิ่ม spacing ใน dropdown */
        select.form-select {
            text-indent: 5px;
            padding-top: 1.625rem;
        }
        
        /* ปรับแต่ง dropdown เวลากดเลือก */
        select.form-select option:checked,
        select.form-select option:hover {
            background-color: #f0f7ff;
            color: #2b5ca3;
        }
        
        /* ทำให้ dropdown value ไม่ทับกับ label */
        .form-floating > .form-select {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }
        
        .alert {
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-lg {
            padding: 12px 25px;
        }

        .btn-primary {
            background-color: #3871c1;
            border-color: #3871c1;
        }

        .btn-primary:hover {
            background-color: #2b5ca3;
            border-color: #2b5ca3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .was-validated .form-control:invalid,
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            .content {
                margin-left: 0;
            }
            .topbar .dashboard-title {
                font-size: 18px;
                padding: 8px 12px;
            }
            .topbar .user-info {
                padding: 5px 10px;
            }
            .topbar .user-info img {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University">
            <h3>Suan Dusit University</h3>
        </div>
        <a href="academic_dashboard.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
        <a href="students.php"><i class="fas fa-user-graduate"></i> ข้อมูลนักศึกษา</a>
        <a href="student_add.php" class="active"><i class="fas fa-user-plus"></i> เพิ่มนักศึกษา</a>
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="majors.php"><i class="fas fa-graduation-cap"></i> สาขา/หลักสูตร</a>
        <a href="แผนการเรียน.php"><i class="fas fa-calendar-check"></i> แผนการเรียน</a>
        <a href="schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>
    
    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">เพิ่มข้อมูลนักศึกษาใหม่</div>
            <div class="user-info">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <div>
                    <strong><?php echo htmlspecialchars(($user['thai_first_name'] ? $user['thai_first_name'] . ' ' . $user['thai_last_name'] : $_SESSION['username'])); ?></strong>
                    <p class="m-0">ฝ่ายวิชาการ</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle me-2"></i>พบข้อผิดพลาด:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Student Form -->
        <div class="section-header mb-4">
            <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i> กรอกข้อมูลนักศึกษา</h2>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง ช่องที่มีเครื่องหมาย * จำเป็นต้องกรอก
        </div>

        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="row">
                <!-- Account Information -->
                <div class="col-lg-6">
                    <div class="form-card">
                        <h4 class="mb-3"><i class="fas fa-user-circle me-2"></i>ข้อมูลบัญชีผู้ใช้</h4>
                        <div class="mb-3 form-floating">
                            <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <label for="username">ชื่อผู้ใช้ (Username)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="อีเมล" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <label for="email">อีเมล (Email)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                            <label for="password">รหัสผ่าน (Password)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
                            <label for="confirm_password">ยืนยันรหัสผ่าน (Confirm Password)</label>
                        </div>
                    </div>
                </div>

                <!-- Student Information -->
                <div class="col-lg-6">
                    <div class="form-card">
                        <h4 class="mb-3"><i class="fas fa-id-card me-2"></i>ข้อมูลนักศึกษา</h4>
                        <div class="mb-3 form-floating">
                            <input type="text" class="form-control" id="student_code" name="student_code" placeholder="รหัสนักศึกษา" value="<?php echo htmlspecialchars($_POST['student_code'] ?? ''); ?>" required>
                            <label for="student_code">รหัสนักศึกษา</label>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <select class="form-select" id="entry_year" name="entry_year" required>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($i = $current_year; $i >= $current_year - 5; $i--): 
                                        ?>
                                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['entry_year']) && $_POST['entry_year'] == $i) ? 'selected' : ($i == $current_year ? 'selected' : ''); ?>>
                                            <?php echo $i; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                    <label for="entry_year">ปีที่เข้าศึกษา</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <select class="form-select" id="entry_semester" name="entry_semester" required>
                                        <option value="1" <?php echo (isset($_POST['entry_semester']) && $_POST['entry_semester'] == 1) ? 'selected' : 'selected'; ?>>ภาคเรียนที่ 1</option>
                                        <option value="2" <?php echo (isset($_POST['entry_semester']) && $_POST['entry_semester'] == 2) ? 'selected' : ''; ?>>ภาคเรียนที่ 2</option>
                                        <option value="3" <?php echo (isset($_POST['entry_semester']) && $_POST['entry_semester'] == 3) ? 'selected' : ''; ?>>ภาคฤดูร้อน</option>
                                    </select>
                                    <label for="entry_semester">ภาคเรียนที่เข้าศึกษา</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 form-floating">
                            <select class="form-select" id="study_year" name="study_year" required>
                                <option value="1" <?php echo (isset($_POST['study_year']) && $_POST['study_year'] == 1) ? 'selected' : 'selected'; ?>>ชั้นปีที่ 1</option>
                                <option value="2" <?php echo (isset($_POST['study_year']) && $_POST['study_year'] == 2) ? 'selected' : ''; ?>>ชั้นปีที่ 2</option>
                                <option value="3" <?php echo (isset($_POST['study_year']) && $_POST['study_year'] == 3) ? 'selected' : ''; ?>>ชั้นปีที่ 3</option>
                                <option value="4" <?php echo (isset($_POST['study_year']) && $_POST['study_year'] == 4) ? 'selected' : ''; ?>>ชั้นปีที่ 4</option>
                            </select>
                            <label for="study_year">ชั้นปี</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : 'selected'; ?>>กำลังศึกษา (Active)</option>
                                <option value="leave_of_absence" <?php echo (isset($_POST['status']) && $_POST['status'] == 'leave_of_absence') ? 'selected' : ''; ?>>ลาพักการศึกษา (Leave of Absence)</option>
                                <option value="graduated" <?php echo (isset($_POST['status']) && $_POST['status'] == 'graduated') ? 'selected' : ''; ?>>สำเร็จการศึกษา (Graduated)</option>
                                <option value="dismissed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'dismissed') ? 'selected' : ''; ?>>พ้นสภาพนักศึกษา (Dismissed)</option>
                            </select>
                            <label for="status">สถานะ</label>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="col-md-12">
                    <div class="form-card">
                        <h4 class="mb-3"><i class="fas fa-address-card me-2"></i>ข้อมูลส่วนตัว</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="ชื่อ (ภาษาอังกฤษ)" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                    <label for="first_name">ชื่อ (ภาษาอังกฤษ)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="นามสกุล (ภาษาอังกฤษ)" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                    <label for="last_name">นามสกุล (ภาษาอังกฤษ)</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" id="thai_first_name" name="thai_first_name" placeholder="ชื่อ (ภาษาไทย)" value="<?php echo htmlspecialchars($_POST['thai_first_name'] ?? ''); ?>" required>
                                    <label for="thai_first_name">ชื่อ (ภาษาไทย) *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" id="thai_last_name" name="thai_last_name" placeholder="นามสกุล (ภาษาไทย)" value="<?php echo htmlspecialchars($_POST['thai_last_name'] ?? ''); ?>" required>
                                    <label for="thai_last_name">นามสกุล (ภาษาไทย) *</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="เบอร์โทรศัพท์" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            <label for="phone">เบอร์โทรศัพท์</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <textarea class="form-control" id="address" name="address" placeholder="ที่อยู่" style="height: 100px"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            <label for="address">ที่อยู่</label>
                        </div>
                    </div>
                </div>

                <!-- Academic Information -->
                <div class="col-md-12">
                    <div class="form-card">
                        <h4 class="mb-3"><i class="fas fa-university me-2"></i>ข้อมูลทางวิชาการ</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <select class="form-select" id="faculty_id" name="faculty_id">
                                        <option value="">เลือกคณะ</option>
                                        <?php foreach ($faculties as $faculty): ?>
                                        <option value="<?php echo $faculty['id']; ?>" <?php echo (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $faculty['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($faculty['thai_faculty_name'] ?: $faculty['faculty_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="faculty_id">คณะ</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">เลือกภาควิชา</option>
                                        <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['department_id']; ?>" 
                                                data-faculty="<?php echo $department['faculty_id']; ?>" 
                                                <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['department_id']) ? 'selected' : ''; ?>
                                                class="department-option">
                                            <?php echo htmlspecialchars($department['thai_department_name'] ?: $department['department_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="department_id">ภาควิชา</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <select class="form-select" id="major_id" name="major_id">
                                        <option value="">เลือกสาขาวิชา</option>
                                        <?php foreach ($majors as $major): ?>
                                        <option value="<?php echo $major['major_id']; ?>" 
                                                data-department="<?php echo $major['department_id']; ?>" 
                                                <?php echo (isset($_POST['major_id']) && $_POST['major_id'] == $major['major_id']) ? 'selected' : ''; ?>
                                                class="major-option">
                                            <?php echo htmlspecialchars($major['thai_major_name'] ?: $major['major_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="major_id">สาขาวิชา</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-floating">
                                    <select class="form-select" id="curriculum_id" name="curriculum_id">
                                        <option value="">เลือกหลักสูตร</option>
                                        <?php foreach ($curriculums as $curriculum): ?>
                                        <option value="<?php echo $curriculum['Curriculum_ID']; ?>" <?php echo (isset($_POST['curriculum_id']) && $_POST['curriculum_id'] == $curriculum['Curriculum_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($curriculum['Curriculum_Name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="curriculum_id">หลักสูตร</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-card">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="students.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>กลับไปหน้ารายการนักศึกษา
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>บันทึกข้อมูลนักศึกษา
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            sidebar.classList.toggle('hidden');
            content.classList.toggle('expanded');
            
            // Update content margin
            if (sidebar.classList.contains('hidden')) {
                content.style.marginLeft = '0';
            } else {
                content.style.marginLeft = '260px';
            }
        });

        // Filter departments based on selected faculty
        document.getElementById('faculty_id').addEventListener('change', function() {
            const facultyId = this.value;
            const departmentSelect = document.getElementById('department_id');
            const majorSelect = document.getElementById('major_id');
            
            // Reset department and major selections
            departmentSelect.value = '';
            majorSelect.value = '';
            
            // Hide/show department options based on selected faculty
            const departmentOptions = document.querySelectorAll('.department-option');
            departmentOptions.forEach(option => {
                if (!facultyId || option.dataset.faculty === facultyId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Hide all major options when faculty changes
            const majorOptions = document.querySelectorAll('.major-option');
            majorOptions.forEach(option => {
                option.style.display = 'none';
            });
        });
        
        // Filter majors based on selected department
        document.getElementById('department_id').addEventListener('change', function() {
            const departmentId = this.value;
            const majorSelect = document.getElementById('major_id');
            
            // Reset major selection
            majorSelect.value = '';
            
            // Hide/show major options based on selected department
            const majorOptions = document.querySelectorAll('.major-option');
            majorOptions.forEach(option => {
                if (!departmentId || option.dataset.department === departmentId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        });
        
                    // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger faculty change to filter departments
            const facultySelect = document.getElementById('faculty_id');
            const event = new Event('change');
            facultySelect.dispatchEvent(event);
            
            // If department is already selected, filter majors
            const departmentSelect = document.getElementById('department_id');
            if (departmentSelect.value) {
                departmentSelect.dispatchEvent(event);
            }
            
            // Add floating label animation
            const formControls = document.querySelectorAll('.form-control, .form-select');
            formControls.forEach(control => {
                if (control.value) {
                    control.classList.add('filled');
                }
                control.addEventListener('input', function() {
                    if (this.value) {
                        this.classList.add('filled');
                    } else {
                        this.classList.remove('filled');
                    }
                });
            });
            
            // Add card hover effect
            const formCards = document.querySelectorAll('.form-card');
            formCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.1)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.08)';
                });
            });
        });
        
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms we want to apply validation to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
    </script>
</body>
</html>