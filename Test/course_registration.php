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

// Process registration if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        if (isset($_POST['register_courses']) && isset($_POST['selected_courses'])) {
            $selected_courses = $_POST['selected_courses'];
            $student_id = null;
            
            // Get student ID from session user
            $student_query = "SELECT student_code FROM student_details WHERE id_account = ?";
            $student_stmt = $conn->prepare($student_query);
            $student_stmt->bindParam(1, $_SESSION['user_id']);
            $student_stmt->execute();
            $student_result = $student_stmt->fetch();
            
            if ($student_result) {
                $student_id = $student_result['student_code'];
                
                // Get current academic year and semester
                $term_query = "SELECT ay.year AS academic_year, sm.semester_number, sm.semester_id
                             FROM academic_years ay
                             JOIN semesters sm ON ay.academic_year_id = sm.academic_year_id
                             WHERE sm.is_current = 1";
                $term_stmt = $conn->prepare($term_query);
                $term_stmt->execute();
                $term_result = $term_stmt->fetch();
                
                if ($term_result) {
                    $academic_year = $term_result['academic_year'];
                    $semester = $term_result['semester_number'];
                    $semester_id = $term_result['semester_id'];
                    
                    // For each selected course, add registration
                    $success_count = 0;
                    $error_messages = [];
                    
                    foreach ($selected_courses as $course_data) {
                        list($course_code, $section_id) = explode('_', $course_data);
                        
                        // Check if the course is already registered
                        $check_query = "SELECT * FROM course_registration 
                                      WHERE Student_ID = ? AND Course_Code = ? AND Semester = ? AND Academic_Year = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bindParam(1, $student_id);
                        $check_stmt->bindParam(2, $course_code);
                        $check_stmt->bindParam(3, $semester);
                        $check_stmt->bindParam(4, $academic_year);
                        $check_stmt->execute();
                        
                        if ($check_stmt->rowCount() > 0) {
                            $error_messages[] = "รายวิชา $course_code ได้ลงทะเบียนไปแล้ว";
                            continue;
                        }
                        
                        // Get course credits
                        $credit_query = "SELECT Credits FROM course WHERE Course_Code = ?";
                        $credit_stmt = $conn->prepare($credit_query);
                        $credit_stmt->bindParam(1, $course_code);
                        $credit_stmt->execute();
                        $credit_result = $credit_stmt->fetch();
                        
                        if (!$credit_result) {
                            $error_messages[] = "ไม่พบข้อมูลหน่วยกิตของรายวิชา $course_code";
                            continue;
                        }
                        
                        $credits = $credit_result['Credits'];
                        
                        // Insert course registration
                        $insert_query = "INSERT INTO course_registration 
                                       (Student_ID, Course_Code, section_id, Semester, Academic_Year, status, Credits) 
                                       VALUES (?, ?, ?, ?, ?, 'registered', ?)";
                        $insert_stmt = $conn->prepare($insert_query);
                        $insert_stmt->bindParam(1, $student_id);
                        $insert_stmt->bindParam(2, $course_code);
                        $insert_stmt->bindParam(3, $section_id);
                        $insert_stmt->bindParam(4, $semester);
                        $insert_stmt->bindParam(5, $academic_year);
                        $insert_stmt->bindParam(6, $credits);
                        
                        if ($insert_stmt->execute()) {
                            // Update current_students count in sections
                            $update_section = "UPDATE course_sections 
                                             SET current_students = current_students + 1 
                                             WHERE section_id = ?";
                            $update_stmt = $conn->prepare($update_section);
                            $update_stmt->bindParam(1, $section_id);
                            $update_stmt->execute();
                            
                            $success_count++;
                        } else {
                            $error_messages[] = "ลงทะเบียนรายวิชา $course_code ไม่สำเร็จ";
                        }
                    }
                    
                    // Commit transaction if successful
                    $conn->commit();
                    
                    if ($success_count > 0) {
                        $success_message = "ลงทะเบียนสำเร็จ $success_count รายวิชา";
                        if (!empty($error_messages)) {
                            $error_message = implode("<br>", $error_messages);
                        }
                    } else {
                        $error_message = "ไม่สามารถลงทะเบียนได้";
                        if (!empty($error_messages)) {
                            $error_message .= "<br>" . implode("<br>", $error_messages);
                        }
                    }
                } else {
                    $error_message = "ไม่พบข้อมูลภาคการศึกษาปัจจุบัน";
                }
            } else {
                $error_message = "ไม่พบข้อมูลนักศึกษา";
            }
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

try {
    // Get student details
    $student_query = "SELECT s.student_code, s.major_id, s.study_year,
                    p.first_name, p.last_name, p.thai_first_name, p.thai_last_name,
                    m.major_name, m.thai_major_name,
                    d.department_name, d.thai_department_name,
                    f.faculty_name, f.thai_faculty_name,
                    c.Curriculum_ID, c.Curriculum_Name
                    FROM student_details s
                    JOIN account a ON s.id_account = a.id_account
                    JOIN user_profiles p ON a.id_account = p.id_account
                    LEFT JOIN major m ON s.major_id = m.major_id
                    LEFT JOIN department d ON m.department_id = d.department_id
                    LEFT JOIN faculty f ON d.faculty_id = f.id
                    LEFT JOIN curriculum c ON s.Curriculum_ID = c.Curriculum_ID
                    WHERE a.id_account = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bindParam(1, $_SESSION['user_id']);
    $student_stmt->execute();
    $student = $student_stmt->fetch();
    
    // Get current semester information
    $term_query = "SELECT ay.year AS academic_year, ay.academic_year_id, 
                  sm.semester_number, sm.semester_id, sm.name, sm.thai_name
                  FROM academic_years ay
                  JOIN semesters sm ON ay.academic_year_id = sm.academic_year_id
                  WHERE sm.is_current = 1";
    $term_stmt = $conn->prepare($term_query);
    $term_stmt->execute();
    $current_term = $term_stmt->fetch();
    
    // If no current semester, get the latest semester
    if (!$current_term) {
        $term_query = "SELECT ay.year AS academic_year, ay.academic_year_id,
                    sm.semester_number, sm.semester_id, sm.name, sm.thai_name
                    FROM academic_years ay
                    JOIN semesters sm ON ay.academic_year_id = sm.academic_year_id
                    ORDER BY ay.year DESC, sm.semester_number DESC LIMIT 1";
        $term_stmt = $conn->prepare($term_query);
        $term_stmt->execute();
        $current_term = $term_stmt->fetch();
    }
    
    // Get already registered courses for current semester
    $registered_courses_query = "SELECT cr.Registration_ID, cr.Course_Code, cr.section_id, cr.status,
                              c.Course_Name, cr.Credits, cs.section_number, cs.instructor_name
                              FROM course_registration cr
                              JOIN course c ON cr.Course_Code = c.Course_Code
                              LEFT JOIN course_sections cs ON cr.section_id = cs.section_id
                              WHERE cr.Student_ID = ? AND cr.Semester = ? AND cr.Academic_Year = ?";
    $registered_courses_stmt = $conn->prepare($registered_courses_query);
    $registered_courses_stmt->bindParam(1, $student['student_code']);
    $registered_courses_stmt->bindParam(2, $current_term['semester_number']);
    $registered_courses_stmt->bindParam(3, $current_term['academic_year']);
    $registered_courses_stmt->execute();
    $registered_courses = $registered_courses_stmt->fetchAll();
    
    // Get available course sections for the current semester based on the student's year and major
    $available_courses_query = "SELECT cs.section_id, cs.Course_Code, cs.section_number, 
                             cs.instructor_name, cs.max_students, cs.current_students, cs.status,
                             c.Course_Name, c.Credits
                             FROM course_sections cs
                             JOIN course c ON cs.Course_Code = c.Course_Code
                             WHERE cs.semester_id = ? AND cs.status = 'active' 
                             AND cs.current_students < cs.max_students
                             AND NOT EXISTS (
                                 SELECT 1 FROM course_registration cr 
                                 WHERE cr.Student_ID = ? AND cr.Course_Code = cs.Course_Code
                                 AND cr.Semester = ? AND cr.Academic_Year = ?
                             )";
    $available_courses_stmt = $conn->prepare($available_courses_query);
    $available_courses_stmt->bindParam(1, $current_term['semester_id']);
    $available_courses_stmt->bindParam(2, $student['student_code']);
    $available_courses_stmt->bindParam(3, $current_term['semester_number']);
    $available_courses_stmt->bindParam(4, $current_term['academic_year']);
    $available_courses_stmt->execute();
    $available_courses = $available_courses_stmt->fetchAll();
    
    // Get recommended courses for the student's year and major from major_courses table
    if ($student['major_id']) {
        $recommended_courses_query = "SELECT mc.Course_Code, c.Course_Name, c.Credits
                                   FROM major_courses mc
                                   JOIN course c ON mc.Course_Code = c.Course_Code
                                   WHERE mc.major_id = ? AND mc.study_year = ? AND mc.semester_number = ?
                                   AND NOT EXISTS (
                                       SELECT 1 FROM course_registration cr 
                                       WHERE cr.Student_ID = ? AND cr.Course_Code = mc.Course_Code
                                   )";
        $recommended_courses_stmt = $conn->prepare($recommended_courses_query);
        $recommended_courses_stmt->bindParam(1, $student['major_id']);
        $recommended_courses_stmt->bindParam(2, $student['study_year']);
        $recommended_courses_stmt->bindParam(3, $current_term['semester_number']);
        $recommended_courses_stmt->bindParam(4, $student['student_code']);
        $recommended_courses_stmt->execute();
        $recommended_courses = $recommended_courses_stmt->fetchAll();
    } else {
        $recommended_courses = [];
    }
    
    // Calculate total credits registered
    $total_credits = 0;
    foreach ($registered_courses as $course) {
        $total_credits += $course['Credits'];
    }
    
} catch (PDOException $e) {
    $error_message = "การดึงข้อมูลล้มเหลว: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนรายวิชา - Suan Dusit University</title>
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

        .table th {
            background-color: #f1f1f1;
        }

        .recommended-course {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .capacity-warning {
            color: #fd7e14;
        }

        .capacity-danger {
            color: #dc3545;
        }

        .remove-course-btn {
            color: #dc3545;
            cursor: pointer;
        }

        .add-course-btn {
            color: #198754;
            cursor: pointer;
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
        <a href="course_registration.php" class="active"><i class="fas fa-book"></i> ลงทะเบียนรายวิชา</a>
        <a href="my_grades.php"><i class="fas fa-chart-line"></i> ผลการเรียน</a>
        <a href="toeic_results.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ลงทะเบียนรายวิชา</div>
            <div class="search-container ms-auto">
                <input type="text" class="form-control" placeholder="ค้นหารายวิชา" id="search-input">
                <button class="btn btn-light" id="search-btn"><i class="fas fa-search"></i></button>
            </div>
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
            <h2 class="mb-0"><i class="fas fa-book me-2"></i> ลงทะเบียนรายวิชา <?php echo htmlspecialchars($current_term['thai_name'] . ' ปีการศึกษา ' . $current_term['academic_year']); ?></h2>
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

        <!-- Student Registration Summary -->
        <div class="table-container">
            <h2><i class="fas fa-info-circle me-2"></i> ข้อมูลการลงทะเบียน</h2>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ชื่อ-นามสกุล:</strong> 
                        <?php echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']); ?>
                    </p>
                    <p><strong>รหัสนักศึกษา:</strong> 
                        <?php echo htmlspecialchars($student['student_code']); ?>
                    </p>
                    <p><strong>คณะ:</strong> 
                        <?php echo htmlspecialchars($student['thai_faculty_name'] ?? 'ไม่ระบุ'); ?>
                    </p>
                    <p><strong>ภาควิชา/สาขา:</strong> 
                        <?php echo htmlspecialchars($student['thai_department_name'] ?? 'ไม่ระบุ'); ?> / 
                        <?php echo htmlspecialchars($student['thai_major_name'] ?? 'ไม่ระบุ'); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>ภาคการศึกษา:</strong> 
                        <?php echo htmlspecialchars($current_term['thai_name'] . ' ปีการศึกษา ' . $current_term['academic_year']); ?>
                    </p>
                    <p><strong>ชั้นปี:</strong> 
                        <?php echo htmlspecialchars($student['study_year']); ?>
                    </p>
                    <p><strong>หลักสูตร:</strong> 
                        <?php echo htmlspecialchars($student['Curriculum_Name'] ?? 'ไม่ระบุ'); ?>
                    </p>
                    <p><strong>หน่วยกิตลงทะเบียนในภาคการศึกษานี้:</strong> 
                        <span class="<?php echo ($total_credits < 9 || $total_credits > 22) ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $total_credits; ?> หน่วยกิต
                        </span>
                        <?php if ($total_credits < 9): ?>
                            <span class="badge bg-warning">น้อยกว่าที่กำหนด (9 หน่วยกิต)</span>
                        <?php elseif ($total_credits > 22): ?>
                            <span class="badge bg-warning">เกินที่กำหนด (22 หน่วยกิต)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Current Registered Courses -->
        <div class="table-container">
            <h2><i class="fas fa-clipboard-list me-2"></i> รายวิชาที่ลงทะเบียนแล้ว</h2>
            <div class="table-responsive">
                <table class="table table-hover" id="registered-courses-table">
                    <thead>
                        <tr>
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>กลุ่มเรียน</th>
                            <th>อาจารย์ผู้สอน</th>
                            <th>หน่วยกิต</th>
                            <th>สถานะ</th>
                            <th>ยกเลิก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($registered_courses) > 0): ?>
                            <?php foreach ($registered_courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['section_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($course['Credits']); ?></td>
                                    <td>
                                        <?php if ($course['status'] == 'registered'): ?>
                                            <span class="badge bg-success">ลงทะเบียนแล้ว</span>
                                        <?php elseif ($course['status'] == 'withdrawn'): ?>
                                            <span class="badge bg-warning">ถอนรายวิชา</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ยกเลิก</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="withdraw_course.php" onsubmit="return confirm('คุณต้องการถอนรายวิชานี้ใช่หรือไม่?');">
                                            <input type="hidden" name="registration_id" value="<?php echo $course['Registration_ID']; ?>">
                                            <button type="submit" class="btn btn-link text-danger p-0" title="ถอนรายวิชา">
                                                <i class="fas fa-times-circle remove-course-btn"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">ยังไม่มีรายวิชาที่ลงทะเบียน</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="text-end"><strong>รวม</strong></td>
                            <td><strong><?php echo $total_credits; ?></strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Available Courses for Registration -->
        <div class="table-container">
            <h2><i class="fas fa-list-alt me-2"></i> รายวิชาที่เปิดให้ลงทะเบียน</h2>
            
            <form method="post" id="register-form">
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="show-recommended-only">
                        <label class="form-check-label" for="show-recommended-only">
                            แสดงเฉพาะรายวิชาที่แนะนำตามแผนการเรียน
                        </label>
                    </div>
                </div>
                
                <div class="table-responsive mb-3">
                    <table class="table table-hover" id="available-courses-table">
                        <thead>
                            <tr>
                                <th>เลือก</th>
                                <th>รหัสวิชา</th>
                                <th>ชื่อวิชา</th>
                                <th>กลุ่มเรียน</th>
                                <th>อาจารย์ผู้สอน</th>
                                <th>หน่วยกิต</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($available_courses) > 0): ?>
                                <?php foreach ($available_courses as $course): 
                                    // Check if course is recommended
                                    $is_recommended = false;
                                    foreach ($recommended_courses as $rec_course) {
                                        if ($rec_course['Course_Code'] == $course['Course_Code']) {
                                            $is_recommended = true;
                                            break;
                                        }
                                    }
                                    
                                    // Calculate capacity percentage
                                    $capacity_percentage = ($course['current_students'] / $course['max_students']) * 100;
                                    $capacity_class = '';
                                    if ($capacity_percentage >= 80) {
                                        $capacity_class = 'capacity-danger';
                                    } elseif ($capacity_percentage >= 60) {
                                        $capacity_class = 'capacity-warning';
                                    }
                                ?>
                                <tr class="<?php echo $is_recommended ? 'recommended-course' : ''; ?>" data-recommended="<?php echo $is_recommended ? '1' : '0'; ?>">
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input course-checkbox" type="checkbox" name="selected_courses[]" value="<?php echo $course['Course_Code'] . '_' . $course['section_id']; ?>">
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?> 
                                        <?php if ($is_recommended): ?>
                                            <span class="badge bg-success">แนะนำ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['section_number']); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Credits']); ?></td>
                                    <td>
                                        <div class="<?php echo $capacity_class; ?>">
                                            <?php echo $course['current_students']; ?>/<?php echo $course['max_students']; ?>
                                            (<?php echo number_format($capacity_percentage, 0); ?>%)
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">ไม่พบรายวิชาที่สามารถลงทะเบียนได้</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    รายการที่มีพื้นหลังสีเขียวอ่อนเป็นรายวิชาที่แนะนำตามแผนการเรียนสำหรับชั้นปีของคุณ
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="me-3"><i class="fas fa-check-circle text-success"></i> รายวิชาที่เลือก: <span id="selected-count">0</span></span>
                        <span><i class="fas fa-calculator"></i> หน่วยกิตรวม: <span id="selected-credits">0</span></span>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" name="register_courses" id="register-btn" disabled>
                            <i class="fas fa-plus-circle me-2"></i> ลงทะเบียนรายวิชาที่เลือก
                        </button>
                    </div>
                </div>
            </form>
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
        
        // Search functionality
        document.getElementById('search-input').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            
            // Search in available courses
            const availableTable = document.getElementById('available-courses-table');
            if (availableTable) {
                const rows = availableTable.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    if (rows[i].cells && rows[i].cells.length > 1) {
                        const courseId = rows[i].cells[1].textContent.toLowerCase();
                        const courseName = rows[i].cells[2].textContent.toLowerCase();
                        const instructor = rows[i].cells[4].textContent.toLowerCase();
                        
                        if (courseId.includes(input) || courseName.includes(input) || instructor.includes(input)) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
            }
            
            // Search in registered courses
            const registeredTable = document.getElementById('registered-courses-table');
            if (registeredTable) {
                const rows = registeredTable.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    if (rows[i].cells && rows[i].cells.length > 1) {
                        const courseId = rows[i].cells[0].textContent.toLowerCase();
                        const courseName = rows[i].cells[1].textContent.toLowerCase();
                        const instructor = rows[i].cells[3].textContent.toLowerCase();
                        
                        if (courseId.includes(input) || courseName.includes(input) || instructor.includes(input)) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
            }
        });
        
        // Filter recommended courses
        document.getElementById('show-recommended-only').addEventListener('change', function() {
            const availableTable = document.getElementById('available-courses-table');
            if (availableTable) {
                const rows = availableTable.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    if (this.checked) {
                        // Show only recommended courses
                        if (rows[i].dataset.recommended === '1') {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    } else {
                        // Show all courses
                        rows[i].style.display = '';
                    }
                }
            }
        });
        
        // Calculate selected courses and credits
        const courseCheckboxes = document.querySelectorAll('.course-checkbox');
        const selectedCountSpan = document.getElementById('selected-count');
        const selectedCreditsSpan = document.getElementById('selected-credits');
        const registerBtn = document.getElementById('register-btn');
        
        courseCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCounts);
        });
        
        function updateSelectedCounts() {
            const selectedCourses = document.querySelectorAll('.course-checkbox:checked');
            selectedCountSpan.textContent = selectedCourses.length;
            
            let totalCredits = <?php echo $total_credits; ?>;
            let additionalCredits = 0;
            
            selectedCourses.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const credits = parseInt(row.cells[5].textContent);
                additionalCredits += credits;
            });
            
            const newTotalCredits = totalCredits + additionalCredits;
            selectedCreditsSpan.textContent = additionalCredits + ' (รวมทั้งหมด: ' + newTotalCredits + ')';
            
            // Enable/disable registration button
            registerBtn.disabled = selectedCourses.length === 0;
            
            // Warning for credit limits
            if (newTotalCredits < 9) {
                selectedCreditsSpan.innerHTML = additionalCredits + ' <span class="badge bg-warning">น้อยกว่าที่กำหนด (9 หน่วยกิต)</span>';
            } else if (newTotalCredits > 22) {
                selectedCreditsSpan.innerHTML = additionalCredits + ' <span class="badge bg-danger">เกินที่กำหนด (22 หน่วยกิต)</span>';
            }
        }
        
        // Search button click
        document.getElementById('search-btn').addEventListener('click', function() {
            const input = document.getElementById('search-input');
            const event = new Event('keyup');
            input.dispatchEvent(event);
        });
        
        // Form submission validation
        document.getElementById('register-form').addEventListener('submit', function(e) {
            const selectedCourses = document.querySelectorAll('.course-checkbox:checked');
            
            if (selectedCourses.length === 0) {
                e.preventDefault();
                alert('กรุณาเลือกรายวิชาที่ต้องการลงทะเบียน');
                return false;
            }
            
            // Calculate total credits
            let totalCredits = <?php echo $total_credits; ?>;
            let additionalCredits = 0;
            
            selectedCourses.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const credits = parseInt(row.cells[5].textContent);
                additionalCredits += credits;
            });
            
            const newTotalCredits = totalCredits + additionalCredits;
            
            // Credit limit validation
            if (newTotalCredits > 22) {
                if (!confirm('คุณกำลังลงทะเบียนเกินกว่า 22 หน่วยกิต ต้องการดำเนินการต่อหรือไม่?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    </script>
</body>
</html>