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

// Fetch user information
try {
    $stmt = $conn->prepare("SELECT up.* FROM user_profiles up WHERE up.id_account = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user information: " . $e->getMessage());
}

// Fetch Majors Information
try {
    $majors_sql = "
        SELECT 
            m.major_id,
            m.major_code,
            m.major_name,
            m.thai_major_name,
            d.department_name,
            d.thai_department_name,
            f.faculty_name,
            f.thai_faculty_name,
            p.program_name,
            p.thai_program_name,
            c.Curriculum_Name,
            (SELECT COUNT(sd.id_account) FROM student_details sd WHERE sd.major_id = m.major_id) as student_count
        FROM major m
        LEFT JOIN department d ON m.department_id = d.department_id
        LEFT JOIN faculty f ON d.faculty_id = f.id
        LEFT JOIN programs p ON m.program_id = p.program_id
        LEFT JOIN curriculum c ON m.Curriculum_ID = c.Curriculum_ID
    ";
    
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    if (!empty($search)) {
        $majors_sql .= " WHERE 
            m.major_code LIKE :search OR 
            m.major_name LIKE :search OR 
            m.thai_major_name LIKE :search OR
            d.department_name LIKE :search OR
            d.thai_department_name LIKE :search OR
            f.faculty_name LIKE :search OR
            f.thai_faculty_name LIKE :search
        ";
    }
    
    $stmt = $conn->prepare($majors_sql);
    
    if (!empty($search)) {
        $search_param = "%{$search}%";
        $stmt->bindParam(':search', $search_param);
    }
    
    $stmt->execute();
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching majors: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สาขา/หลักสูตร - Suan Dusit University</title>
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

        .major-table-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .major-table-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
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

        .table {
            width: 100%;
            border-collapse: collapse;
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

        .btn-study-plan {
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-study-plan:hover {
            background: #138496;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        .badge-count {
            background-color: #17a2b8;
            color: white;
            font-size: 14px;
            border-radius: 50px;
            padding: 6px 12px;
            font-weight: 500;
            display: inline-block;
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
        <a href="academic_dashboard.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
        <a href="students.php"><i class="fas fa-user-graduate"></i> ข้อมูลนักศึกษา</a>
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="majors.php" class="active"><i class="fas fa-graduation-cap"></i> สาขา/หลักสูตร</a>
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
            <div class="dashboard-title">สาขา/หลักสูตร</div>
            <div class="search-container">
                <form method="GET" action="" class="d-flex">
                    <input type="text" name="search" id="searchInput" class="form-control" 
                           placeholder="ค้นหาที่นี่" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-light"><i class="fas fa-search"></i></button>
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
            <h2 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> ข้อมูลสาขา/หลักสูตร</h2>
        </div>

        <!-- Major Table -->
        <div class="major-table-container">
            <div class="d-flex justify-content-end mb-3">
                <a href="major_add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>เพิ่มสาขาใหม่
                </a>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>รหัสสาขา</th>
                        <th>ชื่อสาขา</th>
                        <th>คณะ</th>
                        <th>ภาควิชา</th>
                        <th>หลักสูตร</th>
                        <th>จำนวนนักศึกษา</th>
                        <th>แผนการเรียน</th>
                    </tr>
                </thead>
                <tbody id="majorsTable">
                    <?php if (empty($majors)): ?>
                        <tr>
                            <td colspan="7" class="text-center">ไม่พบข้อมูลสาขา</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($majors as $major): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($major['major_code']); ?></td>
                                <td><?php echo htmlspecialchars($major['thai_major_name'] ?? $major['major_name']); ?></td>
                                <td><?php echo htmlspecialchars($major['thai_faculty_name'] ?? $major['faculty_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($major['thai_department_name'] ?? $major['department_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($major['Curriculum_Name'] ?? 'ไม่ระบุ'); ?></td>
                                <td>
                                    <span class="badge-count">
                                        <?php echo htmlspecialchars($major['student_count']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="แผนการเรียน.php?major=<?php echo $major['major_id']; ?>" class="btn btn-study-plan">
                                        <i class="fas fa-calendar-check me-1"></i> ดูแผนการเรียน
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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

        // Add table hover effect
        document.addEventListener('DOMContentLoaded', function() {
            const tableContainer = document.querySelector('.major-table-container');
            
            tableContainer.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.1)';
            });
            
            tableContainer.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.08)';
            });
        });
    </script>
</body>
</html>