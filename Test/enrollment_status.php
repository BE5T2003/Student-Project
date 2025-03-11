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
    // Fetch student information from the view
    $student_sql = "SELECT * FROM vw_student_info WHERE id_account = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(1, $_SESSION['user_id']);
    $student_stmt->execute();
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch student registrations from the view
    $registrations_sql = "SELECT * FROM vw_student_registrations 
                          WHERE id_account = ? 
                          ORDER BY Academic_Year DESC, Semester";
    $registrations_stmt = $conn->prepare($registrations_sql);
    $registrations_stmt->bindParam(1, $_SESSION['user_id']);
    $registrations_stmt->execute();
    $registrations = $registrations_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch student transcript information
    $transcript_sql = "SELECT * FROM vw_student_transcript 
                       WHERE id_account = ?";
    $transcript_stmt = $conn->prepare($transcript_sql);
    $transcript_stmt->bindParam(1, $_SESSION['user_id']);
    $transcript_stmt->execute();
    $transcript = $transcript_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะการลงทะเบียน - Suan Dusit University</title>
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

        /* Additional styles for enrollment page */
        .grade-badge {
            font-size: 0.9em;
            padding: 0.3em 0.6em;
        }

        .grade-A { background-color: #28a745; color: white; }
        .grade-B { background-color: #17a2b8; color: white; }
        .grade-C { background-color: #ffc107; color: black; }
        .grade-D { background-color: #dc3545; color: white; }
        .grade-F { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <!-- Sidebar (Same as student_dashboard.php) -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University">
            <h3>Suan Dusit University</h3>
        </div>
        <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> หน้าแรก</a>
        <a href="student_profile.php"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</a>
        <a href="class_schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="enrollment_status.php" class="active"><i class="fas fa-tasks"></i> ติดตามการลงทะเบียน</a>
        <a href="course_registration.php"><i class="fas fa-book"></i> ลงทะเบียนรายวิชา</a>
        <a href="my_grades.php"><i class="fas fa-chart-line"></i> ผลการเรียน</a>
        <a href="toeic_results.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ติดตามสถานะการลงทะเบียน</div>
            <div class="search-container ms-auto">
                <input type="text" class="form-control" placeholder="ค้นหาที่นี่" id="search-input">
                <button class="btn btn-light" id="search-btn"><i class="fas fa-search"></i></button>
            </div>
            <div class="user-info">
                <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'https://via.placeholder.com/40'; ?>" alt="User">
                <div>
                    <strong>
                        <?php 
                        echo !empty($student['thai_first_name']) && !empty($student['thai_last_name']) 
                            ? htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name'])
                            : (!empty($student['first_name']) && !empty($student['last_name']) 
                                ? htmlspecialchars($student['first_name'] . ' ' . $student['last_name'])
                                : htmlspecialchars($student['username_account']));
                        ?>
                    </strong>
                    <p class="m-0">นักศึกษา</p>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 class="mb-0"><i class="fas fa-tasks me-2"></i> สถานะการลงทะเบียน</h2>
        </div>

        <div class="row">
            <!-- Student Summary Card -->
            <div class="col-md-4">
                <div class="table-container">
                    <h2><i class="fas fa-user me-2"></i> ข้อมูลนักศึกษา</h2>
                    <div class="profile-info text-center">
                        <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'https://via.placeholder.com/120'; ?>" 
                             alt="Profile Picture" class="profile-image">
                        
                        <h4>
                            <?php 
                            echo !empty($student['thai_first_name']) && !empty($student['thai_last_name']) 
                                ? htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name'])
                                : (!empty($student['first_name']) && !empty($student['last_name']) 
                                    ? htmlspecialchars($student['first_name'] . ' ' . $student['last_name'])
                                    : htmlspecialchars($student['username_account']));
                            ?>
                        </h4>
                        <p class="mb-1"><strong>รหัสนักศึกษา:</strong> <?php echo htmlspecialchars($student['student_code']); ?></p>
                        <p class="mb-1"><strong>คณะ:</strong> <?php echo htmlspecialchars($student['thai_faculty_name'] ?? 'ไม่ระบุ'); ?></p>
                        <p class="mb-1"><strong>สาขา:</strong> <?php echo htmlspecialchars($student['thai_major_name'] ?? 'ไม่ระบุ'); ?></p>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3"><i class="fas fa-graduation-cap me-2"></i> สรุปการศึกษา</h5>
                        <p class="mb-2"><strong>เกรดเฉลี่ยสะสม (CGPA):</strong> 
                            <span class="badge bg-primary">
                                <?php echo number_format($transcript['gpa'] ?? 0, 2); ?>
                            </span>
                        </p>
                        <p class="mb-2"><strong>หน่วยกิตรวม:</strong> 
                            <?php echo $transcript['total_credits'] ?? 0; ?>
                        </p>
                        <p class="mb-2"><strong>หลักสูตร:</strong> 
                            <?php echo htmlspecialchars($student['Curriculum_Name'] ?? 'ไม่ระบุ'); ?>
                        </p>
                        <p class="mb-2"><strong>สถานะการศึกษา:</strong> 
                            <span class="badge bg-success">
                                <?php echo htmlspecialchars($student['student_status']); ?>
                            </span>
                        </p>
                        <p class="mb-2"><strong>สถานะวิชาการ:</strong> 
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($student['academic_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Enrollment Details -->
            <div class="col-md-8">
                <!-- Semester Summary -->
                <div class="table-container">
                    <h2><i class="fas fa-chart-pie me-2"></i> สรุปผลการลงทะเบียน</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-stats">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="icon bg-primary-light">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <h3><?php echo count($registrations); ?></h3>
                                        <p>รายวิชาที่ลงทะเบียน</p>
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
                                        <h3><?php echo number_format($transcript['gpa'] ?? 0, 2); ?></h3>
                                        <p>เกรดเฉลี่ยสะสม</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Details Table -->
                <div class="table-container">
                    <h2><i class="fas fa-book me-2"></i> รายละเอียดการลงทะเบียน</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered enrollment-table" id="enrollment-table">
                            <thead>
                                <tr>
                                    <th>ปีการศึกษา</th>
                                    <th>ภาคเรียน</th>
                                    <th>รหัสวิชา</th>
                                    <th>ชื่อวิชา</th>
                                    <th>หน่วยกิต</th>
                                    <th>เกรด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($registrations)): ?>
                                    <?php foreach ($registrations as $registration): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($registration['Academic_Year']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['Semester']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['Course_Code']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['Course_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['Credits']); ?></td>
                                        <td>
                                            <?php if ($registration['Grade']): ?>
                                                <span class="badge grade-badge grade-<?php echo $registration['Grade']; ?>">
                                                    <?php echo htmlspecialchars($registration['Grade']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">รอประกาศ</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">ยังไม่มีข้อมูลการลงทะเบียน</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar (same as student_dashboard.php)
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
        
        // Search functionality for enrollment table
        document.getElementById('search-input').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            const table = document.getElementById('enrollment-table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const courseId = rows[i].cells[2] ? rows[i].cells[2].textContent.toLowerCase() : '';
                const courseName = rows[i].cells[3] ? rows[i].cells[3].textContent.toLowerCase() : '';
                
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
</body>
</html>