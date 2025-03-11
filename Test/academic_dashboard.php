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

// Get academic staff information
try {
    $sql = "SELECT a.*, up.* FROM account a 
            JOIN user_profiles up ON a.id_account = up.id_account
            WHERE a.id_account = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    // Get total student count
    $student_count_sql = "SELECT COUNT(*) as total FROM student_details";
    $student_count_stmt = $conn->prepare($student_count_sql);
    $student_count_stmt->execute();
    $student_count = $student_count_stmt->fetch();
    
    // Get total teacher count
    $teacher_count_sql = "SELECT COUNT(*) as total FROM teacher_details";
    $teacher_count_stmt = $conn->prepare($teacher_count_sql);
    $teacher_count_stmt->execute();
    $teacher_count = $teacher_count_stmt->fetch();
    
    // Get total course count
    $course_count_sql = "SELECT COUNT(*) as total FROM course";
    $course_count_stmt = $conn->prepare($course_count_sql);
    $course_count_stmt->execute();
    $course_count = $course_count_stmt->fetch();
    
    // Get recent student registrations
    $recent_students_sql = "SELECT s.student_code, up.first_name, up.last_name, up.thai_first_name, up.thai_last_name, 
                         a.created_at, m.major_name, m.thai_major_name, s.study_year
                     FROM student_details s
                     JOIN account a ON s.id_account = a.id_account
                     JOIN user_profiles up ON a.id_account = up.id_account
                     LEFT JOIN major m ON s.major_id = m.major_id
                     ORDER BY a.created_at DESC
                     LIMIT 10";
    $recent_students_stmt = $conn->prepare($recent_students_sql);
    $recent_students_stmt->execute();
    $recent_students = $recent_students_stmt->fetchAll();
    
    // Get TOEIC statistics
    $toeic_stats_sql = "SELECT 
                        MIN(TOEIC_Score) as min_score,
                        MAX(TOEIC_Score) as max_score,
                        AVG(TOEIC_Score) as avg_score,
                        COUNT(*) as total_tests
                    FROM toeic";
    $toeic_stats_stmt = $conn->prepare($toeic_stats_sql);
    $toeic_stats_stmt->execute();
    $toeic_stats = $toeic_stats_stmt->fetch();
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Staff Dashboard - Suan Dusit University</title>
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

        .topbar .search-container {
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: 15px;
        }

        .topbar .search-container input {
            border-radius: 20px;
            padding: 8px 15px;
            border: none;
            width: 250px;
        }

        .topbar .search-container button {
            border-radius: 20px;
            margin-left: 5px;
            padding: 8px 15px;
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

        .card-stats {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-stats .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .card-stats h3 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-stats p {
            color: #6c757d;
            margin-bottom: 0;
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

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .table-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .table-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .table-container h2 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #3871c1;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 10px;
        }

        .table th {
            background-color: #f2f7fd;
            color: #3871c1;
            font-weight: 600;
            text-align: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-bottom: 2px solid #3871c1;
        }

        .table td {
            text-align: center;
            padding: 12px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .action-buttons .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .action-buttons .btn-view {
            background-color: #17a2b8;
            color: white;
            border: none;
        }
        
        .action-buttons .btn-view:hover {
            background-color: #138496;
        }

        .action-buttons .btn-edit {
            background-color: #ffc107;
            color: white;
            border: none;
        }
        
        .action-buttons .btn-edit:hover {
            background-color: #e0a800;
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

        .btn-primary {
            background-color: #3871c1;
            border-color: #3871c1;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: #2b5ca3;
            border-color: #2b5ca3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .progress {
            height: 16px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .progress-bar {
            border-radius: 8px;
        }

        .list-group-item {
            border-radius: 5px !important;
            margin-bottom: 5px;
            transition: all 0.2s;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .list-group-item i {
            color: #3871c1;
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
            .topbar .search-container input {
                width: 180px;
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
        <a href="academic_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
        <a href="students.php"><i class="fas fa-user-graduate"></i> ข้อมูลนักศึกษา</a>
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="majors.php"><i class="fas fa-graduation-cap"></i> สาขา/หลักสูตร</a>
        <a href="แผนการเรียน.php"><i class="fas fa-calendar-check"></i> แผนการเรียน</a>
        <a href="schedule.php" ><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>
    
    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ระบบจัดการข้อมูลนักศึกษา</div>
            <div class="search-container">
                <form method="GET" action="search.php" class="d-flex">
                    <input type="text" name="q" class="form-control" placeholder="ค้นหาที่นี่" id="search-input">
                    <button type="submit" class="btn btn-light" id="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="user-info">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <div>
                    <strong><?php echo htmlspecialchars(($user['thai_first_name'] ? $user['thai_first_name'] . ' ' . $user['thai_last_name'] : $_SESSION['username'])); ?></strong>
                    <p class="m-0">ฝ่ายวิชาการ</p>
                </div>
            </div>
        </div>

        <!-- Section Header -->
        <div class="section-header mb-4">
            <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> ภาพรวมของระบบ</h2>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card-stats">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-primary-light">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3><?php echo $student_count['total']; ?></h3>
                            <p>นักศึกษาทั้งหมด</p>
                        </div>
                        <div class="align-self-center">
                            <a href="students.php" class="btn btn-sm btn-outline-primary rounded-circle">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-success-light">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h3><?php echo $teacher_count['total']; ?></h3>
                            <p>อาจารย์ทั้งหมด</p>
                        </div>
                        <div class="align-self-center">
                            <a href="teachers.php" class="btn btn-sm btn-outline-success rounded-circle">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-info-light">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3><?php echo $course_count['total']; ?></h3>
                            <p>รายวิชาทั้งหมด</p>
                        </div>
                        <div class="align-self-center">
                            <a href="courses.php" class="btn btn-sm btn-outline-info rounded-circle">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-warning-light">
                                <i class="fas fa-language"></i>
                            </div>
                            <h3><?php echo $toeic_stats['total_tests'] ?: 0; ?></h3>
                            <p>ผู้สอบ TOEIC</p>
                        </div>
                        <div class="align-self-center">
                            <a href="toeic.php" class="btn btn-sm btn-outline-warning rounded-circle">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Students -->
            <div class="col-md-8">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2><i class="fas fa-user-plus me-2"></i> นักศึกษาลงทะเบียนล่าสุด</h2>
                        <a href="students.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-users me-1"></i> ดูทั้งหมด
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>รหัสนักศึกษา</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>สาขาวิชา</th>
                                    <th>ชั้นปี</th>
                                    <th>วันที่ลงทะเบียน</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                    <td><?php echo htmlspecialchars(($student['thai_first_name'] . ' ' . $student['thai_last_name'])); ?></td>
                                    <td><?php echo htmlspecialchars($student['thai_major_name'] ?: 'ยังไม่ได้กำหนด'); ?></td>
                                    <td><?php echo htmlspecialchars($student['study_year']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="student_detail.php?id=<?php echo $student['student_code']; ?>" 
                                               class="btn btn-view" title="ดูข้อมูลนักศึกษา">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="student_edit.php?id=<?php echo $student['student_code']; ?>" 
                                               class="btn btn-edit" title="แก้ไขข้อมูลนักศึกษา">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($recent_students) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center">ไม่พบข้อมูลนักศึกษา</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TOEIC Statistics -->
            <div class="col-md-4">
                <div class="table-container">
                    <h2><i class="fas fa-language me-2"></i> สถิติผลสอบ TOEIC</h2>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>คะแนนเฉลี่ย</h5>
                            <div class="progress">
                                <?php 
                                $avg_score = round($toeic_stats['avg_score'] ?: 0);
                                $percent = min(($avg_score / 990) * 100, 100);
                                ?>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%" 
                                    aria-valuenow="<?php echo $avg_score; ?>" aria-valuemin="0" aria-valuemax="990">
                                    <?php echo $avg_score; ?>
                                </div>
                            </div>
                            <small class="text-muted">จากคะแนนเต็ม 990 คะแนน</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="card-stats p-3">
                                    <div class="icon bg-success-light">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <h4><?php echo $toeic_stats['max_score'] ?: 0; ?></h4>
                                    <p>คะแนนสูงสุด</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card-stats p-3">
                                    <div class="icon bg-danger-light">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <h4><?php echo $toeic_stats['min_score'] ?: 0; ?></h4>
                                    <p>คะแนนต่ำสุด</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="toeic.php" class="btn btn-primary w-100">
                                <i class="fas fa-chart-bar me-2"></i> ดูรายงานเพิ่มเติม
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="table-container mt-3">
                    <h2><i class="fas fa-link me-2"></i> ลิงก์ด่วน</h2>
                    <div class="list-group">
                        <a href="student_add.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-plus me-2"></i> เพิ่มนักศึกษาใหม่
                        </a>
                        <a href="แผนการเรียน.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> แผนการเรียน
                        </a>
                        <a href="toeic_register.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit me-2"></i> ลงทะเบียนสอบ TOEIC
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-print me-2"></i> พิมพ์รายงาน
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Department Statistics -->
        <div class="row">
            <div class="col-12">
                <div class="section-header">
                    <h2 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> สถิตินักศึกษาแยกตามสาขา</h2>
                </div>
                <div class="table-container">
                    <div class="table-responsive">
                        <?php
                        // Get faculty and department statistics
                        try {
                            $department_stats_sql = "SELECT 
                                                f.faculty_name,
                                                f.thai_faculty_name,
                                                d.department_name,
                                                d.thai_department_name,
                                                COUNT(DISTINCT s.id_account) as student_count,
                                                SUM(CASE WHEN s.study_year = 1 THEN 1 ELSE 0 END) as year1,
                                                SUM(CASE WHEN s.study_year = 2 THEN 1 ELSE 0 END) as year2,
                                                SUM(CASE WHEN s.study_year = 3 THEN 1 ELSE 0 END) as year3,
                                                SUM(CASE WHEN s.study_year = 4 THEN 1 ELSE 0 END) as year4
                                            FROM faculty f
                                            LEFT JOIN department d ON f.id = d.faculty_id
                                            LEFT JOIN major m ON d.department_id = m.department_id
                                            LEFT JOIN student_details s ON m.major_id = s.major_id
                                            GROUP BY f.id, d.department_id
                                            ORDER BY f.faculty_name, d.department_name";
                            $department_stats_stmt = $conn->prepare($department_stats_sql);
                            $department_stats_stmt->execute();
                            $department_stats = $department_stats_stmt->fetchAll();

                            if (count($department_stats) > 0):
                        ?>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>คณะ</th>
                                    <th>ภาควิชา</th>
                                    <th>ปี 1</th>
                                    <th>ปี 2</th>
                                    <th>ปี 3</th>
                                    <th>ปี 4</th>
                                    <th>รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($department_stats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['thai_faculty_name'] ?: $stat['faculty_name']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['thai_department_name'] ?: $stat['department_name']); ?></td>
                                    <td><?php echo $stat['year1'] ?: 0; ?></td>
                                    <td><?php echo $stat['year2'] ?: 0; ?></td>
                                    <td><?php echo $stat['year3'] ?: 0; ?></td>
                                    <td><?php echo $stat['year4'] ?: 0; ?></td>
                                    <td><strong><?php echo $stat['student_count'] ?: 0; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> ยังไม่มีข้อมูลสาขาและภาควิชาในระบบ
                        </div>
                        <?php endif; ?>
                        <?php } catch (PDOException $e) { ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> การดึงข้อมูลล้มเหลว: <?php echo $e->getMessage(); ?>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <a href="departments.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i> ดูข้อมูลสาขาทั้งหมด
                        </a>
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

        // Add card and table container hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const tableContainers = document.querySelectorAll('.table-container');
            
            tableContainers.forEach(container => {
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
    </script>
</body>
</html>