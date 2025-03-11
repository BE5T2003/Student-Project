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
    
    // Get TOEIC statistics
    $toeic_stats_sql = "SELECT 
                        MIN(TOEIC_Score) as min_score,
                        MAX(TOEIC_Score) as max_score,
                        AVG(TOEIC_Score) as avg_score,
                        COUNT(*) as total_tests,
                        SUM(CASE WHEN TOEIC_Score < 350 THEN 1 ELSE 0 END) as range1_count,
                        SUM(CASE WHEN TOEIC_Score >= 350 AND TOEIC_Score < 500 THEN 1 ELSE 0 END) as range2_count,
                        SUM(CASE WHEN TOEIC_Score >= 500 THEN 1 ELSE 0 END) as range3_count
                    FROM toeic";
    $toeic_stats_stmt = $conn->prepare($toeic_stats_sql);
    $toeic_stats_stmt->execute();
    $toeic_stats = $toeic_stats_stmt->fetch();
    
    // Get student TOEIC data with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // Records per page
    $offset = ($page - 1) * $limit;
    
    // Prepare search filter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where_clause = '';
    $params = [];
    
    if (!empty($search)) {
        $where_clause = " WHERE s.student_code LIKE ? 
                          OR up.first_name LIKE ? 
                          OR up.last_name LIKE ? 
                          OR up.thai_first_name LIKE ? 
                          OR up.thai_last_name LIKE ?
                          OR m.thai_major_name LIKE ?";
        $search_param = "%$search%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $search_param;
        }
    }
    
    // Count total records for pagination
    $count_sql = "SELECT COUNT(*) as total FROM vw_student_toeic" . $where_clause;
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $count_stmt->bindParam($i + 1, $params[$i]);
        }
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Get TOEIC data
    $toeic_sql = "SELECT * FROM vw_student_toeic" . $where_clause . " ORDER BY TOEIC_Score DESC LIMIT $limit OFFSET $offset";
    $toeic_stmt = $conn->prepare($toeic_sql);
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $toeic_stmt->bindParam($i + 1, $params[$i]);
        }
    }
    $toeic_stmt->execute();
    $toeic_data = $toeic_stmt->fetchAll();
    
    // Get major list for filter
    $majors_sql = "SELECT major_id, major_code, thai_major_name, major_name 
                  FROM major 
                  ORDER BY thai_major_name";
    $majors_stmt = $conn->prepare($majors_sql);
    $majors_stmt->execute();
    $majors = $majors_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}

// Function to get score range
function getScoreRange($score) {
    if ($score >= 500) return "3";
    else if ($score >= 350) return "2";
    else return "1";
}

// Function to get score range color
function getScoreRangeColor($score) {
    if ($score >= 500) return "#28a745"; // Green
    else if ($score >= 350) return "#ffc107"; // Yellow
    else return "#dc3545"; // Red
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลผลสอบ TOEIC - มหาวิทยาลัยสวนดุสิต</title>
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

        .toeic-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow-x: auto;
        }

        .toeic-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .toeic-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .toeic-table thead tr {
            background-color: #f2f7fd;
        }
        
        .toeic-table th {
            color: #3871c1;
            font-weight: 600;
            text-align: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-bottom: 2px solid #3871c1;
        }
        
        .toeic-table tbody tr {
            border-bottom: 1px solid #dddddd;
            transition: background-color 0.2s;
        }
        
        .toeic-table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }
        
        .toeic-table tbody tr:hover {
            background-color: #f0f7fb;
        }
        
        .toeic-table td {
            padding: 15px;
            vertical-align: middle;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .score-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            color: white;
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

        .level-chart {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .level-chart:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .level-chart h5 {
            color: #3871c1;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }

        .level-container {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .level-item {
            text-align: center;
            width: 30%;
            transition: transform 0.2s;
        }

        .level-item:hover {
            transform: translateY(-5px);
        }

        .level-badge {
            display: block;
            margin: 0 auto;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            line-height: 60px;
            font-weight: bold;
            color: white;
            font-size: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .level-badge:hover {
            transform: scale(1.1);
        }

        .level-description {
            font-size: 14px;
            color: #6c757d;
            margin-top: 8px;
        }

        .progress {
            height: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        .range-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: white;
            margin-right: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 8px;
            margin: 0 5px;
            transition: all 0.3s;
            color: #3871c1;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .pagination a:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .pagination .active {
            background-color: #3871c1;
            color: white;
            border-color: #3871c1;
        }

        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
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
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="majors.php"><i class="fas fa-graduation-cap"></i> สาขา/หลักสูตร</a>
        <a href="แผนการเรียน.php"><i class="fas fa-calendar-check"></i> แผนการเรียน</a>
        <a href="schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php" class="active"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ข้อมูลผลสอบ TOEIC</div>
            <div class="search-container">
                <form method="GET" action="" class="d-flex">
                    <input type="text" name="search" id="searchInput" class="form-control" 
                           placeholder="ค้นหาที่นี่" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-light"><i class="fas fa-search"></i></button>
                </form>
            </div>
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
            <h2 class="mb-0"><i class="fas fa-language me-2"></i> ระบบจัดการข้อมูล TOEIC</h2>
        </div>

        <!-- TOEIC Statistics -->
        <div class="row course-stats">
            <div class="col-md-3">
                <div class="card-stats">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="icon bg-success-light">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <h3><?php echo $toeic_stats['max_score'] ?: 0; ?></h3>
                            <p>คะแนนสูงสุด</p>
                        </div>
                        <div class="align-self-center">
                            <a href="toeic.php?sort=max" class="btn btn-sm btn-outline-success rounded-circle">
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
                                <i class="fas fa-calculator"></i>
                            </div>
                            <h3><?php echo round($toeic_stats['avg_score'] ?: 0); ?></h3>
                            <p>คะแนนเฉลี่ย</p>
                        </div>
                        <div class="align-self-center">
                            <a href="toeic_analytics.php" class="btn btn-sm btn-outline-info rounded-circle">
                                <i class="fas fa-chart-line"></i>
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
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <h3><?php echo $toeic_stats['min_score'] ?: 0; ?></h3>
                            <p>คะแนนต่ำสุด</p>
                        </div>
                        <div class="align-self-center">
                            <a href="toeic.php?sort=min" class="btn btn-sm btn-outline-warning rounded-circle">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Score Ranges -->
        <div class="level-chart">
            <h5><i class="fas fa-chart-bar me-2"></i> คะแนนที่ได้</h5>
            <div class="level-container">
                <div class="level-item">
                    <div class="level-badge" style="background-color: #dc3545;">1</div>
                    <strong>ต่ำกว่า 350</strong>
                    <div class="level-description">0-349 คะแนน</div>
                </div>
                <div class="level-item">
                    <div class="level-badge" style="background-color: #ffc107;">2</div>
                    <strong>ปานกลาง</strong>
                    <div class="level-description">350-499 คะแนน</div>
                </div>
                <div class="level-item">
                    <div class="level-badge" style="background-color: #28a745;">3</div>
                    <strong>สูงกว่า 500</strong>
                    <div class="level-description">500-990 คะแนน</div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6 class="text-secondary mb-3">ภาพรวมคะแนน TOEIC ของนักศึกษา</h6>
                <?php
                // Calculate percentages for progress bar
                $total = $toeic_stats['total_tests'] ?: 1; // Avoid division by zero
                $range1_percent = round(($toeic_stats['range1_count'] / $total) * 100);
                $range2_percent = round(($toeic_stats['range2_count'] / $total) * 100);
                $range3_percent = round(($toeic_stats['range3_count'] / $total) * 100);
                ?>
                <div class="progress">
                    <div class="progress-bar bg-danger" style="width: <?php echo $range1_percent; ?>%" title="ช่วงที่ 1: <?php echo $range1_percent; ?>%"></div>
                    <div class="progress-bar bg-warning" style="width: <?php echo $range2_percent; ?>%" title="ช่วงที่ 2: <?php echo $range2_percent; ?>%"></div>
                    <div class="progress-bar bg-success" style="width: <?php echo $range3_percent; ?>%" title="ช่วงที่ 3: <?php echo $range3_percent; ?>%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 text-muted">
                    <small>ต่ำกว่า 350: <?php echo $toeic_stats['range1_count']; ?> คน (<?php echo $range1_percent; ?>%)</small>
                    <small>350-499: <?php echo $toeic_stats['range2_count']; ?> คน (<?php echo $range2_percent; ?>%)</small>
                    <small>500 ขึ้นไป: <?php echo $toeic_stats['range3_count']; ?> คน (<?php echo $range3_percent; ?>%)</small>
                </div>
            </div>
        </div>

        <!-- Filter and Search Container -->
        <div class="filter-container">
            <div class="filter-group">
                <label for="majorFilter"><i class="fas fa-filter me-2"></i> กรองตามสาขา:</label>
                <select id="majorFilter" class="form-select">
                    <option value="all" selected>ทุกสาขา</option>
                    <?php foreach ($majors as $major): ?>
                    <option value="<?php echo $major['major_id']; ?>">
                        <?php echo htmlspecialchars($major['thai_major_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <form action="" method="GET" class="search-group">
                <input type="text" name="search" id="searchInput" placeholder="ค้นหารหัสนักศึกษา/ชื่อ" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" id="searchButton" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- TOEIC Data Table -->
        <div class="toeic-container">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="text-primary"><i class="fas fa-clipboard-list me-2"></i> ข้อมูลผลสอบ TOEIC</h5>
                <a href="toeic_add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> เพิ่มผลสอบใหม่
                </a>
            </div>
            <div class="table-responsive">
                <table class="toeic-table" id="toeicTable">
                    <thead>
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>สาขาวิชา</th>
                            <th>คะแนน Pre-Test</th>
                            <th>คะแนนสอบ TOEIC</th>
                            <th>ช่วงคะแนน</th>
                            <th>วันที่สอบ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($toeic_data)): ?>
                            <?php foreach ($toeic_data as $toeic): ?>
                                <?php 
                                    $score = $toeic['TOEIC_Score'] ?: 0;
                                    $range = getScoreRange($score);
                                    $rangeColor = getScoreRangeColor($score);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($toeic['student_code']); ?></td>
                                    <td class="text-start">
                                        <?php if (!empty($toeic['thai_student_name'])): ?>
                                            <?php echo htmlspecialchars($toeic['thai_student_name']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($toeic['student_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($toeic['thai_major_name'] ?: $toeic['major_name'] ?: 'ไม่ระบุ'); ?></td>
                                    <td><?php echo htmlspecialchars($toeic['Pre_Test_Score'] ?: '-'); ?></td>
                                    <td>
                                        <span class="score-badge" style="background-color: <?php echo $rangeColor; ?>">
                                            <?php echo htmlspecialchars($score); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="range-badge" style="background-color: <?php echo $rangeColor; ?>">
                                            <?php echo htmlspecialchars($range); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $toeic['Test_Date'] ? date('d/m/Y', strtotime($toeic['Test_Date'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($toeic['Registration_Status'] === 'registered'): ?>
                                            <span class="badge bg-success">ลงทะเบียนแล้ว</span>
                                        <?php elseif ($toeic['Registration_Status'] === 'pending'): ?>
                                            <span class="badge bg-warning">รอยืนยัน</span>
                                        <?php elseif ($toeic['Registration_Status'] === 'completed'): ?>
                                            <span class="badge bg-primary">สอบเสร็จสิ้น</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ไม่ระบุ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-language"></i>
                                    <p>ไม่พบข้อมูลผลสอบ TOEIC</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">&laquo; หน้าแรก</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">&lsaquo; ก่อนหน้า</a>
                <?php else: ?>
                    <span class="disabled">&laquo; หน้าแรก</span>
                    <span class="disabled">&lsaquo; ก่อนหน้า</span>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($start_page + 4, $total_pages);
                if ($end_page - $start_page < 4 && $start_page > 1) {
                    $start_page = max(1, $end_page - 4);
                }

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">ถัดไป &rsaquo;</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">หน้าสุดท้าย &raquo;</a>
                <?php else: ?>
                    <span class="disabled">ถัดไป &rsaquo;</span>
                    <span class="disabled">หน้าสุดท้าย &raquo;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- TOEIC Score Distribution -->
        <div class="level-chart mt-4">
            <h5><i class="fas fa-chart-pie me-2"></i> กราฟแสดงการกระจายคะแนน TOEIC</h5>
            <div id="scoreDistribution" style="width: 100%; height: 300px;"></div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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

        // Major filter functionality
        document.getElementById("majorFilter").addEventListener("change", function() {
            let selectedMajor = this.value;
            let rows = document.querySelectorAll("#toeicTable tbody tr");

            if (selectedMajor === "all") {
                rows.forEach(row => {
                    row.style.display = "";
                });
            } else {
                // This is a placeholder for client-side filtering - in a real implementation,
                // you would submit the form to server or use AJAX to get filtered data
                alert("กรุณาเพิ่มฟังก์ชันกรองตามสาขาวิชา");
            }
        });

        // Add container hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const containers = document.querySelectorAll('.toeic-container, .level-chart, .filter-container');
            
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

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);

        // Score distribution chart
        document.addEventListener('DOMContentLoaded', function() {
            var options = {
                series: [{
                    name: 'จำนวนนักศึกษา',
                    data: [
                        <?php echo $toeic_stats['range1_count'] ?: 0; ?>,
                        <?php echo $toeic_stats['range2_count'] ?: 0; ?>,
                        <?php echo $toeic_stats['range3_count'] ?: 0; ?>
                    ]
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                colors: ['#dc3545', '#ffc107', '#28a745'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '70%',
                        distributed: true,
                        borderRadius: 6,
                        dataLabels: {
                            position: 'top'
                        }
                    },
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + " คน";
                    },
                    style: {
                        fontSize: '12px',
                        colors: ['#333']
                    }
                },
                legend: {
                    show: false
                },
                xaxis: {
                    categories: ['ต่ำกว่า 350', '350-499', '500 ขึ้นไป'],
                    labels: {
                        style: {
                            fontSize: '14px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'จำนวนนักศึกษา (คน)'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " คน";
                        }
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#scoreDistribution"), options);
            chart.render();
        });
    </script>
</body>