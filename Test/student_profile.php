<?php
// Initialize session
session_start();

// Check if user is logged in and has student role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // Clear session and redirect to login
    session_unset();
    session_destroy();
    header("Location: index.php?error=โปรดเข้าสู่ระบบก่อนใช้งาน");
    exit();
}

// Include database connection
require_once 'db_connect.php';

try {
    // Get student information including profile details
    $student_sql = "SELECT a.username_account, a.email_account, 
                   s.student_code, s.entry_year, s.study_year, s.status, s.academic_status,
                   p.first_name, p.last_name, p.thai_first_name, p.thai_last_name, 
                   p.phone, p.address, p.profile_image,
                   f.faculty_name, f.thai_faculty_name,
                   d.department_name, d.thai_department_name,
                   m.major_name, m.thai_major_name,
                   c.Curriculum_Name
                   FROM account a
                   JOIN student_details s ON a.id_account = s.id_account
                   JOIN user_profiles p ON a.id_account = p.id_account
                   LEFT JOIN faculty f ON p.faculty_id = f.id
                   LEFT JOIN department d ON p.department_id = d.department_id
                   LEFT JOIN major m ON s.major_id = m.major_id
                   LEFT JOIN curriculum c ON s.Curriculum_ID = c.Curriculum_ID
                   WHERE a.id_account = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(1, $_SESSION['user_id']);
    $student_stmt->execute();
    $student = $student_stmt->fetch();
    
    // Get registered courses for the student
    $courses_sql = "SELECT cr.Registration_ID, cr.Course_Code, cr.status, cr.Grade, cr.Credits, 
                  c.Course_Name, cr.Semester, cr.Academic_Year
                  FROM course_registration cr
                  JOIN course c ON cr.Course_Code = c.Course_Code
                  WHERE cr.Student_ID = ? 
                  ORDER BY cr.Academic_Year DESC, cr.Semester DESC, cr.Course_Code";
    $courses_stmt = $conn->prepare($courses_sql);
    $courses_stmt->bindParam(1, $student['student_code']);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll();
    
    // Get TOEIC scores
    $toeic_sql = "SELECT * FROM toeic WHERE Student_ID = ? ORDER BY Test_Date DESC";
    $toeic_stmt = $conn->prepare($toeic_sql);
    $toeic_stmt->bindParam(1, $student['student_code']);
    $toeic_stmt->execute();
    $toeic_result = $toeic_stmt->fetch();
    
    // Calculate total credits and GPA
    $total_credits = 0;
    $total_grade_points = 0;
    $gpa = 0;
    
    foreach ($courses as $course) {
        if ($course['Grade'] !== null && $course['status'] == 'registered') {
            $total_credits += $course['Credits'];
            $total_grade_points += ($course['Grade'] * $course['Credits']);
        }
    }
    
    if ($total_credits > 0) {
        $gpa = $total_grade_points / $total_credits;
    }
    
    // Get courses not registered yet (from their study plan)
    $pending_courses_sql = "SELECT mc.Course_Code, c.Course_Name, c.Credits, 
                          mc.semester_number, mc.study_year
                          FROM major_courses mc
                          JOIN course c ON mc.Course_Code = c.Course_Code
                          WHERE mc.major_id = ? 
                          AND NOT EXISTS (
                              SELECT 1 FROM course_registration cr 
                              WHERE cr.Student_ID = ? AND cr.Course_Code = mc.Course_Code
                          )
                          ORDER BY mc.study_year, mc.semester_number";
    $pending_courses_stmt = $conn->prepare($pending_courses_sql);
    $pending_courses_stmt->bindParam(1, $student['major_id']);
    $pending_courses_stmt->bindParam(2, $student['student_code']);
    $pending_courses_stmt->execute();
    $pending_courses = $pending_courses_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}

// Process profile image upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    // Handle file upload
    $target_dir = "uploads/profiles/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = $_SESSION['user_id'] . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if uploaded file is an image
    $valid_types = array('jpg', 'jpeg', 'png', 'gif');
    if (in_array($file_extension, $valid_types)) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // Update profile image in database
            try {
                $update_sql = "UPDATE user_profiles SET profile_image = ? WHERE id_account = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(1, $target_file);
                $update_stmt->bindParam(2, $_SESSION['user_id']);
                $update_stmt->execute();
                
                // Redirect to refresh page with new image
                header("Location: student_profile.php?success=อัพเดทรูปภาพเรียบร้อยแล้ว");
                exit();
            } catch (PDOException $e) {
                $error_message = "การอัพเดทรูปภาพล้มเหลว: " . $e->getMessage();
            }
        } else {
            $error_message = "ไม่สามารถอัพโหลดไฟล์ได้";
        }
    } else {
        $error_message = "กรุณาอัพโหลดไฟล์รูปภาพ (jpg, jpeg, png, gif) เท่านั้น";
    }
}

// Process TOEIC score upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['toeic_file']) && isset($_POST['toeic_score'])) {
    $toeic_score = $_POST['toeic_score'];
    
    // Handle file upload
    $target_dir = "uploads/toeic/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["toeic_file"]["name"], PATHINFO_EXTENSION));
    $new_filename = $student['student_code'] . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if uploaded file is an image
    $valid_types = array('jpg', 'jpeg', 'png', 'pdf');
    if (in_array($file_extension, $valid_types)) {
        if (move_uploaded_file($_FILES["toeic_file"]["tmp_name"], $target_file)) {
            // Insert or update TOEIC score in database
            try {
                // Check if record exists
                $check_sql = "SELECT COUNT(*) as count FROM toeic WHERE Student_ID = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(1, $student['student_code']);
                $check_stmt->execute();
                $record_exists = $check_stmt->fetch()['count'] > 0;
                
                if ($record_exists) {
                    // Update existing record
                    $update_sql = "UPDATE toeic SET TOEIC_Score = ?, Test_Date = CURRENT_DATE(), 
                                  Registration_Status = 'completed' WHERE Student_ID = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(1, $toeic_score);
                    $update_stmt->bindParam(2, $student['student_code']);
                    $update_stmt->execute();
                } else {
                    // Insert new record
                    $insert_sql = "INSERT INTO toeic (Student_ID, Pre_Test_Score, TOEIC_Score, Registration_Status, Test_Date) 
                                 VALUES (?, 0, ?, 'completed', CURRENT_DATE())";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bindParam(1, $student['student_code']);
                    $insert_stmt->bindParam(2, $toeic_score);
                    $insert_stmt->execute();
                }
                
                // Redirect to refresh page
                header("Location: student_profile.php?success=บันทึกข้อมูล TOEIC เรียบร้อยแล้ว");
                exit();
            } catch (PDOException $e) {
                $error_message = "การบันทึกข้อมูล TOEIC ล้มเหลว: " . $e->getMessage();
            }
        } else {
            $error_message = "ไม่สามารถอัพโหลดไฟล์ได้";
        }
    } else {
        $error_message = "กรุณาอัพโหลดไฟล์รูปภาพหรือ PDF (jpg, jpeg, png, pdf) เท่านั้น";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัวนักศึกษา - Suan Dusit University</title>
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
            padding-top: 20px;
            padding-left: 15px;
            transition: all 0.3s;
        }

        .sidebar.hidden {
            width: 0;
            overflow: hidden;
            padding: 0;
        }

        .sidebar .logo-container {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 20px;
        }

        .sidebar img {
            max-width: 50px;
            margin-right: 10px;
        }

        .sidebar h3 {
            font-size: 1.2rem;
            color: #00c6ff;
        }

        .sidebar a {
            padding: 15px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
        }

        .sidebar a:hover, .sidebar a.active {
            background: #007bff;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .content {
            margin-left: 260px;
            padding: 20px;
            transition: margin-left 0.3s;
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
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .topbar .menu-toggle {
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            margin-right: 15px;
        }

        .topbar .dashboard-title {
            font-size: 24px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 8px;
        }

        .topbar .search-container {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .topbar .search-container input {
            border-radius: 20px;
            padding: 5px 15px;
            margin-right: 10px;
            border: none;
        }

        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .table-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .table-container h2 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #3871c1;
        }

        .section-header {
            color: #ffffff;
            border: 2px solid #1377db;
            padding: 10px;
            background-color: #1939c5;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: bold;
            border-radius: 5px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #3871c1;
        }

        .table th {
            background-color: #f1f1f1;
        }

        .advisor-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .summary-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .toeic-preview {
            max-width: 100%;
            max-height: 250px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
        <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> หน้าแรก</a>
        <a href="student_profile.php" class="active"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</a>
        <a href="class_schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="enrollment_status.php"><i class="fas fa-tasks"></i> ติดตามการลงทะเบียน</a>
        <a href="course_registration.php"><i class="fas fa-book"></i> ลงทะเบียนรายวิชา</a>
        <a href="my_grades.php"><i class="fas fa-chart-line"></i> ผลการเรียน</a>
        <a href="toeic_results.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ข้อมูลส่วนตัวนักศึกษา</div>
            <div class="search-container ms-auto">
                <input type="text" class="form-control" placeholder="ค้นหาที่นี่" id="search-input">
                <button class="btn btn-light" id="search-btn"><i class="fas fa-search"></i></button>
            </div>
            <div class="user-info">
                <?php if (!empty($student['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="User">
                <?php else: ?>
                    <img src="https://via.placeholder.com/40" alt="User">
                <?php endif; ?>
                <div>
                    <strong>
                        <?php 
                        if (!empty($student['thai_first_name']) && !empty($student['thai_last_name'])) {
                            echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']);
                        } elseif (!empty($student['first_name']) && !empty($student['last_name'])) {
                            echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                        } else {
                            echo htmlspecialchars($student['username_account']);
                        }
                        ?>
                    </strong>
                    <p class="m-0">นักศึกษา</p>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 class="mb-0"><i class="fas fa-user me-2"></i> ข้อมูลส่วนตัวนักศึกษา</h2>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <!-- ข้อมูลนักศึกษาและอาจารย์ที่ปรึกษา -->
        <div class="table-container">
            <div class="row">
                <div class="col-md-3 text-center">
                    <?php if (!empty($student['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="รูปนักศึกษา" class="profile-image mb-3">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/150" alt="รูปนักศึกษา" class="profile-image mb-3">
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">อัพโหลดรูปโปรไฟล์</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i> อัพโหลด</button>
                    </form>
                </div>
                <div class="col-md-5">
                    <h3>
                        <?php 
                        if (!empty($student['thai_first_name']) && !empty($student['thai_last_name'])) {
                            echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']);
                        } elseif (!empty($student['first_name']) && !empty($student['last_name'])) {
                            echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                        } else {
                            echo htmlspecialchars($student['username_account']);
                        }
                        ?>
                    </h3>
                    <p><strong>รหัสนักศึกษา:</strong> <?php echo htmlspecialchars($student['student_code']); ?></p>
                    <p><strong>คณะ:</strong> <?php echo htmlspecialchars($student['thai_faculty_name'] ?? $student['faculty_name'] ?? 'ไม่ระบุ'); ?></p>
                    <p><strong>สาขา:</strong> <?php echo htmlspecialchars($student['thai_major_name'] ?? $student['major_name'] ?? 'ไม่ระบุ'); ?></p>
                    <p><strong>ชั้นปี:</strong> <?php echo htmlspecialchars($student['study_year']); ?></p>
                    <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($student['email_account']); ?></p>
                    <p><strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'ไม่ระบุ'); ?></p>
                </div>
                <div class="col-md-4">
                    <div class="advisor-section">
                        <h4><i class="fas fa-chalkboard-teacher me-2"></i> อาจารย์ที่ปรึกษา</h4>
                        <p><strong>ชื่อ:</strong> ดร. <?php echo htmlspecialchars($student['department_name'] ?? 'ที่ปรึกษา'); ?></p>
                        <p><strong>สาขา:</strong> <?php echo htmlspecialchars($student['thai_department_name'] ?? 'ไม่ระบุ'); ?></p>
                        <p><strong>อีเมล:</strong> advisor@example.com</p>
                        <p><strong>ติดต่อ:</strong> 02-244-5555</p>
                        <p><strong>ห้องพัก:</strong> อาคาร 2 ชั้น 3 ห้อง 305</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- คะแนน TOEIC -->
        <div class="table-container">
            <h2><i class="fas fa-language me-2"></i> ผลสอบ TOEIC</h2>
            <div class="row">
                <div class="col-md-6">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="toeic_score" class="form-label">คะแนน TOEIC</label>
                            <input type="number" class="form-control" id="toeic_score" name="toeic_score" 
                                min="0" max="990" value="<?php echo $toeic_result ? $toeic_result['TOEIC_Score'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="toeic_file" class="form-label">อัพโหลดเอกสารผลสอบ</label>
                            <input type="file" class="form-control" id="toeic_file" name="toeic_file" accept="image/*,.pdf">
                        </div>
                        <button type="submit" class="btn btn-info text-white">
                            <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">สถานะการสอบ TOEIC</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($toeic_result): ?>
                                <p><strong>คะแนนล่าสุด:</strong> <?php echo htmlspecialchars($toeic_result['TOEIC_Score']); ?>/990</p>
                                <p><strong>วันที่สอบ:</strong> <?php echo date('d/m/Y', strtotime($toeic_result['Test_Date'])); ?></p>
                                <p><strong>สถานะ:</strong> 
                                    <?php if ($toeic_result['Registration_Status'] == 'completed'): ?>
                                        <span class="badge bg-success">สอบเรียบร้อยแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">รอดำเนินการ</span>
                                    <?php endif; ?>
                                </p>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo ($toeic_result['TOEIC_Score'] / 990) * 100; ?>%" 
                                         aria-valuenow="<?php echo $toeic_result['TOEIC_Score']; ?>" 
                                         aria-valuemin="0" aria-valuemax="990">
                                        <?php echo $toeic_result['TOEIC_Score']; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> ยังไม่มีข้อมูลการสอบ TOEIC
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- วิชาที่ลงทะเบียนเรียน -->
        <div class="table-container">
            <h2><i class="fas fa-book me-2"></i> วิชาที่ลงทะเบียนเรียน</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="courses-table">
                    <thead>
                        <tr class="table-primary">
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>หน่วยกิต</th>
                            <th>ภาคเรียน</th>
                            <th>สถานะ</th>
                            <th>เกรด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Credits']); ?></td>
                                    <td>ภาคเรียนที่ <?php echo htmlspecialchars($course['Semester']); ?> 
                                        ปีการศึกษา <?php echo htmlspecialchars($course['Academic_Year']); ?></td>
                                    <td>
                                        <?php if ($course['status'] == 'registered'): ?>
                                            <span class="badge bg-success">ลงทะเบียนเรียน</span>
                                        <?php elseif ($course['status'] == 'withdrawn'): ?>
                                            <span class="badge bg-warning">ถอนรายวิชา</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ยกเลิก</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $course['Grade'] ? number_format($course['Grade'], 2) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">ยังไม่มีวิชาที่ลงทะเบียน</td>
                            </tr>
                            <!-- Sample data -->
                            <tr>
                                <td>CS101</td>
                                <td>การเขียนโปรแกรมเบื้องต้น</td>
                                <td>3</td>
                                <td>ภาคเรียนที่ 1 2023</td>
                                <td><span class="badge bg-success">ลงทะเบียนเรียน</span></td>
                                <td>4.0</td>
                            </tr>
                            <tr>
                                <td>CS202</td>
                                <td>โครงสร้างข้อมูล</td>
                                <td>3</td>
                                <td>ภาคเรียนที่ 2 2023</td>
                                <td><span class="badge bg-success">ลงทะเบียนเรียน</span></td>
                                <td>3.7</td>
                            </tr>
                            <tr>
                                <td>CS303</td>
                                <td>ระบบปฏิบัติการ</td>
                                <td>3</td>
                                <td>ภาคเรียนที่ 1 2024</td>
                                <td><span class="badge bg-warning">รอดำเนินการ</span></td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- วิชาที่ยังไม่ได้ลงทะเบียนเรียน -->
        <div class="table-container">
            <h2><i class="fas fa-book me-2"></i> วิชาที่ยังไม่ได้ลงทะเบียนเรียน</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="pending-courses-table">
                    <thead>
                        <tr class="table-primary">
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>หน่วยกิต</th>
                            <th>ภาคเรียน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_courses) > 0): ?>
                            <?php foreach ($pending_courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Credits']); ?></td>
                                    <td>ภาคเรียนที่ <?php echo htmlspecialchars($course['semester_number']); ?> 
                                        ปีการศึกษาที่ <?php echo htmlspecialchars($course['study_year']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">ไม่มีวิชาที่รอลงทะเบียน</td>
                            </tr>
                            <!-- Sample data -->
                            <tr>
                                <td>CS404</td>
                                <td>การสถาปนาฐานข้อมูล</td>
                                <td>3</td>
                                <td>ภาคเรียนที่ 1 2024</td>
                            </tr>
                            <tr>
                                <td>CS505</td>
                                <td>การคำนวณ</td>
                                <td>3</td>
                                <td>ภาคเรียนที่ 2 2024</td>
                            </tr>
                            <tr>
                                <td>CS606</td>
                                <td>การเขียนซอฟต์แวร์</td>
                                <td>3</td>
                                <td>ภาคเรียนที่ 1 2025</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- สรุปหน่วยกิต -->
        <div class="table-container">
            <h2><i class="fas fa-calculator me-2"></i> สรุปผลการเรียน</h2>
            <div class="summary-section">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>หน่วยกิตทั้งหมดที่ลงทะเบียน:</strong> <?php echo $total_credits; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>เกรดเฉลี่ยรวม:</strong> <?php echo number_format($gpa, 2); ?></p>
                    </div>
                </div>
                <?php if ($gpa > 0): ?>
                <div class="progress mt-3">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo ($gpa / 4) * 100; ?>%" 
                         aria-valuenow="<?php echo $gpa; ?>" 
                         aria-valuemin="0" aria-valuemax="4">
                        <?php echo number_format($gpa, 2); ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <a href="my_grades.php" class="btn btn-primary">
                        <i class="fas fa-chart-line me-2"></i> ดูผลการเรียนทั้งหมด
                    </a>
                    <a href="course_registration.php" class="btn btn-success ms-2">
                        <i class="fas fa-plus-circle me-2"></i> ลงทะเบียนรายวิชา
                    </a>
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
        
        // Search functionality for courses table
        document.getElementById('search-input').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            const courses_table = document.getElementById('courses-table');
            const pending_table = document.getElementById('pending-courses-table');
            
            // Search in registered courses
            if (courses_table) {
                const rows = courses_table.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    const courseId = rows[i].cells[0] ? rows[i].cells[0].textContent.toLowerCase() : '';
                    const courseName = rows[i].cells[1] ? rows[i].cells[1].textContent.toLowerCase() : '';
                    
                    if (courseId.includes(input) || courseName.includes(input)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
            
            // Search in pending courses
            if (pending_table) {
                const rows = pending_table.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    const courseId = rows[i].cells[0] ? rows[i].cells[0].textContent.toLowerCase() : '';
                    const courseName = rows[i].cells[1] ? rows[i].cells[1].textContent.toLowerCase() : '';
                    
                    if (courseId.includes(input) || courseName.includes(input)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
        
        // Search button click
        document.getElementById('search-btn').addEventListener('click', function() {
            const input = document.getElementById('search-input');
            const event = new Event('keyup');
            input.dispatchEvent(event);
        });
        
        // Preview profile image upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const profileImages = document.querySelectorAll('.profile-image');
                    profileImages.forEach(function(img) {
                        img.src = e.target.result;
                    });
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Preview TOEIC document upload
        document.getElementById('toeic_file').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                const fileType = e.target.files[0].type;
                
                if (fileType.startsWith('image/')) {
                    reader.onload = function(e) {
                        let preview = document.querySelector('.toeic-preview');
                        if (!preview) {
                            preview = document.createElement('img');
                            preview.className = 'toeic-preview';
                            document.querySelector('form').appendChild(preview);
                        }
                        preview.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(e.target.files[0]);
                }
            }
        });
    </script>
</body>
</html>