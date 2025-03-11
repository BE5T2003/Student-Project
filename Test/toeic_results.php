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

// Process TOEIC score upload if submitted
// Process TOEIC score upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toeic_score'])) {
    $pre_toeic_score = isset($_POST['pre_toeic_score']) ? intval($_POST['pre_toeic_score']) : null;
    $post_training1_score = isset($_POST['post_training1_score']) ? intval($_POST['post_training1_score']) : null;
    $post_training2_score = isset($_POST['post_training2_score']) ? intval($_POST['post_training2_score']) : null;
    $toeic_score = intval($_POST['toeic_score']);
    
    // Function to handle file upload
    function uploadFile($file, $student_id, $prefix) {
        if (isset($file) && $file['error'] == 0) {
            $target_dir = "uploads/toeic/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $new_filename = $student_id . "_{$prefix}_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            $valid_types = array('jpg', 'jpeg', 'png', 'pdf');
            if (in_array($file_extension, $valid_types)) {
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    return $target_file;
                }
            }
        }
        return null;
    }
    
    try {
        // Get student ID from session user
        $student_query = "SELECT student_code FROM student_details WHERE id_account = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bindParam(1, $_SESSION['user_id']);
        $student_stmt->execute();
        $student_result = $student_stmt->fetch();
        
        if ($student_result) {
            $student_id = $student_result['student_code'];
            
            // Upload files
            $pre_toeic_document = uploadFile($_FILES['pre_toeic_file'], $student_id, 'pre_toeic');
            $post_training1_document = uploadFile($_FILES['post_training1_file'], $student_id, 'post_training1');
            $post_training2_document = uploadFile($_FILES['post_training2_file'], $student_id, 'post_training2');
            $toeic_document = uploadFile($_FILES['toeic_file'], $student_id, 'toeic');
            
            // Determine course requirements based on score
            $required_courses = 0;
            if ($toeic_score < 350) {
                $required_courses = 2;
            } elseif ($toeic_score < 500) {
                $required_courses = 1;
            } else {
                $required_courses = 0;
            }
            
            // Check if record exists
            $check_sql = "SELECT COUNT(*) as count FROM toeic WHERE Student_ID = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(1, $student_id);
            $check_stmt->execute();
            $record_exists = $check_stmt->fetch()['count'] > 0;
            
            if ($record_exists) {
                // Update existing record
                $update_sql = "UPDATE toeic SET 
                              Pre_Test_Score = ?,
                              Pre_Test_Document = ?,
                              Post_Training1_Score = ?,
                              Post_Training1_Document = ?,
                              Post_Training2_Score = ?,
                              Post_Training2_Document = ?,
                              TOEIC_Score = ?, 
                              Test_Date = CURRENT_DATE(), 
                              Registration_Status = 'completed',
                              Required_Courses = ?";
                              
                if ($toeic_document) {
                    $update_sql .= ", Document_Path = ?";
                }
                
                $update_sql .= " WHERE Student_ID = ?";
                
                $update_stmt = $conn->prepare($update_sql);
                $param_index = 1;
                $update_stmt->bindParam($param_index++, $pre_toeic_score);
                $update_stmt->bindParam($param_index++, $pre_toeic_document);
                $update_stmt->bindParam($param_index++, $post_training1_score);
                $update_stmt->bindParam($param_index++, $post_training1_document);
                $update_stmt->bindParam($param_index++, $post_training2_score);
                $update_stmt->bindParam($param_index++, $post_training2_document);
                $update_stmt->bindParam($param_index++, $toeic_score);
                $update_stmt->bindParam($param_index++, $required_courses);
                
                if ($toeic_document) {
                    $update_stmt->bindParam($param_index++, $toeic_document);
                }
                
                $update_stmt->bindParam($param_index, $student_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "อัพเดทคะแนน TOEIC เรียบร้อยแล้ว";
                } else {
                    $error_message = "ไม่สามารถอัพเดทคะแนน TOEIC ได้";
                }
            } else {
                // Insert new record
                $insert_sql = "INSERT INTO toeic 
                              (Student_ID, Pre_Test_Score, Pre_Test_Document, 
                              Post_Training1_Score, Post_Training1_Document,
                              Post_Training2_Score, Post_Training2_Document,
                              TOEIC_Score, Registration_Status, Test_Date, Required_Courses";
                
                if ($toeic_document) {
                    $insert_sql .= ", Document_Path";
                }
                
                $insert_sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', CURRENT_DATE(), ?";
                
                if ($toeic_document) {
                    $insert_sql .= ", ?";
                }
                
                $insert_sql .= ")";
                
                $insert_stmt = $conn->prepare($insert_sql);
                $param_index = 1;
                $insert_stmt->bindParam($param_index++, $student_id);
                $insert_stmt->bindParam($param_index++, $pre_toeic_score);
                $insert_stmt->bindParam($param_index++, $pre_toeic_document);
                $insert_stmt->bindParam($param_index++, $post_training1_score);
                $insert_stmt->bindParam($param_index++, $post_training1_document);
                $insert_stmt->bindParam($param_index++, $post_training2_score);
                $insert_stmt->bindParam($param_index++, $post_training2_document);
                $insert_stmt->bindParam($param_index++, $toeic_score);
                $insert_stmt->bindParam($param_index++, $required_courses);
                
                if ($toeic_document) {
                    $insert_stmt->bindParam($param_index, $toeic_document);
                }
                
                if ($insert_stmt->execute()) {
                    $success_message = "บันทึกคะแนน TOEIC เรียบร้อยแล้ว";
                } else {
                    $error_message = "ไม่สามารถบันทึกคะแนน TOEIC ได้";
                }
            }
        } else {
            $error_message = "ไม่พบข้อมูลนักศึกษา";
        }
    } catch (PDOException $e) {
        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
try {
    // Get student information
    $student_query = "SELECT s.student_code, s.study_year, 
                    p.first_name, p.last_name, p.thai_first_name, p.thai_last_name,
                    m.major_name, m.thai_major_name
                    FROM student_details s
                    JOIN account a ON s.id_account = a.id_account
                    JOIN user_profiles p ON a.id_account = p.id_account
                    LEFT JOIN major m ON s.major_id = m.major_id
                    WHERE a.id_account = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bindParam(1, $_SESSION['user_id']);
    $student_stmt->execute();
    $student = $student_stmt->fetch();
    
    // Get TOEIC results
    $toeic_query = "SELECT * FROM toeic WHERE Student_ID = ? ORDER BY Test_Date DESC";
    $toeic_stmt = $conn->prepare($toeic_query);
    $toeic_stmt->bindParam(1, $student['student_code']);
    $toeic_stmt->execute();
    $toeic_results = $toeic_stmt->fetchAll();
    
    // Get latest TOEIC score
    $latest_toeic = count($toeic_results) > 0 ? $toeic_results[0] : null;
    
    // Get required TOEIC training courses
    $required_courses = [];
    if ($latest_toeic) {
        $required_courses_count = $latest_toeic['Required_Courses'];
        
        if ($required_courses_count > 0) {
            $courses_query = "SELECT c.Course_Code, c.Course_Name, cs.section_id, cs.section_number, 
                          cs.instructor_name, cs.max_students, cs.current_students, cs.status
                          FROM course c
                          JOIN course_sections cs ON c.Course_Code = cs.Course_Code
                          JOIN semesters s ON cs.semester_id = s.semester_id
                          WHERE c.Course_Name LIKE '%English%' OR c.Course_Name LIKE '%TOEIC%'
                          AND s.is_current = 1
                          AND cs.status = 'active'
                          AND cs.current_students < cs.max_students
                          LIMIT ?";
            $courses_stmt = $conn->prepare($courses_query);
            $courses_stmt->bindParam(1, $required_courses_count, PDO::PARAM_INT);
            $courses_stmt->execute();
            $required_courses = $courses_stmt->fetchAll();
        }
    }
    
    // Get registered English courses
    $registered_courses_query = "SELECT cr.Registration_ID, cr.Course_Code, cr.status, 
                               c.Course_Name, cs.section_number, cs.instructor_name
                               FROM course_registration cr
                               JOIN course c ON cr.Course_Code = c.Course_Code
                               LEFT JOIN course_sections cs ON cr.section_id = cs.section_id
                               WHERE cr.Student_ID = ? 
                               AND (c.Course_Name LIKE '%English%' OR c.Course_Name LIKE '%TOEIC%')
                               ORDER BY cr.Academic_Year DESC, cr.Semester DESC";
    $registered_courses_stmt = $conn->prepare($registered_courses_query);
    $registered_courses_stmt->bindParam(1, $student['student_code']);
    $registered_courses_stmt->execute();
    $registered_courses = $registered_courses_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "การดึงข้อมูลล้มเหลว: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลสอบ TOEIC - Suan Dusit University</title>
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
            z-index: 1000;
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

        .table th {
            background-color: #f1f1f1;
        }

        .progress {
            height: 25px;
        }

        .progress-bar {
            font-weight: bold;
        }

        .stats-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: 100%;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .bg-primary-light {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .bg-success-light {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .bg-info-light {
            background-color: rgba(13, 202, 240, 0.1);
            color: #0dcaf0;
        }

        .toeic-document-preview {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 15px;
        }

        .requirement-alert {
            border-left: 5px solid;
            padding-left: 15px;
        }

        .requirement-alert.no-courses {
            border-color: #198754;
        }

        .requirement-alert.one-course {
            border-color: #ffc107;
        }

        .requirement-alert.two-courses {
            border-color: #dc3545;
        }

        .score-range {
            position: relative;
            height: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            margin: 20px 0;
            overflow: hidden;
        }

        .score-range .range {
            position: absolute;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .score-range .range-low {
            background: #dc3545;
            width: 35%;
            left: 0;
        }

        .score-range .range-mid {
            background: #ffc107;
            width: 15%;
            left: 35%;
        }

        .score-range .range-high {
            background: #198754;
            width: 50%;
            left: 50%;
        }

        .score-marker {
            position: absolute;
            top: -15px;
            width: 3px;
            height: 45px;
            background: #000;
            z-index: 10;
        }

        .score-marker::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: -5px;
            width: 13px;
            height: 13px;
            background: #000;
            border-radius: 50%;
        }

        .score-value {
            position: absolute;
            top: -35px;
            transform: translateX(-50%);
            background: #000;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
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
        <a href="student_profile.php"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</a>
        <a href="class_schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="enrollment_status.php"><i class="fas fa-tasks"></i> ติดตามการลงทะเบียน</a>
        <a href="course_registration.php"><i class="fas fa-book"></i> ลงทะเบียนรายวิชา</a>
        <a href="my_grades.php"><i class="fas fa-chart-line"></i> ผลการเรียน</a>
        <a href="toeic_results.php" class="active"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ผลสอบ TOEIC</div>
            <div class="user-info">
                <img src="https://via.placeholder.com/40" alt="User">
                <div>
                    <strong>
                        <?php 
                        if (!empty($student['thai_first_name']) && !empty($student['thai_last_name'])) {
                            echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']);
                        } elseif (!empty($student['first_name']) && !empty($student['last_name'])) {
                            echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                        } else {
                            echo "นักศึกษา";
                        }
                        ?>
                    </strong>
                    <p class="m-0"><?php echo htmlspecialchars($student['student_code']); ?></p>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 class="mb-0"><i class="fas fa-language me-2"></i> ผลการสอบ TOEIC</h2>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- TOEIC Score Overview -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-primary-light">
                                <i class="fas fa-award"></i>
                            </div>
                            <h3>
                                <?php if ($latest_toeic): ?>
                                    <?php echo $latest_toeic['TOEIC_Score']; ?> / 990
                                <?php else: ?>
                                    - / 990
                                <?php endif; ?>
                            </h3>
                            <p>คะแนน TOEIC ล่าสุด</p>
                        </div>
                        <div>
                            <?php if ($latest_toeic): ?>
                                <div class="badge bg-primary p-2">
                                    <?php echo date('d/m/Y', strtotime($latest_toeic['Test_Date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($latest_toeic): ?>
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" 
                                style="width: <?php echo ($latest_toeic['TOEIC_Score'] / 990) * 100; ?>%" 
                                aria-valuenow="<?php echo $latest_toeic['TOEIC_Score']; ?>" 
                                aria-valuemin="0" aria-valuemax="990">
                                <?php echo $latest_toeic['TOEIC_Score']; ?>
                            </div>
                        </div>
                        
                        <!-- Score range visualization -->
                        <div class="score-range mt-4">
                            <div class="range range-low">ต่ำกว่า 350</div>
                            <div class="range range-mid">350-499</div>
                            <div class="range range-high">500 ขึ้นไป</div>
                            
                            <?php
                            $score = $latest_toeic['TOEIC_Score'];
                            $position = min(($score / 990) * 100, 100);
                            ?>
                            
                            <div class="score-marker" style="left: <?php echo $position; ?>%">
                                <div class="score-value"><?php echo $score; ?></div>
                            </div>
                        </div>
                        
                        <!-- Course Requirements Alert -->
                        <div class="mt-4">
                            <?php if ($latest_toeic['Required_Courses'] == 0): ?>
                                <div class="alert alert-success requirement-alert no-courses">
                                    <h5><i class="fas fa-check-circle me-2"></i> ไม่ต้องลงทะเบียน course เพิ่มเติม</h5>
                                    <p>คะแนน TOEIC ของคุณสูงกว่า 500 คะแนน ไม่จำเป็นต้องลงทะเบียนเรียน course ภาษาอังกฤษเพิ่มเติม</p>
                                </div>
                            <?php elseif ($latest_toeic['Required_Courses'] == 1): ?>
                                <div class="alert alert-warning requirement-alert one-course">
                                    <h5><i class="fas fa-exclamation-triangle me-2"></i> ต้องลงทะเบียน 1 course</h5>
                                    <p>คะแนน TOEIC ของคุณอยู่ระหว่าง 350-499 คะแนน ต้องลงทะเบียนเรียน course ภาษาอังกฤษเพิ่มเติม 1 course</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger requirement-alert two-courses">
                                    <h5><i class="fas fa-exclamation-circle me-2"></i> ต้องลงทะเบียน 2 course</h5>
                                    <p>คะแนน TOEIC ของคุณต่ำกว่า 350 คะแนน ต้องลงทะเบียนเรียน course ภาษาอังกฤษเพิ่มเติม 2 course</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i> ยังไม่มีข้อมูลการสอบ TOEIC กรุณาบันทึกผลการสอบ
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="stats-card">
                    <h4><i class="fas fa-info-circle me-2"></i> คำอธิบายเกณฑ์คะแนน TOEIC</h4>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">ข้อกำหนดการลงทะเบียนตามคะแนน TOEIC</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-danger">คะแนนต่ำกว่า 350</strong>
                                            <p class="mb-0">ต้องลงทะเบียน course ภาษาอังกฤษเพิ่มเติม 2 course</p>
                                        </div>
                                        <span class="badge bg-danger rounded-pill">2 course</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-warning">คะแนน 350-499</strong>
                                            <p class="mb-0">ต้องลงทะเบียน course ภาษาอังกฤษเพิ่มเติม 1 course</p>
                                        </div>
                                        <span class="badge bg-warning rounded-pill">1 course</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-success">คะแนน 500 ขึ้นไป</strong>
                                            <p class="mb-0">ไม่ต้องลงทะเบียน course ภาษาอังกฤษเพิ่มเติม</p>
                                        </div>
                                        <span class="badge bg-success rounded-pill">0 course</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Required English Courses Based on TOEIC Score -->
        <?php if ($latest_toeic && $latest_toeic['Required_Courses'] > 0): ?>
        <div class="table-container">
            <h2><i class="fas fa-graduation-cap me-2"></i> course ภาษาอังกฤษที่ต้องลงทะเบียน</h2>
            
            <?php if (count($required_courses) > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    ตามคะแนน TOEIC ของคุณ (<?php echo $latest_toeic['TOEIC_Score']; ?>) 
                    คุณจำเป็นต้องลงทะเบียน course ภาษาอังกฤษเพิ่มเติม <?php echo $latest_toeic['Required_Courses']; ?> course
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>รหัสวิชา</th>
                                <th>ชื่อวิชา</th>
                                <th>กลุ่มเรียน</th>
                                <th>อาจารย์ผู้สอน</th>
                                <th>จำนวนที่นั่ง</th>
                                <th>ลงทะเบียน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($required_courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['section_number']); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                    <td>
                                        <?php echo $course['current_students']; ?> / <?php echo $course['max_students']; ?>
                                        (<?php echo round(($course['current_students'] / $course['max_students']) * 100); ?>%)
                                    </td>
                                    <td>
                                        <form method="post" action="course_registration.php">
                                            <input type="hidden" name="selected_courses[]" value="<?php echo $course['Course_Code'] . '_' . $course['section_id']; ?>">
                                            <button type="submit" name="register_courses" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus-circle"></i> ลงทะเบียน
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> 
                    ขณะนี้ไม่มี course ภาษาอังกฤษที่เปิดให้ลงทะเบียน โปรดติดต่อฝ่ายทะเบียนหรือตรวจสอบในภาคการศึกษาถัดไป
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Registered English Courses -->
        <?php if (count($registered_courses) > 0): ?>
        <div class="table-container">
            <h2><i class="fas fa-check-circle me-2"></i> course ภาษาอังกฤษที่ลงทะเบียนแล้ว</h2>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>กลุ่มเรียน</th>
                            <th>อาจารย์ผู้สอน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registered_courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($course['section_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($course['instructor_name'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($course['status'] == 'registered'): ?>
                                        <span class="badge bg-success">ลงทะเบียนแล้ว</span>
                                    <?php elseif ($course['status'] == 'withdrawn'): ?>
                                        <span class="badge bg-warning">ถอนรายวิชา</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ยกเลิก</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- TOEIC Test History -->
        <div class="table-container">
            <h2><i class="fas fa-history me-2"></i> ประวัติการสอบ TOEIC</h2>
            
            <?php if (count($toeic_results) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>วันที่สอบ</th>
                                <th>คะแนน</th>
                                <th>course ที่ต้องลงทะเบียน</th>
                                <th>สถานะ</th>
                                <th>เอกสาร</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($toeic_results as $result): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($result['Test_Date'])); ?></td>
                                    <td><?php echo $result['TOEIC_Score']; ?> / 990</td>
                                    <td>
                                        <?php if ($result['Required_Courses'] == 0): ?>
                                            <span class="badge bg-success">ไม่ต้องลงทะเบียนเพิ่มเติม</span>
                                        <?php elseif ($result['Required_Courses'] == 1): ?>
                                            <span class="badge bg-warning">1 course</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">2 course</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($result['Registration_Status'] == 'completed'): ?>
                                            <span class="badge bg-success">สมบูรณ์</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">รอดำเนินการ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($result['Document_Path'])): ?>
                                            <a href="<?php echo htmlspecialchars($result['Document_Path']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-file"></i> ดูเอกสาร
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">ไม่มีเอกสาร</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> ไม่มีประวัติการสอบ TOEIC
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Submit TOEIC Score Form -->
        <div class="table-container">
            <h2><i class="fas fa-upload me-2"></i> บันทึกผลการสอบ TOEIC</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6 border-end pe-4">
                        <h4 class="text-primary mb-3"><i class="fas fa-arrow-left me-2"></i> Pre-Test และ Post-Test</h4>
                        
                        <div class="mb-3">
                            <label for="pre_toeic_score" class="form-label">คะแนน Pre-TOEIC</label>
                            <input type="number" class="form-control" id="pre_toeic_score" name="pre_toeic_score" 
                                min="0" max="990"
                                value="<?php echo $latest_toeic ? $latest_toeic['Pre_Test_Score'] : ''; ?>">
                            <div class="form-text">คะแนนเต็ม 990 คะแนน</div>
                        </div>
                        <div class="mb-3">
                            <label for="pre_toeic_file" class="form-label">อัพโหลดเอกสารผลสอบ Pre-TOEIC</label>
                            <input type="file" class="form-control" id="pre_toeic_file" name="pre_toeic_file" accept="image/*,.pdf">
                            <div class="form-text">รองรับไฟล์ภาพ (JPG, PNG) และ PDF</div>
                        </div>

                        <div class="mb-3">
                            <label for="post_training1_score" class="form-label">คะแนน Post-Test อบรม 1</label>
                            <input type="number" class="form-control" id="post_training1_score" name="post_training1_score" 
                                min="0" max="100"
                                value="<?php echo $latest_toeic ? $latest_toeic['Post_Training1_Score'] : ''; ?>">
                            <div class="form-text">คะแนนเต็ม 100 คะแนน</div>
                        </div>
                        <div class="mb-3">
                            <label for="post_training1_file" class="form-label">อัพโหลดเอกสารผลสอบ Post-Test อบรม 1</label>
                            <input type="file" class="form-control" id="post_training1_file" name="post_training1_file" accept="image/*,.pdf">
                            <div class="form-text">รองรับไฟล์ภาพ (JPG, PNG) และ PDF</div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6 ps-4">
                        <h4 class="text-primary mb-3"><i class="fas fa-arrow-right me-2"></i> TOEIC และ Post-Test อบรม 2</h4>
                        
                        <div class="mb-3">
                            <label for="post_training2_score" class="form-label">คะแนน Post-Test อบรม 2</label>
                            <input type="number" class="form-control" id="post_training2_score" name="post_training2_score" 
                                min="0" max="100"
                                value="<?php echo $latest_toeic ? $latest_toeic['Post_Training2_Score'] : ''; ?>">
                            <div class="form-text">คะแนนเต็ม 100 คะแนน</div>
                        </div>
                        <div class="mb-3">
                            <label for="post_training2_file" class="form-label">อัพโหลดเอกสารผลสอบ Post-Test อบรม 2</label>
                            <input type="file" class="form-control" id="post_training2_file" name="post_training2_file" accept="image/*,.pdf">
                            <div class="form-text">รองรับไฟล์ภาพ (JPG, PNG) และ PDF</div>
                        </div>

                        <div class="mb-3">
                            <label for="toeic_score" class="form-label">คะแนน TOEIC</label>
                            <input type="number" class="form-control" id="toeic_score" name="toeic_score" 
                                min="0" max="990" required
                                value="<?php echo $latest_toeic ? $latest_toeic['TOEIC_Score'] : ''; ?>">
                            <div class="form-text">คะแนนเต็ม 990 คะแนน</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="toeic_file" class="form-label">อัพโหลดเอกสารผลสอบ TOEIC</label>
                            <input type="file" class="form-control" id="toeic_file" name="toeic_file" accept="image/*,.pdf">
                            <div class="form-text">รองรับไฟล์ภาพ (JPG, PNG) และ PDF</div>
                        </div>
                    </div>
                </div>

                <!-- Preview and Submit Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div id="preview-container" class="mb-3 text-center" style="display: none;">
                            <label class="form-label">ตัวอย่างเอกสาร</label>
                            <img id="document-preview" src="#" alt="Document Preview" class="toeic-document-preview">
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i> บันทึกผลการสอบ
                            </button>
                        </div>
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
        
        // Document preview
        document.getElementById('toeic_file').addEventListener('change', function(e) {
            const preview = document.getElementById('document-preview');
            const previewContainer = document.getElementById('preview-container');
            
            if (e.target.files && e.target.files[0]) {
                const fileType = e.target.files[0].type;
                
                if (fileType.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(e.target.files[0]);
                } else if (fileType === 'application/pdf') {
                    // For PDF files, show a generic image
                    preview.src = 'https://via.placeholder.com/300x400?text=PDF+Preview';
                    previewContainer.style.display = 'block';
                } else {
                    previewContainer.style.display = 'none';
                }
            } else {
                previewContainer.style.display = 'none';
            }
        });
        
        // Score input validation
        document.getElementById('toeic_score').addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            if (value < 0) {
                e.target.value = 0;
            } else if (value > 990) {
                e.target.value = 990;
            }
        });
    </script>
</body>
</html>