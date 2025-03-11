<?php
// Initialize session
session_start();

// Check if user is logged in and has academic role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'academic') {
    // Clear session and redirect to login
    session_unset();
    session_destroy();
    header("Location: index.php?error=โปรดเข้าสู่ระบบก่อนใช้งาน");
    exit();
}

// Include database connection
require_once 'db_connect.php';

try {
    // Get academic staff information
    $academic_sql = "SELECT a.username_account, a.email_account, 
                    p.first_name, p.last_name, p.thai_first_name, p.thai_last_name, 
                    p.phone, p.address, p.profile_image
                    FROM account a
                    JOIN user_profiles p ON a.id_account = p.id_account
                    WHERE a.id_account = ?";
    $academic_stmt = $conn->prepare($academic_sql);
    $academic_stmt->bindParam(1, $_SESSION['user_id']);
    $academic_stmt->execute();
    $academic = $academic_stmt->fetch();
    
    // Get course information
    $course_sql = "SELECT c.Course_Code, c.Course_Name, c.Credits, 
                   ct.Course_Type_ID,
                   (SELECT COUNT(*) FROM course_sections cs WHERE cs.Course_Code = c.Course_Code) as section_count,
                   (SELECT COUNT(*) FROM major_courses mc WHERE mc.Course_Code = c.Course_Code) as plan_count
                   FROM course c
                   LEFT JOIN course_type ct ON c.Course_Code = ct.Course_Code
                   ORDER BY c.Course_Code";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->execute();
    $courses = $course_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}

// Function to get course type name
function getCourseTypeName($typeID) {
    switch ($typeID) {
        case '1':
            return "วิชาทั่วไป";
        case '2':
            return "วิชาเฉพาะ";
        case '3':
            return "วิชาเสรี";
        default:
            return "ไม่ระบุ";
    }
}

// Function to format credits display
function formatCredits($credits) {
    // This is a simple example - adjust based on your actual credits format
    return $credits . '(3-0-6)';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายวิชาทั้งหมด - มหาวิทยาลัยสวนดุสิต</title>
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
            background-color: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }

        .filter-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .filter-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .filter-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 500;
            color: #3871c1;
            margin-bottom: 0;
        }
        
        .filter-group select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-group select:hover,
        .filter-group select:focus {
            border-color: #3871c1;
            box-shadow: 0 2px 6px rgba(56, 113, 193, 0.2);
        }

        .search-group {
            display: flex;
            align-items: center;
        }

        .search-group input {
            padding: 10px 15px;
            border-radius: 8px 0 0 8px;
            border: 1px solid #ddd;
            border-right: none;
            width: 250px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .search-group input:hover,
        .search-group input:focus {
            border-color: #3871c1;
            box-shadow: 0 2px 6px rgba(56, 113, 193, 0.2);
        }

        .search-group button {
            padding: 10px 15px;
            border-radius: 0 8px 8px 0;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-group button:hover {
            background-color: #3871c1;
            color: white;
            border-color: #3871c1;
        }

        .course-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow-x: auto;
        }

        .course-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .course-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .course-table thead tr {
            background-color: #f2f7fd;
        }
        
        .course-table th {
            color: #3871c1;
            font-weight: 600;
            text-align: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-bottom: 2px solid #3871c1;
        }
        
        .course-table tbody tr {
            border-bottom: 1px solid #dddddd;
            transition: background-color 0.2s;
        }
        
        .course-table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }
        
        .course-table tbody tr:hover {
            background-color: #f0f7fb;
        }
        
        .course-table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }
        
        .sub-title {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .btn-details {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .btn-details:hover {
            background-color: #138496;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        .course-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            color: white;
        }

        .badge-general {
            background-color: #0d6efd;
        }

        .badge-specialized {
            background-color: #198754;
        }

        .badge-elective {
            background-color: #fd7e14;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            gap: 15px;
        }

        .action-button {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .action-button i {
            font-size: 16px;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
        }

        .btn-add:hover {
            background-color: #218838;
            color: white;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background-color: #138496;
            color: white;
        }
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        .highlight {
            background-color: #fff3cd !important;
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
            .filter-container {
                flex-direction: column;
                align-items: flex-start;
            }
            .filter-group, .search-group {
                width: 100%;
            }
            .filter-group select, .search-group input {
                width: 100%;
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
        <a href="courses.php" class="active"><i class="fas fa-book"></i> รายวิชา</a>
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
            <div class="dashboard-title">ข้อมูลรายวิชา</div>
            <div class="user-info">
                <?php if (!empty($academic['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($academic['profile_image']); ?>" alt="User Profile">
                <?php else: ?>
                    <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <?php endif; ?>
                <div>
                    <strong>
                        <?php 
                        if (!empty($academic['thai_first_name']) && !empty($academic['thai_last_name'])) {
                            echo htmlspecialchars($academic['thai_first_name'] . ' ' . $academic['thai_last_name']);
                        } elseif (!empty($academic['first_name']) && !empty($academic['last_name'])) {
                            echo htmlspecialchars($academic['first_name'] . ' ' . $academic['last_name']);
                        } else {
                            echo htmlspecialchars($academic['username_account']);
                        }
                        ?>
                    </strong>
                    <p class="m-0">ฝ่ายวิชาการ</p>
                </div>
            </div>
        </div>

        <!-- แสดงข้อความหลังการทำงาน -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Section Header -->
        <div class="section-header mb-4">
            <h2 class="mb-0"><i class="fas fa-book me-2"></i> ระบบจัดการรายวิชา</h2>
        </div>

        <!-- Course Statistics -->
        <div class="row course-stats">
            <div class="col-md-3">
                <div class="card-stats">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-primary-light">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3><?php 
                                $total_courses = count($courses);
                                echo $total_courses;
                            ?></h3>
                            <p>รายวิชาทั้งหมด</p>
                        </div>
                        <div class="align-self-center">
                            <a href="courses.php" class="btn btn-sm btn-outline-primary rounded-circle">
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
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3><?php 
                                $general_courses = 0;
                                $specialized_courses = 0;
                                $elective_courses = 0;
                                
                                foreach ($courses as $course) {
                                    if ($course['Course_Type_ID'] == '1') $general_courses++;
                                    else if ($course['Course_Type_ID'] == '2') $specialized_courses++;
                                    else if ($course['Course_Type_ID'] == '3') $elective_courses++;
                                }
                                
                                echo $general_courses;
                            ?></h3>
                            <p>วิชาทั่วไป</p>
                        </div>
                        <div class="align-self-center">
                            <a href="courses.php?type=1" class="btn btn-sm btn-outline-success rounded-circle">
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
                                <i class="fas fa-code"></i>
                            </div>
                            <h3><?php echo $specialized_courses; ?></h3>
                            <p>วิชาเฉพาะ</p>
                        </div>
                        <div class="align-self-center">
                            <a href="courses.php?type=2" class="btn btn-sm btn-outline-info rounded-circle">
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
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <h3><?php echo $elective_courses; ?></h3>
                            <p>วิชาเลือกเสรี</p>
                        </div>
                        <div class="align-self-center">
                            <a href="courses.php?type=3" class="btn btn-sm btn-outline-warning rounded-circle">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="course_add.php" class="action-button btn-add">
                <i class="fas fa-plus"></i> เพิ่มรายวิชาใหม่
            </a>
            <a href="แผนการเรียน.php" class="action-button btn-view">
                <i class="fas fa-calendar-check"></i> ดูแผนการเรียน
            </a>
        </div>

        <!-- Filter and Search Container -->
        <div class="filter-container">
            <div class="filter-group">
                <label for="categoryFilter"><i class="fas fa-filter me-2"></i> กรองตามหมวดวิชา:</label>
                <select id="categoryFilter" class="form-select">
                    <option value="all" selected>ทุกหมวดวิชา</option>
                    <option value="1">วิชาทั่วไป</option>
                    <option value="2">วิชาเฉพาะ</option>
                    <option value="3">วิชาเสรี</option>
                </select>
            </div>
            
            <div class="search-group">
                <input type="text" id="searchInput" class="form-control" placeholder="ค้นหารหัสวิชา/ชื่อวิชา">
                <button id="searchButton" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <!-- Course Table -->
        <div class="course-container">
            <table class="course-table" id="courseTable">
                <thead>
                    <tr>
                        <th style="width: 15%">รหัสวิชา</th>
                        <th style="width: 45%">ชื่อรายวิชา</th>
                        <th style="width: 15%">หน่วยกิต</th>
                        <th style="width: 15%">หมวดวิชา</th>
                        <th style="width: 10%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <tr data-category="<?php echo htmlspecialchars($course['Course_Type_ID'] ?: 'none'); ?>">
                                <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($course['Course_Name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($course['Credits']); ?></td>
                                <td><?php 
                                    $typeID = $course['Course_Type_ID'];
                                    $typeName = getCourseTypeName($typeID);
                                    $badgeClass = "";
                                    
                                    if ($typeID == '1') $badgeClass = "badge-general";
                                    else if ($typeID == '2') $badgeClass = "badge-specialized";
                                    else if ($typeID == '3') $badgeClass = "badge-elective";
                                    
                                    echo '<span class="course-badge ' . $badgeClass . '">' . htmlspecialchars($typeName) . '</span>';
                                ?></td>
                                <td>
                                    <a href="course_details.php?id=<?php echo htmlspecialchars($course['Course_Code']); ?>" class="btn-details">
                                        <i class="fas fa-info-circle"></i> รายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>ไม่พบข้อมูลรายวิชา</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
            const containers = document.querySelectorAll('.course-container, .filter-container');
            
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

        // Category filter functionality
        document.getElementById("categoryFilter").addEventListener("change", function() {
            filterAndSearch();
        });

        // Search functionality
        document.getElementById("searchButton").addEventListener("click", function() {
            filterAndSearch();
        });

        // Search on Enter key
        document.getElementById("searchInput").addEventListener("keyup", function(e) {
            if (e.key === "Enter") {
                filterAndSearch();
            }
        });

        // Function to handle both filtering and searching
        function filterAndSearch() {
            const selectedCategory = document.getElementById("categoryFilter").value;
            const searchValue = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#courseTable tbody tr");
            
            let noMatchFound = true;
            
            rows.forEach(row => {
                if (row.classList.contains('empty-search-row')) {
                    row.remove();
                    return;
                }
                
                const rowCategory = row.getAttribute("data-category");
                const courseCode = row.cells[0].textContent.toLowerCase();
                const courseName = row.cells[1].textContent.toLowerCase();
                
                // Check if row matches category filter
                const categoryMatch = selectedCategory === "all" || rowCategory === selectedCategory;
                
                // Check if row matches search filter
                const searchMatch = !searchValue || 
                                    courseCode.includes(searchValue) || 
                                    courseName.includes(searchValue);
                
                // Show row only if it matches both filters
                if (categoryMatch && searchMatch) {
                    row.style.display = "";
                    row.classList.remove("highlight");
                    
                    // If there's a search term, highlight the row
                    if (searchValue && (courseCode.includes(searchValue) || courseName.includes(searchValue))) {
                        row.classList.add("highlight");
                    }
                    
                    noMatchFound = false;
                } else {
                    row.style.display = "none";
                }
            });
            
            // Show "no results" row if no matches found
            const tableBody = document.querySelector("#courseTable tbody");
            const emptyRows = document.querySelectorAll(".empty-search-row");
            
            emptyRows.forEach(row => row.remove());
            
            if (noMatchFound) {
                const newRow = document.createElement("tr");
                newRow.className = "empty-search-row";
                newRow.innerHTML = `
                    <td colspan="5" class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>ไม่พบรายวิชาที่ตรงกับการค้นหา</p>
                    </td>
                `;
                tableBody.appendChild(newRow);
            }
        }

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