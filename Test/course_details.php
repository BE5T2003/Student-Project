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

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: courses.php?error=กรุณาระบุรหัสวิชา");
    exit();
}

$course_id = $_GET['id'];

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
    $course_sql = "SELECT c.Course_Code, c.Course_Name, c.Credits, c.Instructor, c.Curriculum_ID,
                   ct.Course_Type_ID,
                   cur.Curriculum_Name
                   FROM course c
                   LEFT JOIN course_type ct ON c.Course_Code = ct.Course_Code
                   LEFT JOIN curriculum cur ON c.Curriculum_ID = cur.Curriculum_ID
                   WHERE c.Course_Code = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bindParam(1, $course_id);
    $course_stmt->execute();
    
    if ($course_stmt->rowCount() == 0) {
        header("Location: courses.php?error=ไม่พบข้อมูลรายวิชา");
        exit();
    }
    
    $course = $course_stmt->fetch();
    
    // Get course sections
    $sections_sql = "SELECT cs.section_id, cs.section_number, cs.instructor_name, 
                    cs.max_students, cs.current_students, cs.status,
                    s.name AS semester_name, s.thai_name AS semester_thai_name,
                    ay.year AS academic_year
                    FROM course_sections cs
                    JOIN semesters s ON cs.semester_id = s.semester_id
                    JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
                    WHERE cs.Course_Code = ?
                    ORDER BY cs.section_number";
    $sections_stmt = $conn->prepare($sections_sql);
    $sections_stmt->bindParam(1, $course_id);
    $sections_stmt->execute();
    $sections = $sections_stmt->fetchAll();
    
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

// Function to get section status class
function getSectionStatusClass($status) {
    switch ($status) {
        case 'active':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        case 'closed':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
}

// Function to get section status text
function getSectionStatusText($status) {
    switch ($status) {
        case 'active':
            return 'เปิดสอน';
        case 'cancelled':
            return 'ยกเลิก';
        case 'closed':
            return 'ปิดรับลงทะเบียน';
        default:
            return 'ไม่ระบุ';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ข้อมูลรายวิชา <?php echo htmlspecialchars($course['Course_Code']); ?> - มหาวิทยาลัยสวนดุสิต</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
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
        .content {
            margin-left: 290px;
            padding: 40px;
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
            object-fit: cover;
        }
        .course-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .course-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #43b3e5;
            padding-bottom: 10px;
            position: relative;
        }

        .course-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
            position: absolute;
            right: 10px;
            top: 10px;
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
        
        .course-header h2 {
            font-size: 1.8rem;
            color: #333;
        }
        
        .course-header .subtitle {
            font-size: 1.2rem;
            color: #666;
        }
        
        .course-details {
            margin-bottom: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #43b3e5;
        }
        
        .course-details .row {
            margin-bottom: 10px;
        }
        
        .course-details .label {
            font-weight: bold;
            color: #555;
        }

        .section-header {
            color: #ffffff;
            border: 2px solid #1377db;
            padding: 15px;
            background: linear-gradient(45deg, #3871c1, #3871d3);
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: bold;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .section-header:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .section-header i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .section-header:hover i {
            transform: scale(1.2);
        }

        .course-action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-bottom: 20px;
        }

        .course-action-buttons .btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background-color: #ffca2c;
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-add-section {
            background-color: #28a745;
            color: white;
        }

        .btn-add-section:hover {
            background-color: #2db854;
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .course-action-buttons .btn i {
            transition: transform 0.3s ease;
        }

        .course-action-buttons .btn:hover i {
            transform: scale(1.2);
        }
        
        .course-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .course-table thead tr {
            background-color: #43b3e5;
            color: white;
        }
        
        .course-table th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .course-table tbody tr {
            border-bottom: 1px solid #dddddd;
        }
        
        .course-table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }
        
        .course-table tbody tr:hover {
            background-color: #f0f7fb;
            transition: all 0.2s;
        }
        
        .course-table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
            display: inline-block;
        }
        
        .back-button-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
        }
        
        .back-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background-color: #5a6268;
            transform: scale(1.05);
            color: white;
        }

        .course-action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-add-section {
            background-color: #28a745;
        }

        .course-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            flex: 1;
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-box .number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-box .label {
            color: #6c757d;
            font-size: 14px;
        }

        .stat-box.active {
            border-bottom: 3px solid #28a745;
        }

        .stat-box.closed {
            border-bottom: 3px solid #ffc107;
        }

        .stat-box.cancelled {
            border-bottom: 3px solid #dc3545;
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
        <a href="schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle">☰</button>
            <div class="dashboard-title">ข้อมูลรายวิชา: <?php echo htmlspecialchars($course['Course_Code']); ?></div>
            <div class="search-container ms-auto">
                <input type="text" id="searchInput" placeholder="ค้นหาที่นี่">
                <button class="btn btn-light" id="searchButton">🔍</button>
            </div>
            <div class="user-info">
                <?php if (!empty($academic['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($academic['profile_image']); ?>" alt="User Profile">
                <?php else: ?>
                    <img src="https://via.placeholder.com/40" alt="User Profile">
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
            <div class="alert alert-success mt-3">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger mt-3">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <h2 class="mb-0"><i class="fas fa-book me-2"></i> ข้อมูลรายวิชา</h2>
        </div>

        <div class="course-action-buttons">
            <button class="btn btn-edit">
                <i class="fas fa-edit me-2"></i> แก้ไขรายวิชา
            </button>
            <button class="btn btn-add-section">
                <i class="fas fa-plus me-2"></i> เพิ่มตอนเรียน
            </button>
        </div>
        
        <div class="course-container mt-4">
            <div class="course-header">
                <h2><?php echo htmlspecialchars($course['Course_Name']); ?></h2>
                <div class="subtitle"><?php echo htmlspecialchars($course['Course_Code']); ?></div>
                <?php 
                    $typeID = $course['Course_Type_ID'];
                    $typeName = getCourseTypeName($typeID);
                    $badgeClass = "";
                    
                    if ($typeID == '1') $badgeClass = "badge-general";
                    else if ($typeID == '2') $badgeClass = "badge-specialized";
                    else if ($typeID == '3') $badgeClass = "badge-elective";
                    
                    echo '<span class="course-badge ' . $badgeClass . '">' . htmlspecialchars($typeName) . '</span>';
                ?>
            </div>
            
            <?php
                // Calculate section statistics
                $total_sections = count($sections);
                $active_sections = 0;
                $closed_sections = 0;
                $cancelled_sections = 0;
                $total_students = 0;
                $max_capacity = 0;

                foreach ($sections as $section) {
                    if ($section['status'] == 'active') $active_sections++;
                    else if ($section['status'] == 'closed') $closed_sections++;
                    else if ($section['status'] == 'cancelled') $cancelled_sections++;
                    
                    $total_students += $section['current_students'];
                    $max_capacity += $section['max_students'];
                }
            ?>

            <div class="course-stats">
                <div class="stat-box active">
                    <div class="number"><?php echo $active_sections; ?></div>
                    <div class="label">ตอนเรียนที่เปิดสอน</div>
                </div>
                <div class="stat-box closed">
                    <div class="number"><?php echo $closed_sections; ?></div>
                    <div class="label">ตอนเรียนที่ปิดรับ</div>
                </div>
                <div class="stat-box cancelled">
                    <div class="number"><?php echo $cancelled_sections; ?></div>
                    <div class="label">ตอนเรียนที่ยกเลิก</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $total_students; ?>/<?php echo $max_capacity; ?></div>
                    <div class="label">นักศึกษา/ความจุ</div>
                </div>
            </div>
            
            <div class="course-details">
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">รหัสวิชา</div>
                        <div><?php echo htmlspecialchars($course['Course_Code']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">ชื่อรายวิชา</div>
                        <div><?php echo htmlspecialchars($course['Course_Name']); ?></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">หน่วยกิต</div>
                        <div><?php echo htmlspecialchars($course['Credits']) . '(3-0-6)'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">หมวดวิชา</div>
                        <div><?php echo htmlspecialchars(getCourseTypeName($course['Course_Type_ID'])); ?></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">ผู้สอน</div>
                        <div><?php echo htmlspecialchars($course['Instructor']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">หลักสูตร</div>
                        <div><?php echo htmlspecialchars($course['Curriculum_Name'] ?? 'ไม่ระบุ'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="section-header mt-4">
                <h2 class="mb-0"><i class="fas fa-list me-2"></i> ตอนเรียน</h2>
            </div>
            
            <table class="course-table" id="sectionTable">
                <thead>
                    <tr>
                        <th>ตอนเรียน</th>
                        <th>ผู้สอน</th>
                        <th>ภาคการศึกษา</th>
                        <th>ปีการศึกษา</th>
                        <th>จำนวนที่รับ</th>
                        <th>จำนวนปัจจุบัน</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sections)): ?>
                        <?php foreach ($sections as $section): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($section['section_number']); ?></td>
                                <td><?php echo htmlspecialchars($section['instructor_name']); ?></td>
                                <td><?php echo htmlspecialchars($section['semester_thai_name']); ?></td>
                                <td><?php echo htmlspecialchars($section['academic_year']); ?></td>
                                <td><?php echo htmlspecialchars($section['max_students']); ?></td>
                                <td><?php echo htmlspecialchars($section['current_students']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getSectionStatusClass($section['status']); ?>">
                                        <?php echo htmlspecialchars(getSectionStatusText($section['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">ไม่พบข้อมูลตอนเรียน</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="back-button-container">
            <a href="courses.php" class="btn back-button">
                <i class="fas fa-arrow-left"></i> ย้อนกลับ
            </a>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById("menu-toggle").addEventListener("click", function() {
            let sidebar = document.getElementById("sidebar");
            let content = document.getElementById("content");
            sidebar.classList.toggle("hidden");
            content.classList.toggle("expanded");
        });

        // Search functionality
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll("#sectionTable tbody tr");

            rows.forEach(row => {
                let instructor = row.cells[1].textContent.toLowerCase();
                let sectionNumber = row.cells[0].textContent.toLowerCase();
                let semester = row.cells[2].textContent.toLowerCase();

                if (instructor.includes(searchValue) || sectionNumber.includes(searchValue) || semester.includes(searchValue)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

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