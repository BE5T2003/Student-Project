<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_data_tracking";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8");

// Get all majors for dropdown
$majorQuery = "SELECT major_id, major_code, thai_major_name FROM major ORDER BY thai_major_name";
$majorResult = $conn->query($majorQuery);

// Get all academic years
$academicYearQuery = "SELECT academic_year_id, year FROM academic_years ORDER BY year DESC";
$academicYearResult = $conn->query($academicYearQuery);

// Initialize variables
$selectedMajor = isset($_GET['major']) ? $_GET['major'] : null;
$selectedYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;
$selectedYearTerm = isset($_GET['year_term']) ? $_GET['year_term'] : 'all';
$selectedCourse = isset($_GET['course_code']) ? $_GET['course_code'] : null;

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

// Get study plan data if a major is selected
$studyPlanData = [];
if ($selectedMajor) {
    // Query to get major information
    $majorInfoQuery = "SELECT m.major_id, m.major_code, m.thai_major_name, m.major_name, 
                       d.thai_department_name, f.thai_faculty_name, c.Curriculum_Name, c.Required_Credit
                       FROM major m
                       JOIN department d ON m.department_id = d.department_id
                       JOIN faculty f ON d.faculty_id = f.id
                       LEFT JOIN curriculum c ON m.Curriculum_ID = c.Curriculum_ID
                       WHERE m.major_id = $selectedMajor";
    $majorInfoResult = $conn->query($majorInfoQuery);
    $majorInfo = $majorInfoResult->fetch_assoc();
    
    // Query to get course plan by major id
    $studyPlanQuery = "SELECT mc.major_id, mc.study_year, mc.semester_number, 
                      c.Course_Code, c.Course_Name, ct.Course_Type_ID,
                      c.Credits, IFNULL(mc.is_required, 1) as is_required
                      FROM major_courses mc
                      JOIN course c ON mc.Course_Code = c.Course_Code
                      LEFT JOIN course_type ct ON c.Course_Code = ct.Course_Code
                      WHERE mc.major_id = $selectedMajor";
    
    // Add year/term filter if selected
    if ($selectedYearTerm !== 'all') {
        list($year, $term) = explode('_', $selectedYearTerm);
        $studyPlanQuery .= " AND mc.study_year = $year AND mc.semester_number = $term";
    }
    
    $studyPlanQuery .= " ORDER BY mc.study_year, mc.semester_number, ct.Course_Type_ID, c.Course_Code";
    $studyPlanResult = $conn->query($studyPlanQuery);

    // Group courses by year, semester and course type
    if ($studyPlanResult && $studyPlanResult->num_rows > 0) {
        while ($row = $studyPlanResult->fetch_assoc()) {
            $yearKey = $row['study_year'];
            $semesterKey = $row['semester_number'];
            $courseTypeKey = $row['Course_Type_ID'] ?: '0'; // Default to '0' if null
            
            if (!isset($studyPlanData[$yearKey])) {
                $studyPlanData[$yearKey] = [];
            }
            if (!isset($studyPlanData[$yearKey][$semesterKey])) {
                $studyPlanData[$yearKey][$semesterKey] = [];
            }
            if (!isset($studyPlanData[$yearKey][$semesterKey][$courseTypeKey])) {
                $studyPlanData[$yearKey][$semesterKey][$courseTypeKey] = [];
            }
            
            $studyPlanData[$yearKey][$semesterKey][$courseTypeKey][] = $row;
        }
    }
}

// Course type mapping
$courseTypes = [
    '1' => 'หมวดวิชาศึกษาทั่วไป',
    '2' => 'หมวดวิชาเฉพาะ',
    '3' => 'หมวดวิชาเลือกเสรี',
    '0' => 'หมวดวิชาอื่นๆ'
];

// Function to calculate total credits
function calculateTotalCredits($courses) {
    $total = 0;
    foreach ($courses as $course) {
        $total += $course['Credits'];
    }
    return $total;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนการเรียน - ระบบจัดการข้อมูลนักศึกษา</title>
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

        .filter-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .filter-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .filter-container label {
            font-weight: 500;
            color: #3871c1;
            margin-bottom: 8px;
        }

        .filter-container select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-container select:hover,
        .filter-container select:focus {
            border-color: #3871c1;
            box-shadow: 0 2px 6px rgba(56, 113, 193, 0.2);
        }

        .study-plan-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .study-plan-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .study-plan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .study-plan-table th, .study-plan-table td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            text-align: center;
        }

        .study-plan-table th {
            background-color: #f2f7fd;
            color: #3871c1;
            font-weight: 600;
            text-align: center;
            border-bottom: 2px solid #3871c1;
        }

        .category-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .course-name {
            text-align: left;
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

        .btn-outline-primary,
        .btn-outline-success {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-outline-primary:hover,
        .btn-outline-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .table-container:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
        <a href="แผนการเรียน.php" class="active"><i class="fas fa-calendar-check"></i> แผนการเรียน</a>
        <a href="schedule.php"><i class="fas fa-calendar-alt"></i> ตารางเรียน</a>
        <a href="toeic.php"><i class="fas fa-language"></i> ผลสอบ TOEIC</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> รายงาน</a>
        <a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>  

    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="dashboard-title">ระบบแผนการเรียน</div>
            <div class="search-container">
                <form class="d-flex">
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาที่นี่">
                    <button type="button" class="btn btn-light" id="searchButton"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="user-info">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <div>
                    <strong>ฝ่ายวิชาการ</strong>
                    <p class="m-0">มหาวิทยาลัยสวนดุสิต</p>
                </div>
            </div>
        </div>

        <!-- Section Header -->
        <div class="section-header mb-4">
            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i> แผนการเรียน</h2>
        </div>

        <!-- Filter controls -->
        <div class="filter-container">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="major" class="form-label"><i class="fas fa-graduation-cap me-1"></i> สาขาวิชา</label>
                    <select name="major" id="major" class="form-select" onchange="this.form.submit()">
                        <option value="">กรุณาเลือกสาขาวิชา</option>
                        <?php while ($major = $majorResult->fetch_assoc()): ?>
                            <option value="<?php echo $major['major_id']; ?>" <?php echo ($selectedMajor == $major['major_id']) ? 'selected' : ''; ?>>
                                <?php echo $major['thai_major_name'] . ' (' . $major['major_code'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="year_term" class="form-label"><i class="fas fa-calendar me-1"></i> ปี/ภาคการศึกษา</label>
                    <select name="year_term" id="year_term" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo ($selectedYearTerm == 'all') ? 'selected' : ''; ?>>ทั้งหมด</option>
                        <?php for ($year = 1; $year <= 4; $year++): ?>
                            <?php for ($term = 1; $term <= 2; $term++): ?>
                                <option value="<?php echo $year . '_' . $term; ?>" <?php echo ($selectedYearTerm == $year . '_' . $term) ? 'selected' : ''; ?>>
                                    ปี <?php echo $year; ?> เทอม <?php echo $term; ?>
                                </option>
                            <?php endfor; ?>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="academic_year" class="form-label"><i class="fas fa-university me-1"></i> ปีการศึกษา</label>
                    <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                        <option value="">ทุกปีการศึกษา</option>
                        <?php while ($academicYear = $academicYearResult->fetch_assoc()): ?>
                            <option value="<?php echo $academicYear['academic_year_id']; ?>" <?php echo ($selectedYear == $academicYear['academic_year_id']) ? 'selected' : ''; ?>>
                                <?php echo $academicYear['year']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selectedMajor && isset($majorInfo)): ?>
        <!-- Major information card -->
        <div class="table-container">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h5 class="text-primary"><i class="fas fa-info-circle me-2"></i>ข้อมูลหลักสูตร</h5>
                    <hr>
                </div>
                <div class="col-md-6">
                    <p><strong><i class="fas fa-graduation-cap me-1"></i> สาขาวิชา:</strong> <?php echo $majorInfo['thai_major_name'] . ' (' . $majorInfo['major_name'] . ')'; ?></p>
                    <p><strong><i class="fas fa-id-card me-1"></i> รหัสสาขา:</strong> <?php echo $majorInfo['major_code']; ?></p>
                    <p><strong><i class="fas fa-university me-1"></i> คณะ:</strong> <?php echo $majorInfo['thai_faculty_name']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><i class="fas fa-building me-1"></i> ภาควิชา:</strong> <?php echo $majorInfo['thai_department_name']; ?></p>
                    <p><strong><i class="fas fa-book me-1"></i> หลักสูตร:</strong> <?php echo $majorInfo['Curriculum_Name'] ?: 'ไม่ระบุ'; ?></p>
                    <p><strong><i class="fas fa-calculator me-1"></i> จำนวนหน่วยกิตตลอดหลักสูตร:</strong> <?php echo $majorInfo['Required_Credit'] ?: 'ไม่ระบุ'; ?> หน่วยกิต</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($selectedCourse): ?>
        <!-- Course specific information -->
        <div class="table-container">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h5 class="text-primary"><i class="fas fa-book me-2"></i>แผนการเรียนรายวิชา <?php echo $selectedCourse; ?></h5>
                    <hr>
                </div>
            </div>
            <div class="row">
                <?php
                    // Fetch course details
                    $courseQuery = "SELECT c.Course_Code, c.Course_Name, c.Credits, c.Curriculum_ID, ct.Course_Type_ID 
                                   FROM course c
                                   LEFT JOIN course_type ct ON c.Course_Code = ct.Course_Code
                                   WHERE c.Course_Code = '$selectedCourse'";
                    $courseResult = $conn->query($courseQuery);
                    
                    if ($courseResult && $courseResult->num_rows > 0) {
                        $courseInfo = $courseResult->fetch_assoc();
                        
                        // Find all majors that have this course in their plan
                        $majorListQuery = "SELECT DISTINCT m.major_id, m.thai_major_name, m.major_code, mc.study_year, mc.semester_number
                                         FROM major_courses mc
                                         JOIN major m ON mc.major_id = m.major_id
                                         WHERE mc.Course_Code = '$selectedCourse'
                                         ORDER BY m.thai_major_name, mc.study_year, mc.semester_number";
                        $majorListResult = $conn->query($majorListQuery);
                        
                        echo "<div class='col-md-6'>";
                        echo "<p><strong><i class='fas fa-hashtag me-1'></i> รหัสวิชา:</strong> " . $courseInfo['Course_Code'] . "</p>";
                        echo "<p><strong><i class='fas fa-book-open me-1'></i> ชื่อวิชา:</strong> " . $courseInfo['Course_Name'] . "</p>";
                        echo "<p><strong><i class='fas fa-calculator me-1'></i> หน่วยกิต:</strong> " . $courseInfo['Credits'] . "</p>";
                        echo "</div>";
                        echo "<div class='col-md-6'>";
                        echo "<p><strong><i class='fas fa-list-alt me-1'></i> หมวดวิชา:</strong> " . getCourseTypeName($courseInfo['Course_Type_ID']) . "</p>";
                        echo "</div>";
                        echo "</div>";
                        
                        if ($majorListResult && $majorListResult->num_rows > 0) {
                            echo "<div class='row mt-4'>";
                            echo "<div class='col-md-12'>";
                            echo "<h6 class='text-secondary mb-3'><i class='fas fa-university me-2'></i>สาขาวิชาที่มีรายวิชานี้ในแผนการเรียน:</h6>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-bordered table-striped'>";
                            echo "<thead class='table-primary'>";
                            echo "<tr>";
                            echo "<th><i class='fas fa-graduation-cap me-1'></i> สาขาวิชา</th>";
                            echo "<th><i class='fas fa-id-card me-1'></i> รหัสสาขา</th>";
                            echo "<th><i class='fas fa-layer-group me-1'></i> ชั้นปี</th>";
                            echo "<th><i class='fas fa-calendar-alt me-1'></i> ภาคการศึกษา</th>";
                            echo "<th><i class='fas fa-cogs me-1'></i> การดำเนินการ</th>";
                            echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                            
                            while ($majorRow = $majorListResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $majorRow['thai_major_name'] . "</td>";
                                echo "<td>" . $majorRow['major_code'] . "</td>";
                                echo "<td>" . $majorRow['study_year'] . "</td>";
                                echo "<td>" . $majorRow['semester_number'] . "</td>";
                                echo "<td><a href='แผนการเรียน.php?major=" . $majorRow['major_id'] . "&year_term=" . $majorRow['study_year'] . "_" . $majorRow['semester_number'] . "' class='btn btn-sm btn-primary'><i class='fas fa-eye me-1'></i> ดูแผนการเรียน</a></td>";
                                echo "</tr>";
                            }
                            
                            echo "</tbody>";
                            echo "</table>";
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                        } else {
                            echo "<div class='alert alert-warning mt-3'>";
                            echo "<i class='fas fa-exclamation-triangle me-2'></i> ไม่พบข้อมูลแผนการเรียนที่มีรายวิชานี้";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='alert alert-danger'>";
                        echo "<i class='fas fa-times-circle me-2'></i> ไม่พบข้อมูลรายวิชา";
                        echo "</div>";
                    }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($studyPlanData)): ?>
            <?php foreach ($studyPlanData as $year => $semesters): ?>
                <?php foreach ($semesters as $semester => $coursesByType): ?>
                    <div class="study-plan-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-primary mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>ปีที่ <?php echo $year; ?> / ภาคการศึกษาที่ <?php echo $semester; ?>
                            </h5>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i> พิมพ์
                                </button>
                                <button class="btn btn-sm btn-outline-success ms-2" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-1"></i> Excel
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="study-plan-table" id="studyPlanTable_<?php echo $year; ?>_<?php echo $semester; ?>">
                                <thead>
                                    <tr>
                                        <th rowspan="2" style="min-width: 300px;">รายวิชา</th>
                                        <th rowspan="2">หน่วยกิต</th>
                                        <th colspan="2">ชั่วโมง/สัปดาห์</th>
                                        <th rowspan="2">ศึกษาด้วยตนเอง</th>
                                    </tr>
                                    <tr>
                                        <th>ทฤษฎี</th>
                                        <th>ปฏิบัติ</th>
                                    </tr>
                                </thead>
                                
                                <?php 
                                $semesterTotalCredits = 0;
                                foreach ($coursesByType as $courseType => $courses): 
                                    // Calculate credits for this course type
                                    $typeTotalCredits = 0;
                                    foreach ($courses as $course) {
                                        $typeTotalCredits += $course['Credits'];
                                        $semesterTotalCredits += $course['Credits'];
                                    }
                                ?>
                                    <tbody>
                                        <tr class="category-header">
                                            <td colspan="5" class="text-start bg-light">
                                                <strong><i class="fas fa-bookmark me-2"></i><?php echo $courseTypes[$courseType]; ?></strong>
                                            </td>
                                        </tr>
                                        <?php foreach ($courses as $course): ?>
                                                <tr class="category-row" data-category="ปี<?php echo $year; ?>เทอม<?php echo $semester; ?>">
                                                    <td class="course-name">
                                                        <strong><?php echo $course['Course_Code']; ?></strong> <?php echo $course['Course_Name']; ?>
                                                    </td>
                                                    <td><?php echo $course['Credits']; ?></td>
                                                    <td>
                                                        <?php 
                                                            // Default theory hours (typically 1 hour per credit)
                                                            echo $course['Credits']; 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            // Default practice hours (typically 0 for lecture courses)
                                                            echo '0'; 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            // Default self-study hours (typically 2 hours per credit)
                                                            echo $course['Credits'] * 2; 
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            
                                            <!-- Sub-total for this course type -->
                                            <tr>
                                                <td class="text-end">
                                                    <strong>รวม <?php echo $courseTypes[$courseType]; ?></strong>
                                                </td>
                                                <td style="font-weight: bold;"><?php echo $typeTotalCredits; ?></td>
                                                <td colspan="3"></td>
                                            </tr>
                                        </tbody>
                                    <?php endforeach; ?>
                                    
                                    <tfoot>
                                        <tr style="background-color: #e9f7ff;">
                                            <td class="text-end">
                                                <strong>รวมจำนวนหน่วยกิตทั้งหมด</strong>
                                            </td>
                                            <td style="font-weight: bold; font-size: 1.1em;"><?php echo $semesterTotalCredits; ?></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php elseif ($selectedMajor): ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i> ไม่พบข้อมูลแผนการเรียนสำหรับสาขาวิชาที่เลือก กรุณาเพิ่มข้อมูลในตาราง major_courses
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i> กรุณาเลือกสาขาวิชาเพื่อดูแผนการเรียน
                </div>
            <?php endif; ?>
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
            
            // Search functionality
            document.getElementById('searchButton').addEventListener('click', function() {
                const searchText = document.getElementById('searchInput').value.toLowerCase();
                const courseRows = document.querySelectorAll('.category-row');
                
                courseRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchText)) {
                        row.style.display = '';
                        row.closest('tbody').querySelector('.category-header').style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show headings only if there are visible rows
                document.querySelectorAll('tbody').forEach(tbody => {
                    const visibleRows = tbody.querySelectorAll('.category-row[style="display: none;"]').length;
                    const totalRows = tbody.querySelectorAll('.category-row').length;
                    
                    if (visibleRows === totalRows) {
                        tbody.querySelector('.category-header').style.display = 'none';
                    }
                });
            });
            
            // Reset search on Enter key
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('searchButton').click();
                } else if (this.value === '') {
                    const courseRows = document.querySelectorAll('.category-row, .category-header');
                    courseRows.forEach(row => {
                        row.style.display = '';
                    });
                }
            });
            
            // Add container hover effects
            document.addEventListener('DOMContentLoaded', function() {
                const containers = document.querySelectorAll('.study-plan-container, .table-container, .filter-container');
                
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
            
            // Function to export table to Excel
            function exportToExcel() {
                alert('ฟังก์ชันส่งออกเป็น Excel อยู่ระหว่างการพัฒนา');
                // In a real implementation, you would use a library like SheetJS or make an AJAX request to a server-side script
            }
        </script>
    </body>
    </html>
    <?php
    // Close the database connection
    $conn->close();
    ?>