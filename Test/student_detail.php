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

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid student ID";
    exit();
}

$student_code = htmlspecialchars($_GET['id']);

try {
    // Fetch academic staff information
    $staff_sql = "SELECT a.*, up.* FROM account a 
            JOIN user_profiles up ON a.id_account = up.id_account
            WHERE a.id_account = ?";
            
    $staff_stmt = $conn->prepare($staff_sql);
    $staff_stmt->bindParam(1, $_SESSION['user_id']);
    $staff_stmt->execute();
    
    $user = $staff_stmt->fetch();

    // Fetch student information using the view
    $student_sql = "SELECT * FROM vw_student_info WHERE student_code = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(1, $student_code);
    $student_stmt->execute();
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo "Student not found";
        exit();
    }

    // Fetch student's course registrations
    $registration_sql = "SELECT * FROM vw_student_registrations WHERE student_code = ?";
    $registration_stmt = $conn->prepare($registration_sql);
    $registration_stmt->bindParam(1, $student_code);
    $registration_stmt->execute();
    $registrations = $registration_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch student's TOEIC information
    $toeic_sql = "SELECT * FROM vw_student_toeic WHERE student_code = ?";
    $toeic_stmt = $conn->prepare($toeic_sql);
    $toeic_stmt->bindParam(1, $student_code);
    $toeic_stmt->execute();
    $toeic_info = $toeic_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch student's transcript information
    $transcript_sql = "SELECT * FROM vw_student_transcript WHERE student_code = ?";
    $transcript_stmt = $conn->prepare($transcript_sql);
    $transcript_stmt->bindParam(1, $student_code);
    $transcript_stmt->execute();
    $transcript = $transcript_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching student details: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดนักศึกษา - Suan Dusit University</title>
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

        .table-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

        .btn-back {
            background-color: #6c757d;
            color: white;
            transition: background-color 0.3s;
        }

        .btn-back:hover {
            background-color: #545b62;
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
        <a href="academic_dashboard.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
        <a href="students.php" class="active"><i class="fas fa-user-graduate"></i> ข้อมูลนักศึกษา</a>
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
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
        <!-- Topbar -->
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ข้อมูลนักศึกษา</div>
            <div class="search-container ms-auto">
                <input type="text" class="form-control" placeholder="ค้นหาที่นี่" id="search-input">
                <button class="btn btn-light" id="search-btn"><i class="fas fa-search"></i></button>
            </div>
            <div class="user-info">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <div>
                    <strong><?php echo htmlspecialchars(($user['thai_first_name'] ? $user['thai_first_name'] . ' ' . $user['thai_last_name'] : $_SESSION['username'])); ?></strong>
                    <p class="m-0">ฝ่ายวิชาการ</p>
                </div>
            </div>
        </div>

        <!-- Student Details -->
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <img src="<?php 
                    $profile_image = isset($student['profile_image']) && !empty($student['profile_image']) 
                        ? $student['profile_image'] 
                        : 'https://randomuser.me/api/portraits/men/1.jpg'; 
                    echo htmlspecialchars($profile_image); 
                ?>" alt="รูปนักศึกษา" class="profile-image mb-3">
            </div>
            <div class="col-md-8">
                <div class="card-stats">
                    <h3 class="mb-4"><?php echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']); ?></h3>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>รหัสนักศึกษา:</strong> <?php echo htmlspecialchars($student['student_code']); ?></p>
                            <p><strong>สาขา:</strong> <?php echo htmlspecialchars($student['thai_major_name'] ?: 'ไม่ได้ระบุ'); ?></p>
                            <p><strong>คณะ:</strong> <?php echo htmlspecialchars($student['thai_faculty_name'] ?: 'ไม่ระบุ'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>สถานะ:</strong> <?php echo htmlspecialchars($student['student_status']); ?></p>
                            <p><strong>สถานะทางวิชาการ:</strong> <?php echo htmlspecialchars($student['academic_status']); ?></p>
                            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($student['email_account']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOEIC Section -->
        <div class="section-header">
            <i class="fas fa-language me-2"></i> ผลสอบ TOEIC
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="table-container">
                    <?php if ($toeic_info && $toeic_info['TOEIC_Score']): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <h5>คะแนนสอบ</h5>
                                <div class="progress">
                                    <?php 
                                    $toeic_score = $toeic_info['TOEIC_Score'];
                                    $percent = min(($toeic_score / 990) * 100, 100);
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $percent; ?>%" 
                                         aria-valuenow="<?php echo $toeic_score; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="990">
                                        <?php echo $toeic_score; ?>
                                    </div>
                                </div>
                                <small class="text-muted">จากคะแนนเต็ม 990 คะแนน</small>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>วันที่สอบ:</strong> <?php echo date('d/m/Y', strtotime($toeic_info['Test_Date'])); ?></p>
                                        <p><strong>สถานะการสอบ:</strong> <?php echo htmlspecialchars($toeic_info['Registration_Status']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>คะแนนก่อนสอบ:</strong> <?php echo $toeic_info['Pre_Test_Score'] ?: 'ไม่มีข้อมูล'; ?></p>
                                        <p><strong>คะแนนพัฒนาการ:</strong> <?php 
                                            if ($toeic_info['Pre_Test_Score'] && $toeic_info['TOEIC_Score']) {
                                                echo $toeic_info['TOEIC_Score'] - $toeic_info['Pre_Test_Score'];
                                            } else {
                                                echo 'ไม่มีข้อมูล';
                                            }
                                        ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> ยังไม่มีข้อมูลผลสอบ TOEIC
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Course Registrations Section -->
        <div class="section-header">
            <i class="fas fa-book me-2"></i> รายวิชาที่ลงทะเบียน
        </div>
        <div class="table-container mb-4">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>หน่วยกิต</th>
                            <th>ภาคเรียน</th>
                            <th>เกรด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registrations)): ?>
                            <?php foreach ($registrations as $registration): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registration['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['Credits']); ?></td>
                                    <td>ภาคเรียนที่ <?php echo htmlspecialchars($registration['Semester'] . ' ' . $registration['Academic_Year']); ?></td>
                                    <td><?php echo $registration['Grade'] ? htmlspecialchars($registration['Grade']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">ไม่มีรายวิชาที่ลงทะเบียน</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Academic Summary Section -->
        <div class="section-header">
            <i class="fas fa-graduation-cap me-2"></i> สรุปผลการศึกษา
        </div>
        <div class="table-container">
            <div class="row">
                <div class="col-md-6">
                    <h5>รายละเอียดหลักสูตร</h5>
                    <table class="table">
                        <tr>
                            <th>หลักสูตร</th>
                            <td><?php echo htmlspecialchars($transcript['Curriculum_Name'] ?: 'ไม่ระบุ'); ?></td>
                        </tr>
                        <tr>
                            <th>หน่วยกิตรวม</th>
                            <td><?php echo htmlspecialchars($transcript['total_credits'] ?: '0'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>ผลการเรียน</h5>
                    <table class="table">
                        <tr>
                            <th>เกรดเฉลี่ยสะสม</th>
                            <td><?php echo htmlspecialchars(number_format($transcript['gpa'] ?: 0, 2)); ?></td>
                        </tr>
                        <tr>
                            <th>สถานะทางวิชาการ</th>
                            <td><?php echo htmlspecialchars($student['academic_status']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="students.php" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left me-2"></i> กลับสู่รายชื่อนักศึกษา
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

        // Search functionality (placeholder)
        document.getElementById('search-btn').addEventListener('click', function() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            // Implement search logic here if needed
            console.log('Searching for:', searchInput);
        });
    </script>
</body>
</html>                                        
            <div>
              <p><strong>สถานะการสอบ:</strong> 
              
              <?php echo htmlspecialchars($toeic_info['Registration_Status']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>คะแนนก่อนสอบ:</strong> <?php echo $toeic_info['Pre_Test_Score'] ?: 'ไม่มีข้อมูล'; ?></p>
                                        <p><strong>คะแนนพัฒนาการ:</strong> <?php 
                                            if ($toeic_info['Pre_Test_Score'] && $toeic_info['TOEIC_Score']) {
                                                echo $toeic_info['TOEIC_Score'] - $toeic_info['Pre_Test_Score'];
                                            } else {
                                                echo 'ไม่มีข้อมูล';
                                            }
                                        ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        
</body>
</html>