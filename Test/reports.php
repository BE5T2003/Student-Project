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

// Fetch Report Categories
try {
    $reports = [
        [
            'title' => 'รายงานนักศึกษา',
            'items' => [
                [
                    'name' => 'จำนวนนักศึกษาทั้งหมด',
                    'description' => 'รายงานจำนวนนักศึกษาแยกตามคณะ สาขา และชั้นปี',
                    'icon' => 'fas fa-users',
                    'href' => 'report_student_count.php'
                ],
                [
                    'name' => 'นักศึกษาใหม่',
                    'description' => 'รายงานนักศึกษาที่ลงทะเบียนในปีการศึกษาปัจจุบัน',
                    'icon' => 'fas fa-user-plus',
                    'href' => 'report_new_students.php'
                ],
                [
                    'name' => 'สถานะการศึกษา',
                    'description' => 'รายงานสถานะนักศึกษา (ปกติ, รอพินิจ, พ้นสภาพ)',
                    'icon' => 'fas fa-clipboard-list',
                    'href' => 'report_student_status.php'
                ]
            ]
        ],
        [
            'title' => 'รายงานวิชาเรียน',
            'items' => [
                [
                    'name' => 'รายวิชาเปิดสอน',
                    'description' => 'รายงานรายวิชาที่เปิดสอนในภาคการศึกษาปัจจุบัน',
                    'icon' => 'fas fa-book-open',
                    'href' => 'report_course_offerings.php'
                ],
                [
                    'name' => 'การลงทะเบียนรายวิชา',
                    'description' => 'รายงานการลงทะเบียนและจำนวนนักศึกษาในแต่ละรายวิชา',
                    'icon' => 'fas fa-clipboard-check',
                    'href' => 'report_course_registration.php'
                ],
                [
                    'name' => 'ผลการเรียน',
                    'description' => 'รายงานผลการเรียนของนักศึกษาแยกตามรายวิชา',
                    'icon' => 'fas fa-chart-bar',
                    'href' => 'report_course_grades.php'
                ]
            ]
        ],
        [
            'title' => 'รายงานพิเศษ',
            'items' => [
                [
                    'name' => 'TOEIC',
                    'description' => 'รายงานผลคะแนนสอบ TOEIC ของนักศึกษา',
                    'icon' => 'fas fa-language',
                    'href' => 'report_toeic.php'
                ],
                [
                    'name' => 'อาจารย์ที่ปรึกษา',
                    'description' => 'รายงานข้อมูลนักศึกษาแยกตามอาจารย์ที่ปรึกษา',
                    'icon' => 'fas fa-chalkboard-teacher',
                    'href' => 'report_advisors.php'
                ],
                [
                    'name' => 'การเข้าชั้นเรียน',
                    'description' => 'รายงานสถิติการเข้าชั้นเรียนของนักศึกษา',
                    'icon' => 'fas fa-calendar-check',
                    'href' => 'report_attendance.php'
                ]
            ]
        ]
    ];
} catch (Exception $e) {
    die("Error preparing reports: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - Suan Dusit University</title>
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

        .reports-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .reports-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .report-category {
            margin-bottom: 30px;
        }

        .report-category h2 {
            border-bottom: 2px solid #3871c1;
            padding-bottom: 12px;
            margin-bottom: 20px;
            color: #3871c1;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .report-item {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .report-item:hover {
            background-color: #e9ecef;
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .report-item .icon {
            background-color: #3871c1;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(56, 113, 193, 0.2);
            transition: transform 0.2s;
        }

        .report-item:hover .icon {
            transform: scale(1.1);
        }

        .report-item .details {
            flex-grow: 1;
        }

        .report-item .details h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        .report-item .details p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        .report-item .btn {
            background-color: #3871c1;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .report-item .btn:hover {
            background-color: #2a5ea8;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            .report-item {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }
            .report-item .icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
            .report-item .btn {
                margin-top: 15px;
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
        <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>
    
    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">รายงาน</div>
            <div class="search-container">
                <form class="d-flex">
                    <input type="text" id="searchReports" class="form-control" placeholder="ค้นหารายงาน">
                    <button type="button" class="btn btn-light"><i class="fas fa-search"></i></button>
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
            <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i> ระบบรายงานข้อมูลวิชาการ</h2>
        </div>

        <div class="reports-container">
            <?php foreach ($reports as $category): ?>
                <div class="report-category">
                    <h2><i class="fas fa-folder-open me-2"></i><?php echo htmlspecialchars($category['title']); ?></h2>
                    <div class="row">
                        <?php foreach ($category['items'] as $item): ?>
                            <div class="col-md-4 mb-3">
                                <div class="report-item">
                                    <div class="icon">
                                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                    </div>
                                    <div class="details">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($item['href']); ?>" class="btn">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
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

        // Add container hover effect
        document.addEventListener('DOMContentLoaded', function() {
            const reportsContainer = document.querySelector('.reports-container');
            
            reportsContainer.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.1)';
            });
            
            reportsContainer.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.08)';
            });
        });

        // Search reports functionality
        document.getElementById('searchReports').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const reportItems = document.querySelectorAll('.report-item');
            
            reportItems.forEach(item => {
                const name = item.querySelector('h3').textContent.toLowerCase();
                const description = item.querySelector('p').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                    item.closest('.col-md-4').style.display = 'block';
                } else {
                    item.closest('.col-md-4').style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>