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
    // Get student information
    $student_query = "SELECT s.student_code, s.major_id, s.study_year, s.entry_year, s.Curriculum_ID,
                    p.first_name, p.last_name, p.thai_first_name, p.thai_last_name,
                    m.major_name, m.thai_major_name,
                    d.department_name, d.thai_department_name,
                    f.faculty_name, f.thai_faculty_name,
                    c.Curriculum_Name, c.Required_Credit
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
    
    // Get all academic years for filter
    $years_query = "SELECT DISTINCT Academic_Year FROM course_registration 
                  WHERE Student_ID = ? ORDER BY Academic_Year DESC";
    $years_stmt = $conn->prepare($years_query);
    $years_stmt->bindParam(1, $student['student_code']);
    $years_stmt->execute();
    $academic_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Set active academic year filter (default to latest or from GET parameter)
    $active_year = isset($_GET['year']) ? intval($_GET['year']) : (count($academic_years) > 0 ? $academic_years[0] : null);
    
    // Get courses and grades
    $filter_condition = $active_year ? " AND cr.Academic_Year = ?" : "";
    $grades_query = "SELECT cr.Registration_ID, cr.Course_Code, cr.Semester, cr.Academic_Year, 
                    cr.Grade, cr.Credits, cr.status, c.Course_Name, ct.Course_Type_ID
                    FROM course_registration cr
                    JOIN course c ON cr.Course_Code = c.Course_Code
                    LEFT JOIN course_type ct ON c.Course_Code = ct.Course_Code
                    WHERE cr.Student_ID = ? $filter_condition
                    ORDER BY cr.Academic_Year DESC, cr.Semester ASC, cr.Course_Code ASC";
    $grades_stmt = $conn->prepare($grades_query);
    $grades_stmt->bindParam(1, $student['student_code']);
    if ($active_year) {
        $grades_stmt->bindParam(2, $active_year);
    }
    $grades_stmt->execute();
    $grades = $grades_stmt->fetchAll();
    
    // Calculate statistics
    $total_credits = 0;
    $completed_credits = 0;
    $total_grade_points = 0;
    $semester_stats = [];
    
    // Course type statistics
    $course_type_names = [
        '1' => 'วิชาทั่วไป',
        '2' => 'วิชาเฉพาะ',
        '3' => 'วิชาเสรี'
    ];
    
    $course_type_stats = [
        '1' => ['credits' => 0, 'completed' => 0, 'grade_points' => 0],
        '2' => ['credits' => 0, 'completed' => 0, 'grade_points' => 0],
        '3' => ['credits' => 0, 'completed' => 0, 'grade_points' => 0]
    ];
    
    // Process grades
    foreach ($grades as $course) {
        $term_key = $course['Academic_Year'] . '-' . $course['Semester'];
        
        // Initialize semester statistics if not exists
        if (!isset($semester_stats[$term_key])) {
            $semester_stats[$term_key] = [
                'year' => $course['Academic_Year'],
                'semester' => $course['Semester'],
                'credits' => 0,
                'grade_points' => 0,
                'completed_credits' => 0,
                'gpa' => 0
            ];
        }
        
        // Count credits for registered courses
        if ($course['status'] == 'registered') {
            $total_credits += $course['Credits'];
            $semester_stats[$term_key]['credits'] += $course['Credits'];
            
            // Course type statistics
            if (isset($course['Course_Type_ID']) && isset($course_type_stats[$course['Course_Type_ID']])) {
                $course_type_stats[$course['Course_Type_ID']]['credits'] += $course['Credits'];
            }
            
            // If grade exists, calculate grade points
            if ($course['Grade'] !== null) {
                $completed_credits += $course['Credits'];
                $semester_stats[$term_key]['completed_credits'] += $course['Credits'];
                
                $grade_points = $course['Grade'] * $course['Credits'];
                $total_grade_points += $grade_points;
                $semester_stats[$term_key]['grade_points'] += $grade_points;
                
                // Course type statistics for completed courses with grades
                if (isset($course['Course_Type_ID']) && isset($course_type_stats[$course['Course_Type_ID']])) {
                    $course_type_stats[$course['Course_Type_ID']]['completed'] += $course['Credits'];
                    $course_type_stats[$course['Course_Type_ID']]['grade_points'] += $grade_points;
                }
            }
        }
    }
    
    // Calculate GPA for each semester
    foreach ($semester_stats as $term_key => &$stats) {
        if ($stats['completed_credits'] > 0) {
            $stats['gpa'] = $stats['grade_points'] / $stats['completed_credits'];
        }
    }
    
    // Calculate overall GPA
    $overall_gpa = ($completed_credits > 0) ? ($total_grade_points / $completed_credits) : 0;
    
    // Calculate GPA for each course type
    foreach ($course_type_stats as $type_id => &$stats) {
        $stats['gpa'] = ($stats['completed'] > 0) ? ($stats['grade_points'] / $stats['completed']) : 0;
    }
    
    // Calculate progress percentage
    $curriculum_credits = $student['Required_Credit'] ?? 120; // Default to 120 if not specified
    $progress_percentage = ($completed_credits / $curriculum_credits) * 100;
    
} catch (PDOException $e) {
    $error_message = "การดึงข้อมูลล้มเหลว: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการเรียน - Suan Dusit University</title>
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

        .grade-a {
            color: #198754;
            font-weight: bold;
        }

        .grade-b {
            color: #0d6efd;
            font-weight: bold;
        }

        .grade-c {
            color: #6c757d;
            font-weight: bold;
        }

        .grade-d {
            color: #fd7e14;
            font-weight: bold;
        }

        .grade-f {
            color: #dc3545;
            font-weight: bold;
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
            padding: 15px;
            height: 100%;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .bg-primary-light {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .bg-success-light {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .bg-info-light {
            background-color: rgba(13, 202, 240, 0.1);
            color: #0dcaf0;
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .semester-link.active {
            background-color: #0d6efd;
            color: white;
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
        <a href="my_grades.php" class="active"><i class="fas fa-chart-line"></i> ผลการเรียน</a>
        <a href="toeic_results.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ผลการเรียน</div>
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
            <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i> ผลการเรียน</h2>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Student Information -->
        <div class="table-container">
            <h2><i class="fas fa-user-graduate me-2"></i> ข้อมูลนักศึกษา</h2>
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
                    <p><strong>ปีที่เข้าศึกษา:</strong> 
                        <?php echo htmlspecialchars($student['entry_year']); ?>
                    </p>
                    <p><strong>ชั้นปี:</strong> 
                        <?php echo htmlspecialchars($student['study_year']); ?>
                    </p>
                    <p><strong>หลักสูตร:</strong> 
                        <?php echo htmlspecialchars($student['Curriculum_Name'] ?? 'ไม่ระบุ'); ?>
                    </p>
                    <p><strong>เกรดเฉลี่ยรวม:</strong> 
                        <span class="
                            <?php
                            if ($overall_gpa >= 3.5) echo 'grade-a';
                            elseif ($overall_gpa >= 3.0) echo 'grade-b';
                            elseif ($overall_gpa >= 2.0) echo 'grade-c';
                            elseif ($overall_gpa >= 1.0) echo 'grade-d';
                            else echo 'grade-f';
                            ?>
                        ">
                            <?php echo number_format($overall_gpa, 2); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Grade Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon bg-primary-light">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3><?php echo number_format($overall_gpa, 2); ?></h3>
                    <p>เกรดเฉลี่ยรวม</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-primary" role="progressbar" 
                            style="width: <?php echo min(($overall_gpa / 4) * 100, 100); ?>%" 
                            aria-valuenow="<?php echo $overall_gpa; ?>" aria-valuemin="0" aria-valuemax="4">
                            <?php echo number_format($overall_gpa, 2); ?>/4.00
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon bg-success-light">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3><?php echo $completed_credits; ?>/<?php echo $curriculum_credits; ?></h3>
                    <p>หน่วยกิตที่สะสม</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" role="progressbar" 
                            style="width: <?php echo min($progress_percentage, 100); ?>%" 
                            aria-valuenow="<?php echo $completed_credits; ?>" 
                            aria-valuemin="0" aria-valuemax="<?php echo $curriculum_credits; ?>">
                            <?php echo number_format($progress_percentage, 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon bg-info-light">
                        <i class="fas fa-university"></i>
                    </div>
                    <h3><?php echo count($semester_stats); ?></h3>
                    <p>ภาคการศึกษาที่ผ่านมา</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-info" role="progressbar" 
                            style="width: <?php echo min((count($semester_stats) / 8) * 100, 100); ?>%" 
                            aria-valuenow="<?php echo count($semester_stats); ?>" aria-valuemin="0" aria-valuemax="8">
                            <?php echo count($semester_stats); ?>/8 เทอม
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon bg-warning-light">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo $student['study_year']; ?>/4</h3>
                    <p>ชั้นปี</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-warning" role="progressbar" 
                            style="width: <?php echo ($student['study_year'] / 4) * 100; ?>%" 
                            aria-valuenow="<?php echo $student['study_year']; ?>" aria-valuemin="0" aria-valuemax="4">
                            ปี <?php echo $student['study_year']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Course Type Progress -->
        <div class="table-container">
            <h2><i class="fas fa-tasks me-2"></i> สรุปผลการเรียนตามหมวดวิชา</h2>
            <div class="row">
                <?php foreach ($course_type_stats as $type_id => $stats): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><?php echo $course_type_names[$type_id]; ?></h5>
                        </div>
                        <div class="card-body">
                            <p><strong>เกรดเฉลี่ย:</strong> <?php echo number_format($stats['gpa'], 2); ?></p>
                            <p><strong>หน่วยกิตที่ลงทะเบียน:</strong> <?php echo $stats['credits']; ?></p>
                            <p><strong>หน่วยกิตที่สะสม:</strong> <?php echo $stats['completed']; ?></p>
                            <div class="progress mt-2">
                                <div class="progress-bar" role="progressbar" 
                                    style="width: <?php echo ($stats['credits'] > 0) ? (($stats['completed'] / $stats['credits']) * 100) : 0; ?>%">
                                    <?php echo ($stats['credits'] > 0) ? number_format(($stats['completed'] / $stats['credits']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Academic Year Filter -->
        <div class="table-container">
            <h2><i class="fas fa-filter me-2"></i> เลือกปีการศึกษา</h2>
            <div class="d-flex flex-wrap">
                <a href="my_grades.php" class="btn btn-outline-primary m-1 semester-link <?php echo empty($active_year) ? 'active' : ''; ?>">
                    ทั้งหมด
                </a>
                <?php foreach ($academic_years as $year): ?>
                <a href="my_grades.php?year=<?php echo $year; ?>" class="btn btn-outline-primary m-1 semester-link <?php echo $active_year == $year ? 'active' : ''; ?>">
                    <?php echo $year; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Grade Details -->
        <div class="table-container">
            <h2>
                <i class="fas fa-book me-2"></i> 
                ผลการเรียน<?php echo $active_year ? " ปีการศึกษา " . $active_year : "ทั้งหมด"; ?>
            </h2>
            
            <?php
            // Group courses by semester
            $grouped_courses = [];
            foreach ($grades as $course) {
                $term_key = $course['Academic_Year'] . '-' . $course['Semester'];
                if (!isset($grouped_courses[$term_key])) {
                    $grouped_courses[$term_key] = [
                        'year' => $course['Academic_Year'],
                        'semester' => $course['Semester'],
                        'courses' => []
                    ];
                }
                $grouped_courses[$term_key]['courses'][] = $course;
            }
            
            // Sort by year and semester (latest first)
            krsort($grouped_courses);
            
            if (count($grouped_courses) > 0):
                foreach ($grouped_courses as $term_key => $term_data):
                    $term_stats = $semester_stats[$term_key] ?? null;
            ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                ภาคเรียนที่ <?php echo $term_data['semester']; ?> 
                                ปีการศึกษา <?php echo $term_data['year']; ?>
                            </h5>
                            <?php if ($term_stats): ?>
                            <div>
                                <span class="badge bg-primary">
                                    เกรดเฉลี่ย: <?php echo number_format($term_stats['gpa'], 2); ?>
                                </span>
                                <span class="badge bg-secondary ms-2">
                                    หน่วยกิต: <?php echo $term_stats['completed_credits']; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>รหัสวิชา</th>
                                        <th>ชื่อวิชา</th>
                                        <th>หน่วยกิต</th>
                                        <th>เกรด</th>
                                        <th>สถานะ</th>
                                        <th>หมวดวิชา</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($term_data['courses'] as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                        <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['Credits']); ?></td>
                                        <td class="
                                            <?php
                                            if ($course['Grade'] >= 3.5) echo 'grade-a';
                                            elseif ($course['Grade'] >= 3.0) echo 'grade-b';
                                            elseif ($course['Grade'] >= 2.0) echo 'grade-c';
                                            elseif ($course['Grade'] >= 1.0) echo 'grade-d';
                                            elseif ($course['Grade'] !== null) echo 'grade-f';
                                            ?>
                                        ">
                                            <?php echo $course['Grade'] !== null ? number_format($course['Grade'], 2) : '-'; ?>
                                        </td>
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
                                            <?php if (isset($course['Course_Type_ID']) && isset($course_type_names[$course['Course_Type_ID']])): ?>
                                                <?php echo htmlspecialchars($course_type_names[$course['Course_Type_ID']]); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if ($term_stats): ?>
                                <tfoot>
                                    <tr class="table-secondary">
                                        <td colspan="2" class="text-end"><strong>รวมประจำภาคเรียน</strong></td>
                                        <td><strong><?php echo $term_stats['credits']; ?></strong></td>
                                        <td class="
                                            <?php
                                            if ($term_stats['gpa'] >= 3.5) echo 'grade-a';
                                            elseif ($term_stats['gpa'] >= 3.0) echo 'grade-b';
                                            elseif ($term_stats['gpa'] >= 2.0) echo 'grade-c';
                                            elseif ($term_stats['gpa'] >= 1.0) echo 'grade-d';
                                            else echo 'grade-f';
                                            ?>
                                        ">
                                            <strong><?php echo number_format($term_stats['gpa'], 2); ?></strong>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> ยังไม่มีข้อมูลผลการเรียน
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Grade Distribution Chart -->
        <div class="table-container">
            <h2><i class="fas fa-chart-bar me-2"></i> การกระจายเกรด</h2>
            
            <?php
            // Calculate grade distribution
            $grade_distribution = [
                'A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 
                'D+' => 0, 'D' => 0, 'F' => 0
            ];
            
            foreach ($grades as $course) {
                if ($course['Grade'] !== null && $course['status'] == 'registered') {
                    if ($course['Grade'] >= 3.5) $grade_distribution['A']++;
                    elseif ($course['Grade'] >= 3.0) $grade_distribution['B+']++;
                    elseif ($course['Grade'] >= 2.5) $grade_distribution['B']++;
                    elseif ($course['Grade'] >= 2.0) $grade_distribution['C+']++;
                    elseif ($course['Grade'] >= 1.5) $grade_distribution['C']++;
                    elseif ($course['Grade'] >= 1.0) $grade_distribution['D+']++;
                    elseif ($course['Grade'] >= 0.5) $grade_distribution['D']++;
                    else $grade_distribution['F']++;
                }
            }
            
            // Find max value for scaling
            $max_count = max($grade_distribution);
            $max_count = max($max_count, 1); // Avoid division by zero
            ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container" style="height: 300px;">
                        <div class="d-flex h-100 align-items-end">
                            <?php foreach ($grade_distribution as $grade => $count): ?>
                                <div class="d-flex flex-column align-items-center mx-2" style="flex: 1;">
                                    <div><?php echo $count; ?></div>
                                    <div class="bg-primary rounded-top" style="width: 40px; height: <?php echo ($count / $max_count) * 250; ?>px;"></div>
                                    <div class="mt-2 font-weight-bold"><?php echo $grade; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">สรุปเกรด</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>เกรด</th>
                                        <th>จำนวนวิชา</th>
                                        <th>ร้อยละ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_courses = array_sum($grade_distribution);
                                    foreach ($grade_distribution as $grade => $count): 
                                        $percentage = $total_courses > 0 ? ($count / $total_courses) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $grade; ?></td>
                                        <td><?php echo $count; ?></td>
                                        <td><?php echo number_format($percentage, 1); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary">
                                        <td><strong>รวม</strong></td>
                                        <td><strong><?php echo $total_courses; ?></strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Print Transcript Button -->
        <div class="d-flex justify-content-center mb-4">
            <a href="print_transcript.php" target="_blank" class="btn btn-primary">
                <i class="fas fa-print me-2"></i> พิมพ์ใบแสดงผลการเรียน
            </a>
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
    </script>
</body>
</html>