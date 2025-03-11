<?php
// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Get current user's information
try {
    $sql = "SELECT a.*, up.*, f.faculty_name, f.thai_faculty_name, 
                   d.department_name, d.thai_department_name 
            FROM account a
            JOIN user_profiles up ON a.id_account = up.id_account
            LEFT JOIN faculty f ON up.faculty_id = f.id
            LEFT JOIN department d ON up.department_id = d.department_id
            WHERE a.id_account = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all faculties for dropdown
    $faculty_sql = "SELECT id, faculty_name, thai_faculty_name FROM faculty ORDER BY faculty_name";
    $faculty_stmt = $conn->prepare($faculty_sql);
    $faculty_stmt->execute();
    $faculties = $faculty_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_name = $user['id_account'] . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
            $upload_path = $upload_dir . $file_name;

            // Check file size (max 5MB)
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                $error_message = 'ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB';
            } else {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_image']['type'];

                if (in_array($file_type, $allowed_types)) {
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        // Delete old profile image if exists
                        if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                            unlink($user['profile_image']);
                        }
                    } else {
                        $error_message = 'การอัปโหลดรูปภาพล้มเหลว';
                    }
                } else {
                    $error_message = 'รูปแบบไฟล์ไม่ถูกต้อง กรุณาใช้ JPEG, PNG หรือ GIF เท่านั้น';
                }
            }
        }

        // Update profile information
        if (empty($error_message)) {
            try {
                // Validate faculty and department
                $faculty_id = !empty($_POST['faculty_id']) ? $_POST['faculty_id'] : null;
                $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;

                // Validate faculty exists
                if ($faculty_id !== null) {
                    $faculty_check_sql = "SELECT COUNT(*) FROM faculty WHERE id = ?";
                    $faculty_check_stmt = $conn->prepare($faculty_check_sql);
                    $faculty_check_stmt->execute([$faculty_id]);
                    $faculty_exists = $faculty_check_stmt->fetchColumn();

                    if (!$faculty_exists) {
                        $faculty_id = null; // Reset to null if faculty doesn't exist
                    }
                }

                // Validate department exists and belongs to the selected faculty
                if ($department_id !== null) {
                    $dept_check_sql = "SELECT COUNT(*) FROM department WHERE department_id = ? " . 
                                      ($faculty_id !== null ? "AND faculty_id = ?" : "");
                    $dept_check_stmt = $conn->prepare($dept_check_sql);
                    
                    if ($faculty_id !== null) {
                        $dept_check_stmt->execute([$department_id, $faculty_id]);
                    } else {
                        $dept_check_stmt->execute([$department_id]);
                    }
                    $dept_exists = $dept_check_stmt->fetchColumn();

                    if (!$dept_exists) {
                        $department_id = null; // Reset to null if department doesn't exist or doesn't match faculty
                    }
                }

                // Prepare update query
                $update_sql = "UPDATE user_profiles 
                               SET first_name = :first_name, 
                                   last_name = :last_name, 
                                   thai_first_name = :thai_first_name, 
                                   thai_last_name = :thai_last_name, 
                                   phone = :phone, 
                                   address = :address, 
                                   faculty_id = :faculty_id, 
                                   department_id = :department_id" . 
                               (!empty($file_name) ? ", profile_image = :profile_image" : "") . "
                               WHERE id_account = :id_account";

                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(':first_name', $_POST['first_name']);
                $update_stmt->bindParam(':last_name', $_POST['last_name']);
                $update_stmt->bindParam(':thai_first_name', $_POST['thai_first_name']);
                $update_stmt->bindParam(':thai_last_name', $_POST['thai_last_name']);
                $update_stmt->bindParam(':phone', $_POST['phone']);
                $update_stmt->bindParam(':address', $_POST['address']);
                
                // Bind faculty and department with null check
                if ($faculty_id !== null) {
                    $update_stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
                } else {
                    $update_stmt->bindValue(':faculty_id', null, PDO::PARAM_NULL);
                }

                if ($department_id !== null) {
                    $update_stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
                } else {
                    $update_stmt->bindValue(':department_id', null, PDO::PARAM_NULL);
                }

                $update_stmt->bindParam(':id_account', $user['id_account'], PDO::PARAM_INT);

                if (!empty($file_name)) {
                    $profile_image_path = $upload_path;
                    $update_stmt->bindParam(':profile_image', $profile_image_path);
                }

                $update_stmt->execute();
                
                // Add a warning if faculty or department were reset
                if ($faculty_id === null || $department_id === null) {
                    $success_message .= " (คณะหรือภาควิชาที่เลือกไม่ถูกต้อง จึงถูกรีเซ็ต)";
                }

                // Log the profile update
                $log_sql = "INSERT INTO logs (id_account, action, details, ip_address) 
                            VALUES (?, 'profile_update', 'User updated profile information', ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->execute([$user['id_account'], $_SERVER['REMOTE_ADDR']]);

                $success_message = 'อัปเดตข้อมูลส่วนตัวสำเร็จ';

                // Refresh user data
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(1, $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error_message = 'การอัปเดตข้อมูลล้มเหลว: ' . $e->getMessage();
            }
        }
    }

    // Fetch departments based on selected faculty
    $departments = [];
    if (!empty($user['faculty_id'])) {
        $dept_sql = "SELECT department_id, department_name, thai_department_name 
                     FROM department 
                     WHERE faculty_id = ? 
                     ORDER BY department_name";
        $dept_stmt = $conn->prepare($dept_sql);
        $dept_stmt->bindParam(1, $user['faculty_id'], PDO::PARAM_INT);
        $dept_stmt->execute();
        $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าบัญชี - Suan Dusit University</title>
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

        .profile-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .profile-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .profile-container h4 {
            color: #3871c1;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }

        .profile-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #3871c1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .profile-image:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            transform: scale(1.02);
        }

        .profile-image-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: #3871c1;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .profile-image-upload:hover {
            background-color: #2a5ea8;
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .form-control:focus, .form-select:focus {
            border-color: #3871c1;
            box-shadow: 0 0 0 0.25rem rgba(56, 113, 193, 0.25);
        }

        .btn-primary {
            background-color: #3871c1;
            border-color: #3871c1;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #2a5ea8;
            border-color: #2a5ea8;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
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
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="majors.php"><i class="fas fa-graduation-cap"></i> สาขา/หลักสูตร</a>
        <a href="แผนการเรียน.php"><i class="fas fa-calendar-check"></i> แผนการเรียน</a>
        <a href="schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php" class="active"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ตั้งค่าบัญชี</div>
            <div class="user-info">
                <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://randomuser.me/api/portraits/men/1.jpg'; ?>" alt="User Profile">
                <div>
                    <strong><?php echo htmlspecialchars(($user['thai_first_name'] ? $user['thai_first_name'] . ' ' . $user['thai_last_name'] : $_SESSION['username'])); ?></strong>
                    <p class="m-0">ฝ่ายวิชาการ</p>
                </div>
            </div>
        </div>

        <!-- Section Header -->
        <div class="section-header mb-4">
            <h2 class="mb-0"><i class="fas fa-user-cog me-2"></i> ตั้งค่าบัญชีผู้ใช้งาน</h2>
        </div>

        <div class="container-fluid">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="profile-container text-center">
                        <form action="" method="POST" enctype="multipart/form-data" id="profile-image-form">
                            <div class="profile-image-container">
                                <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://randomuser.me/api/portraits/men/1.jpg'; ?>" 
                                     alt="Profile Image" class="profile-image" id="profile-image-preview">
                                <label for="profile_image" class="profile-image-upload">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" name="profile_image" id="profile_image" 
                                           accept="image/jpeg,image/png,image/gif" style="display:none;">
                                </label>
                            </div>
                        </form>
                        <h3><?php echo htmlspecialchars(($user['thai_first_name'] ? $user['thai_first_name'] . ' ' . $user['thai_last_name'] : $_SESSION['username'])); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($user['Role_account']); ?></p>
                    </div>

                    <div class="profile-container mt-3">
                        <h4 class="mb-3"><i class="fas fa-lock me-2"></i> การรักษาความปลอดภัย</h4>
                        <a href="change_password.php" class="btn btn-primary w-100">
                            <i class="fas fa-key me-2"></i> เปลี่ยนรหัสผ่าน
                        </a>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="profile-container">
                        <h4 class="mb-3"><i class="fas fa-user-edit me-2"></i> แก้ไขข้อมูลส่วนตัว</h4>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="first_name" class="form-label">ชื่อ (ภาษาอังกฤษ)</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name'] ?: ''); ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="last_name" class="form-label">นามสกุล (ภาษาอังกฤษ)</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name'] ?: ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="thai_first_name" class="form-label">ชื่อ (ภาษาไทย)</label>
                                    <input type="text" class="form-control" id="thai_first_name" name="thai_first_name" 
                                           value="<?php echo htmlspecialchars($user['thai_first_name'] ?: ''); ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="thai_last_name" class="form-label">นามสกุล (ภาษาไทย)</label>
                                    <input type="text" class="form-control" id="thai_last_name" name="thai_last_name" 
                                           value="<?php echo htmlspecialchars($user['thai_last_name'] ?: ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="address" class="form-label">ที่อยู่</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?: ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="faculty_id" class="form-label">คณะ</label>
                                    <select class="form-select" id="faculty_id" name="faculty_id">
                                        <option value="">เลือกคณะ</option>
                                        <?php foreach ($faculties as $faculty): ?>
                                            <option value="<?php echo $faculty['id']; ?>" 
                                                <?php echo ($user['faculty_id'] == $faculty['id'] ? 'selected' : ''); ?>>
                                                <?php echo htmlspecialchars($faculty['thai_faculty_name'] ?: $faculty['faculty_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="department_id" class="form-label">ภาควิชา</label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">เลือกภาควิชา</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?php echo $department['department_id']; ?>" 
                                                <?php echo ($user['department_id'] == $department['department_id'] ? 'selected' : ''); ?>>
                                                <?php echo htmlspecialchars($department['thai_department_name'] ?: $department['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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

        // Add container hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const containers = document.querySelectorAll('.profile-container');
            
            containers.forEach(container => {
                container.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.1)';
                });
                
                container.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.08)';
                });
            });
        });

        // Profile image preview and upload
        document.getElementById('profile_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('profile-image-preview');
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                // Submit form to upload image
                document.getElementById('profile-image-form').submit();
            }

            reader.readAsDataURL(file);
        });

        // Dynamic department dropdown based on faculty
        document.getElementById('faculty_id').addEventListener('change', function() {
            const facultyId = this.value;
            const departmentSelect = document.getElementById('department_id');
            
            // Reset department dropdown
            departmentSelect.innerHTML = '<option value="">เลือกภาควิชา</option>';

            // If a faculty is selected, fetch departments
            if (facultyId) {
                fetch(`get_departments.php?faculty_id=${facultyId}`)
                    .then(response => response.json())
                    .then(departments => {
                        departments.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.department_id;
                            option.textContent = dept.thai_department_name || dept.department_name;
                            departmentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }
        });

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>