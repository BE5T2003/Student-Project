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
    // Check if profile is completed
    $check_profile_sql = "SELECT * FROM user_profiles WHERE id_account = ? AND 
                         (first_name IS NOT NULL AND last_name IS NOT NULL AND 
                          thai_first_name IS NOT NULL AND thai_last_name IS NOT NULL)";
    $check_profile_stmt = $conn->prepare($check_profile_sql);
    $check_profile_stmt->bindParam(1, $_SESSION['user_id']);
    $check_profile_stmt->execute();
    
    // If profile is not completed, redirect to profile completion page
    if ($check_profile_stmt->rowCount() == 0) {
        header("Location: profile_completion.php");
        exit();
    }
    
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
    $courses_sql = "SELECT cr.Registration_ID, cr.Course_Code, cr.status, cr.Grade, 
                  c.Course_Name
                  FROM course_registration cr
                  JOIN course c ON cr.Course_Code = c.Course_Code
                  WHERE cr.Student_ID = ? 
                  ORDER BY cr.Course_Code";
    $courses_stmt = $conn->prepare($courses_sql);
    $courses_stmt->bindParam(1, $student['student_code']);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Suan Dusit University</title>
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

        .card-stats {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card-stats:hover {
            transform: translateY(-5px);
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

        .table th {
            background-color: #f1f1f1;
        }

        .profile-info {
            margin-bottom: 20px;
        }

        .profile-info .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #3871c1;
            object-fit: cover;
            margin: 0 auto 15px;
            display: block;
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
    </style>
</head>
<body>
     <!-- Sidebar -->
     <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University">
            <h3 class="sidebar-text">Suan Dusit University</h3>
        </div>
        <a href="student_dashboard.php" class="active">
            <i class="fas fa-tachometer-alt"></i> 
            <span class="sidebar-text">หน้าแรก</span>
        </a>
        <a href="student_profile.php">
            <i class="fas fa-user"></i> 
            <span class="sidebar-text">ข้อมูลส่วนตัว</span>
        </a>
        <a href="class_schedule.php">
            <i class="fas fa-calendar-alt"></i> 
            <span class="sidebar-text">ตารางเรียน</span>
        </a>
        <a href="enrollment_status.php">
            <i class="fas fa-tasks"></i> 
            <span class="sidebar-text">ติดตามการลงทะเบียน</span>
        </a>
        <a href="course_registration.php">
            <i class="fas fa-book"></i> 
            <span class="sidebar-text">ลงทะเบียนรายวิชา</span>
        </a>
        <a href="my_grades.php">
            <i class="fas fa-chart-line"></i> 
            <span class="sidebar-text">ผลการเรียน</span>
        </a>
        <a href="toeic_results.php">
            <i class="fas fa-language"></i> 
            <span class="sidebar-text">ผลสอบ TOEIC</span>
        </a>
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> 
            <span class="sidebar-text">ออกจากระบบ</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ระบบจัดการข้อมูลนักศึกษา</div>
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
            <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> แดชบอร์ดนักศึกษา</h2>
        </div>

        <!-- Dashboard Content -->
        <div class="row">
            <!-- Student Profile Card -->
            <div class="col-md-4">
                <div class="table-container">
                    <h2><i class="fas fa-user me-2"></i> ข้อมูลส่วนตัว</h2>
                    <div class="profile-info text-center">
                        <?php if (!empty($student['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Picture" class="profile-image">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/120" alt="Profile Picture" class="profile-image">
                        <?php endif; ?>
                        
                        <h4>
                            <?php 
                            if (!empty($student['thai_first_name']) && !empty($student['thai_last_name'])) {
                                echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']);
                            } elseif (!empty($student['first_name']) && !empty($student['last_name'])) {
                                echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                            } else {
                                echo htmlspecialchars($student['username_account']);
                            }
                            ?>
                        </h4>
                        <p class="mb-1"><strong>รหัสนักศึกษา:</strong> <?php echo htmlspecialchars($student['student_code']); ?></p>
                        <p class="mb-1"><strong>คณะ:</strong> <?php echo htmlspecialchars($student['thai_faculty_name'] ?? 'ไม่ระบุ'); ?></p>
                        <p class="mb-1"><strong>สาขา:</strong> <?php echo htmlspecialchars($student['thai_major_name'] ?? 'ไม่ระบุ'); ?></p>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i> รายละเอียดการศึกษา</h5>
                        <p class="mb-2"><strong>ชั้นปี:</strong> <?php echo htmlspecialchars($student['study_year']); ?></p>
                        <p class="mb-2"><strong>หลักสูตร:</strong> <?php echo htmlspecialchars($student['Curriculum_Name'] ?? 'ไม่ระบุ'); ?></p>
                        <p class="mb-2"><strong>ปีการศึกษาที่เข้า:</strong> <?php echo htmlspecialchars($student['entry_year']); ?></p>
                        <p class="mb-2"><strong>สถานะการศึกษา:</strong> 
                            <span class="badge bg-success"><?php echo htmlspecialchars($student['status']); ?></span>
                        </p>
                        <p class="mb-2"><strong>สถานะวิชาการ:</strong> 
                            <span class="badge bg-primary"><?php echo htmlspecialchars($student['academic_status']); ?></span>
                        </p>
                    </div>
                    
                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3"><i class="fas fa-address-card me-2"></i> ข้อมูลติดต่อ</h5>
                        <p class="mb-2"><strong>อีเมล:</strong> <?php echo htmlspecialchars($student['email_account']); ?></p>
                        <p class="mb-2"><strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'ไม่ระบุ'); ?></p>
                        <?php if (!empty($student['address'])): ?>
                        <p class="mb-2"><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($student['address']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="student_profile.php" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> แก้ไขข้อมูลส่วนตัว
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links Card -->
                <div class="table-container">
                    <h2><i class="fas fa-link me-2"></i> ลิงก์ด่วน</h2>
                    <div class="list-group">
                        <a href="class_schedule.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i> ตารางเรียน
                        </a>
                        <a href="course_registration.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i> ลงทะเบียนรายวิชา
                        </a>
                        <a href="my_grades.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-line me-2"></i> ผลการเรียน
                        </a>
                        <a href="toeic_results.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-language me-2"></i> ผลสอบ TOEIC
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Dashboard Content -->
            <div class="col-md-8">
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-stats">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="icon bg-primary-light">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <h3><?php echo count($courses) ?: 0; ?></h3>
                                    <p>วิชาที่ลงทะเบียน</p>
                                </div>
                                <div class="align-self-center">
                                    <a href="course_registration.php" class="btn btn-sm btn-light"><i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-stats">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="icon bg-success-light">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <h3>3.50</h3>
                                    <p>เกรดเฉลี่ยสะสม</p>
                                </div>
                                <div class="align-self-center">
                                    <a href="my_grades.php" class="btn btn-sm btn-light"><i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Table -->
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2><i class="fas fa-book me-2"></i> รายวิชาที่ลงทะเบียน</h2>
                        <a href="course_registration.php" class="btn btn-sm btn-primary">ลงทะเบียนเพิ่มเติม</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="courses-table">
                            <thead>
                                <tr>
                                    <th>รหัสวิชา</th>
                                    <th>ชื่อวิชา</th>
                                    <th>สถานะ</th>
                                    <th>เกรด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($courses)): ?>
                                    <?php foreach ($courses as $index => $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                        <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                        <td>
                                            <?php if ($course['status'] == 'registered'): ?>
                                                <span class="badge bg-success">ลงทะเบียนแล้ว</span>
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
                                        <td colspan="4" class="text-center">ยังไม่มีรายวิชาที่ลงทะเบียน</td>
                                    </tr>
                                    <!-- ตัวอย่างข้อมูล -->
                                    <tr>
                                        <td>1500202</td>
                                        <td>ความเป็นสวนดุสิต</td>
                                        <td><span class="badge bg-success">ลงทะเบียนแล้ว</span></td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>4000114</td>
                                        <td>จุดประกายความคิดเชิงธุรกิจ</td>
                                        <td><span class="badge bg-success">ลงทะเบียนแล้ว</span></td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>4000115</td>
                                        <td>วิธีการใช้ชีวิตในยุคดิจิทัล</td>
                                        <td><span class="badge bg-success">ลงทะเบียนแล้ว</span></td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>4121314</td>
                                        <td>โครงสร้างข้อมูลและอัลกอริทึม</td>
                                        <td><span class="badge bg-success">ลงทะเบียนแล้ว</span></td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>4121315</td>
                                        <td>ปฏิบัติการโครงสร้างข้อมูลและอัลกอริทึม</td>
                                        <td><span class="badge bg-warning">ถอนรายวิชา</span></td>
                                        <td>-</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Schedule Preview -->
                <div class="table-container">
                    <h2><i class="fas fa-calendar-alt me-2"></i> ตารางเรียนประจำสัปดาห์</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="bg-light">
                                    <th width="15%">เวลา/วัน</th>
                                    <th>จันทร์</th>
                                    <th>อังคาร</th>
                                    <th>พุธ</th>
                                    <th>พฤหัสบดี</th>
                                    <th>ศุกร์</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>08:00 - 10:00</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-primary text-white">1500202<br>ความเป็นสวนดุสิต</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-success text-white">4000114<br>จุดประกาย<br>ความคิดเชิงธุรกิจ</td>
                                    <td class="bg-light">-</td>
                                </tr>
                                <tr>
                                    <td>10:00 - 12:00</td>
                                    <td class="bg-warning">4121314<br>โครงสร้างข้อมูล<br>และอัลกอริทึม</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-info text-white">4000115<br>วิธีการใช้ชีวิต<br>ในยุคดิจิทัล</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-light">-</td>
                                </tr>
                                <tr>
                                    <td>13:00 - 15:00</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-danger text-white">4121315<br>ปฏิบัติการ<br>โครงสร้างข้อมูล</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-light">-</td>
                                    <td class="bg-light">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="class_schedule.php" class="btn btn-primary">
                            <i class="fas fa-calendar-alt me-2"></i> ดูตารางเรียนทั้งหมด
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const menuToggle = document.getElementById('menu-toggle');

            // Function to toggle sidebar
            function toggleSidebar() {
                // Toggle sidebar visibility
                sidebar.classList.toggle('sidebar-collapsed');
                
                // Adjust content margin based on sidebar state
                if (sidebar.classList.contains('sidebar-collapsed')) {
                    // Sidebar is collapsed
                    content.style.marginLeft = '0';
                    
                    // Hide sidebar content
                    sidebar.querySelectorAll('.sidebar-text').forEach(el => {
                        el.style.display = 'none';
                    });
                    
                    // Narrow sidebar width
                    sidebar.style.width = '60px';
                } else {
                    // Sidebar is expanded
                    content.style.marginLeft = '260px';
                    
                    // Show sidebar content
                    sidebar.querySelectorAll('.sidebar-text').forEach(el => {
                        el.style.display = 'inline';
                    });
                    
                    // Restore original sidebar width
                    sidebar.style.width = '260px';
                }
            }

            // Add click event to menu toggle
            menuToggle.addEventListener('click', toggleSidebar);

            // Initialize sidebar state (optional)
            const savedSidebarState = localStorage.getItem('sidebarCollapsed');
            if (savedSidebarState === 'true') {
                sidebar.classList.add('sidebar-collapsed');
                content.style.marginLeft = '0';
                sidebar.querySelectorAll('.sidebar-text').forEach(el => {
                    el.style.display = 'none';
                });
                sidebar.style.width = '60px';
            }
        });

        // Search functionality
        document.getElementById('search-input').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            const table = document.getElementById('courses-table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const courseId = rows[i].cells[0] ? rows[i].cells[0].textContent.toLowerCase() : '';
                const courseName = rows[i].cells[1] ? rows[i].cells[1].textContent.toLowerCase() : '';
                
                if (courseId.includes(input) || courseName.includes(input)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
        
        // Search button click
        document.getElementById('search-btn').addEventListener('click', function() {
            const input = document.getElementById('search-input');
            const event = new Event('keyup');
            input.dispatchEvent(event);
        });
    </script>
        
        // Search button click
        document.getElementById('search-btn').addEventListener('click', function() {
            const input = document.getElementById('search-input');
            const event = new Event('keyup');
            input.dispatchEvent(event);
        });