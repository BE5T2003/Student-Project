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

// Day Mapping
$dayMapping = [
    'monday' => 'วันจันทร์',
    'tuesday' => 'วันอังคาร', 
    'wednesday' => 'วันพุธ',
    'thursday' => 'วันพฤหัสบดี', 
    'friday' => 'วันศุกร์',
    'saturday' => 'วันเสาร์',
    'sunday' => 'วันอาทิตย์'
];

// Fetch Current Semester
function getCurrentSemester($conn) {
    $stmt = $conn->query("SELECT semester_id FROM semesters WHERE is_current = 1");
    return $stmt->fetch(PDO::FETCH_COLUMN);
}

// Fetch Course Schedules
function fetchCourseSchedules($conn, $semesterId) {
    $stmt = $conn->prepare("
        SELECT 
            c.Course_Code, 
            cs.section_number, 
            cs.instructor_name,
            cl.day_of_week,
            cl.start_time,
            cl.end_time,
            c.Course_Name,
            b.building_name,
            r.room_name
        FROM course_sections cs
        JOIN course c ON cs.Course_Code = c.Course_Code
        JOIN class_schedules cl ON cs.section_id = cl.section_id
        LEFT JOIN rooms r ON cl.room_id = r.room_id
        LEFT JOIN buildings b ON r.building_id = b.building_id
        WHERE cs.semester_id = ?
    ");
    $stmt->execute([$semesterId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Time to Slot Index Conversion
function timeToSlotIndex($time) {
    list($hours, $minutes) = explode(':', $time);
    $totalMinutes = $hours * 60 + $minutes;
    return floor(($totalMinutes - 480) / 60); // 8:00 AM is the start time
}

// Get current semester
$currentSemesterId = getCurrentSemester($conn);
$courseSchedules = fetchCourseSchedules($conn, $currentSemesterId);

// Prepare timetable data
$timeTableData = array_fill_keys(array_keys($dayMapping), 
    array_fill(0, 14, null)
);

// Populate timetable data
foreach ($courseSchedules as $course) {
    $startSlot = timeToSlotIndex($course['start_time']);
    $endSlot = timeToSlotIndex($course['end_time']);
    
    for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
        $timeTableData[$course['day_of_week']][$slot] = $course;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางเรียน - Suan Dusit University</title>
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

        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .control-button {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .control-button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .control-button i {
            font-size: 16px;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            min-width: 150px;
        }

        .filter-select:hover,
        .filter-select:focus {
            border-color: #3871c1;
            box-shadow: 0 2px 6px rgba(56, 113, 193, 0.2);
        }

        .timetable-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow-x: auto;
        }

        .timetable-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .timetable th {
            background-color: #f2f7fd;
            color: #3871c1;
            font-weight: 600;
            text-align: center;
            padding: 15px 10px;
            border: 1px solid #dee2e6;
            border-bottom: 2px solid #3871c1;
            white-space: nowrap;
        }

        .timetable td {
            vertical-align: top;
            border: 1px solid #dee2e6;
            height: 60px;
            padding: 5px;
            text-align: center;
            position: relative;
        }

        .timetable td.day {
            background-color: #f2f7fd;
            color: #3871c1;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            width: 100px;
        }

        .droppable {
            min-width: 100px;
            transition: background-color 0.3s;
        }

        .droppable:hover:empty {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .course {
            background-color: #e3f2fd;
            border-radius: 5px;
            padding: 5px;
            margin: 2px 0;
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: move;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .course:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .course-code {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .course-location {
            font-size: 10px;
            color: #666;
        }

        .course-sidebar {
            position: fixed;
            top: 100px;
            right: -300px;
            width: 300px;
            height: calc(100vh - 120px);
            background-color: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: right 0.3s;
            z-index: 900;
            overflow-y: auto;
            border-radius: 10px 0 0 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .course-sidebar.active {
            right: 0;
        }

        .course-sidebar h2 {
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #3871c1;
            text-align: center;
        }

        .course-template {
            background-color: #f8f9fa;
            border: 1px dashed #aaa;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: grab;
            transition: all 0.2s;
        }

        .course-template:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .delete-zone {
            height: 80px;
            margin-top: 20px;
            background-color: #f8d7da;
            border: 2px dashed #dc3545;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #dc3545;
            font-weight: bold;
            transition: all 0.3s;
        }

        .delete-zone.highlight {
            background-color: #f5c2c7;
            transform: scale(1.05);
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
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-controls {
                flex-direction: column;
                width: 100%;
            }
            .filter-select {
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
        <a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>
    
    <!-- Main Content -->
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ตารางเรียน</div>
            <div class="search-container">
                <form class="d-flex">
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาที่นี่">
                    <button type="button" class="btn btn-light" id="searchButton"><i class="fas fa-search"></i></button>
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
            <h2 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> ระบบจัดการตารางเรียน</h2>
        </div>

        <div class="controls">
            <button id="addCourseButton" class="control-button">
                <i class="fas fa-plus me-2"></i>เพิ่มรายวิชา
            </button>
            <div class="filter-controls">
                <select class="filter-select" id="facultySelect">
                    <option value="">เลือกคณะ</option>
                    <?php
                    // Fetch faculties
                    $stmt = $conn->query("SELECT id, faculty_name, thai_faculty_name FROM faculty");
                    while ($faculty = $stmt->fetch()) {
                        $name = $faculty['thai_faculty_name'] ?: $faculty['faculty_name'];
                        echo "<option value='{$faculty['id']}'>" . htmlspecialchars($name) . "</option>";
                    }
                    ?>
                </select>
    
                <select class="filter-select" id="programSelect">
                    <option value="">เลือกหลักสูตร</option>
                    <?php
                    // Fetch programs
                    $stmt = $conn->query("SELECT program_id, program_name, thai_program_name FROM programs");
                    while ($program = $stmt->fetch()) {
                        $name = $program['thai_program_name'] ?: $program['program_name'];
                        echo "<option value='{$program['program_id']}'>" . htmlspecialchars($name) . "</option>";
                    }
                    ?>
                </select>
    
                <select class="filter-select" id="majorSelect">
                    <option value="">เลือกสาขา</option>
                    <?php
                    // Fetch majors
                    $stmt = $conn->query("SELECT major_id, major_name, thai_major_name FROM major");
                    while ($major = $stmt->fetch()) {
                        $name = $major['thai_major_name'] ?: $major['major_name'];
                        echo "<option value='{$major['major_id']}'>" . htmlspecialchars($name) . "</option>";
                    }
                    ?>
                </select>
    
                <select class="filter-select" id="yearSelect">
                    <option value="">ชั้นปี</option>
                    <option value="1">ปี 1</option>
                    <option value="2">ปี 2</option>
                    <option value="3">ปี 3</option>
                    <option value="4">ปี 4</option>
                </select>
    
                <select class="filter-select" id="semesterSelect">
                    <option value="">ภาคเรียน</option>
                    <?php
                    // Fetch semesters
                    $stmt = $conn->query("SELECT semester_id, name, thai_name FROM semesters");
                    while ($semester = $stmt->fetch()) {
                        $name = $semester['thai_name'] ?: $semester['name'];
                        echo "<option value='{$semester['semester_id']}'>" . htmlspecialchars($name) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    
        <div class="timetable-container">
            <table class="timetable" id="timetable">
                <tr>
                    <th></th>
                    <th>08:00-09:00</th>
                    <th>09:00-10:00</th>
                    <th>10:00-11:00</th>
                    <th>11:00-12:00</th>
                    <th>12:00-13:00</th>
                    <th>13:00-14:00</th>
                    <th>14:00-15:00</th>
                    <th>15:00-16:00</th>
                    <th>16:00-17:00</th>
                    <th>17:00-18:00</th>
                    <th>18:00-19:00</th>
                    <th>19:00-20:00</th>
                    <th>20:00-21:00</th>
                </tr>
                <?php
                $days = array_keys($dayMapping);
                foreach ($days as $day) {
                    echo "<tr>";
                    echo "<td class='day $day'>{$dayMapping[$day]}</td>";
                    
                    for ($slot = 0; $slot < 14; $slot++) {
                        $course = $timeTableData[$day][$slot];
                        
                        if ($course) {
                            $colspan = 1;
                            // Calculate colspan
                            while ($slot + $colspan < 14 && 
                                $timeTableData[$day][$slot + $colspan] === $course) {
                                $colspan++;
                            }
                            
                            $location = ($course['building_name'] ? $course['building_name'] . ' - ' : '') . 
                                        ($course['room_name'] ?? 'Online');
                            
                            echo "<td class='droppable' data-day='$day' data-time='$slot' colspan='$colspan'>";
                            echo "<div class='course' 
                                data-course-code='{$course['Course_Code']}'data-section='{$course['section_number']}' 
                                data-location='" . htmlspecialchars($location) . "'>";
                            echo "<div class='course-code'>{$course['Course_Code']} ({$course['section_number']})</div>";
                            echo "<div class='course-location'>" . htmlspecialchars($location) . "</div>";
                            echo "</div>";
                            echo "</td>";
                            
                            // Skip the colspan slots
                            $slot += $colspan - 1;
                        } else {
                            echo "<td class='droppable' data-day='$day' data-time='$slot'></td>";
                        }
                    }
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
    
        <div class="delete-zone" id="deleteZone">
            <i class="fas fa-trash-alt me-2"></i> ลากมาที่นี่เพื่อลบรายวิชา
        </div>
    
        <div class="course-sidebar" id="course-sidebar">
            <h2><i class="fas fa-th-list me-2"></i> รายวิชาที่สามารถเพิ่มได้</h2>
            <?php
            // Fetch available courses for the current semester
            $stmt = $conn->prepare("
                SELECT 
                    c.Course_Code, 
                    c.Course_Name, 
                    cs.section_number,
                    f.id as faculty_id,
                    p.program_id,
                    m.major_id,
                    b.building_name,
                    r.room_name
                FROM course c
                JOIN course_sections cs ON c.Course_Code = cs.Course_Code
                LEFT JOIN curriculum cur ON c.Curriculum_ID = cur.Curriculum_ID
                LEFT JOIN department d ON cur.department_id = d.department_id
                LEFT JOIN faculty f ON d.faculty_id = f.id
                LEFT JOIN major m ON d.department_id = m.department_id
                LEFT JOIN programs p ON m.program_id = p.program_id
                LEFT JOIN class_schedules cl ON cs.section_id = cl.section_id
                LEFT JOIN rooms r ON cl.room_id = r.room_id
                LEFT JOIN buildings b ON r.building_id = b.building_id
                WHERE cs.semester_id = ?
            ");
            $stmt->execute([$currentSemesterId]);
            $availableCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($availableCourses as $course) {
                $location = ($course['building_name'] ? $course['building_name'] . ' - ' : '') . 
                            ($course['room_name'] ?? 'Online');
                echo "<div class='course-template' draggable='true' 
                    data-course-code='{$course['Course_Code']}' 
                    data-section='{$course['section_number']}'
                    data-faculty='{$course['faculty_id']}' 
                    data-program='{$course['program_id']}' 
                    data-major='{$course['major_id']}' 
                    data-location='" . htmlspecialchars($location) . "'>";
                echo "<div class='course-code'>{$course['Course_Code']} ({$course['section_number']})</div>";
                echo "<div class='course-location'>" . htmlspecialchars($location) . "</div>";
                echo "</div>";
            }
            ?>
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

        // Toggle course sidebar
        document.getElementById('addCourseButton').addEventListener('click', function() {
            const courseSidebar = document.getElementById('course-sidebar');
            courseSidebar.classList.toggle('active');
        });

        // Container hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.timetable-container');
            
            container.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.1)';
            });
            
            container.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.08)';
            });
        });

        // Additional filter functionality to match database structure
        document.addEventListener('DOMContentLoaded', () => {
            const facultySelect = document.getElementById('facultySelect');
            const programSelect = document.getElementById('programSelect');
            const majorSelect = document.getElementById('majorSelect');
            const yearSelect = document.getElementById('yearSelect');
            const semesterSelect = document.getElementById('semesterSelect');

            function filterCourses() {
                const courseTemplates = document.querySelectorAll('.course-template');
                
                courseTemplates.forEach(template => {
                    const matchesFaculty = !facultySelect.value || 
                        template.dataset.faculty === facultySelect.value;
                    const matchesProgram = !programSelect.value || 
                        template.dataset.program === programSelect.value;
                    const matchesMajor = !majorSelect.value || 
                        template.dataset.major === majorSelect.value;
                    const matchesYear = !yearSelect.value || 
                        template.dataset.year === yearSelect.value;
                    const matchesSemester = !semesterSelect.value || 
                        template.dataset.semester === semesterSelect.value;

                    template.style.display = (
                        matchesFaculty && 
                        matchesProgram && 
                        matchesMajor && 
                        matchesYear && 
                        matchesSemester
                    ) ? 'block' : 'none';
                });
            }

            facultySelect.addEventListener('change', filterCourses);
            programSelect.addEventListener('change', filterCourses);
            majorSelect.addEventListener('change', filterCourses);
            yearSelect.addEventListener('change', filterCourses);
            semesterSelect.addEventListener('change', filterCourses);
            
            // Search functionality
            document.getElementById('searchButton').addEventListener('click', function() {
                const searchText = document.getElementById('searchInput').value.toLowerCase();
                const courseTemplates = document.querySelectorAll('.course-template');
                
                courseTemplates.forEach(template => {
                    const courseCode = template.querySelector('.course-code').textContent.toLowerCase();
                    template.style.display = courseCode.includes(searchText) ? 'block' : 'none';
                });
            });
            
            // Search on Enter key
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('searchButton').click();
                } else if (this.value === '') {
                    const courseTemplates = document.querySelectorAll('.course-template');
                    courseTemplates.forEach(template => {
                        template.style.display = 'block';
                    });
                    filterCourses(); // Apply filters again
                }
            });
            
            // Drag and drop functionality
            const courseTemplates = document.querySelectorAll('.course-template');
            const droppableAreas = document.querySelectorAll('.droppable');
            const deleteZone = document.getElementById('deleteZone');
            
            courseTemplates.forEach(course => {
                course.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', JSON.stringify({
                        courseCode: this.dataset.courseCode,
                        section: this.dataset.section,
                        location: this.dataset.location
                    }));
                });
            });
            
            droppableAreas.forEach(area => {
                area.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.style.backgroundColor = 'rgba(40, 167, 69, 0.2)';
                });
                
                area.addEventListener('dragleave', function() {
                    this.style.backgroundColor = '';
                });
                
                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.backgroundColor = '';
                    
                    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                    
                    // Check if the cell is already occupied
                    if (this.children.length === 0) {
                        const courseElement = document.createElement('div');
                        courseElement.className = 'course';
                        courseElement.dataset.courseCode = data.courseCode;
                        courseElement.dataset.section = data.section;
                        courseElement.dataset.location = data.location;
                        courseElement.draggable = true;
                        
                        courseElement.innerHTML = `
                            <div class="course-code">${data.courseCode} (${data.section})</div>
                            <div class="course-location">${data.location}</div>
                        `;
                        
                        this.appendChild(courseElement);
                        
                        // Add drag functionality to the newly added course
                        courseElement.addEventListener('dragstart', function(e) {
                            e.dataTransfer.setData('text/plain', JSON.stringify({
                                courseCode: this.dataset.courseCode,
                                section: this.dataset.section,
                                location: this.dataset.location
                            }));
                        });
                    }
                });
            });
            
            // Delete zone functionality
            deleteZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('highlight');
            });
            
            deleteZone.addEventListener('dragleave', function() {
                this.classList.remove('highlight');
            });
            
            deleteZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('highlight');
                
                // The dragged element is removed by the browser automatically
            });
        });
    </script>
</body>
</html>