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

    // Fetch student's timetable
    $timetable_sql = "SELECT * FROM vw_student_timetable WHERE student_code = ?";
    $timetable_stmt = $conn->prepare($timetable_sql);
    $timetable_stmt->bindParam(1, $student_code);
    $timetable_stmt->execute();
    $timetable = $timetable_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>รายละเอียดนักศึกษา</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="ข้อมูลส่วนตัว.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University">
            <h3>Suan Dusit University</h3>
        </div>
        <a href="academic_dashboard.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
        <a href="students.php" class="active"><i class="fas fa-user-graduate"></i> ข้อมูลนักศึกษา</a>
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>  

    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle">☰</button>
            <div class="dashboard-title">ข้อมูลนักศึกษา</div>
            <div class="search-container ms-auto">
                <input type="text" id="searchInput" class="form-control w-50" placeholder="ค้นหาที่นี่">
                <button class="btn btn-light">🔍</button>
            </div>
            <div class="user-info">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <div>
                    <strong><?php echo htmlspecialchars(($student['thai_first_name'] ? $student['thai_first_name'] . ' ' . $student['thai_last_name'] : $student['first_name'] . ' ' . $student['last_name'])); ?></strong>
                    <p class="m-0">ฝ่ายวิชาการ</p>
                </div>
            </div>
        </div>

        <!-- ข้อมูลนักศึกษาและอาจารย์ที่ปรึกษา -->
        <div class="row mb-4">
            <div class="col-md-3 text-center">
                <img src="<?php echo $student['profile_image'] ?: 'https://randomuser.me/api/portraits/men/1.jpg'; ?>" alt="รูปนักศึกษา" class="profile-image mb-2">
            </div>
            <div class="col-md-5">
                <h3><?php echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']); ?></h3>
                <p><strong>รหัสนักศึกษา:</strong> <span><?php echo htmlspecialchars($student['student_code']); ?></span></p>
                <p><strong>สาขา:</strong> <?php echo htmlspecialchars($student['thai_major_name'] ?: 'ไม่ได้ระบุ'); ?></p>
                <p><strong>ปีการศึกษา:</strong> <?php echo '1'; // Hardcoded for now ?></p>
                <p><strong>สถานะ:</strong> <?php echo htmlspecialchars($student['student_status']); ?></p>
                <p><strong>สถานะทางวิชาการ:</strong> <?php echo htmlspecialchars($student['academic_status']); ?></p>
            </div>
            <div class="col-md-4">
                <div class="advisor-section">
                    <h4>ข้อมูลนักศึกษา</h4>
                    <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($student['email_account']); ?></p>
                    <p><strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars($student['phone'] ?: 'ไม่ระบุ'); ?></p>
                    <p><strong>คณะ:</strong> <?php echo htmlspecialchars($student['thai_faculty_name'] ?: 'ไม่ระบุ'); ?></p>
                    <p><strong>ภาควิชา:</strong> <?php echo htmlspecialchars($student['thai_department_name'] ?: 'ไม่ระบุ'); ?></p>
                </div>
            </div>
        </div>

        <!-- คะแนน TOEIC -->
        <div class="toeic-section">
            <h4>คะแนน TOEIC</h4>
            <div class="row">
                <?php if ($toeic_info && $toeic_info['TOEIC_Score']): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">ผลสอบ TOEIC</h5>
                                <p><strong>คะแนนสอบ:</strong> <?php echo $toeic_info['TOEIC_Score']; ?></p>
                                <p><strong>วันที่สอบ:</strong> <?php echo date('d/m/Y', strtotime($toeic_info['Test_Date'])); ?></p>
                                <p><strong>สถานะการลงทะเบียน:</strong> <?php echo $toeic_info['Registration_Status']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            ยังไม่มีข้อมูลผลสอบ TOEIC
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- วิชาที่ลงทะเบียนเรียน -->
        <h4 class="mt-4">วิชาที่ลงทะเบียนเรียน</h4>
        <table class="table table-bordered">
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
                        <td colspan="5" class="text-center">ไม่มีวิชาที่ลงทะเบียน</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- สรุปผลการเรียน -->
        <div class="summary-section">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>หลักสูตร:</strong> <?php echo htmlspecialchars($transcript['Curriculum_Name'] ?: 'ไม่ระบุ'); ?></p>
                    <p><strong>หน่วยกิตสะสม:</strong> <?php echo htmlspecialchars($transcript['total_credits'] ?: '0'); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>เกรดเฉลี่ยสะสม:</strong> <?php echo htmlspecialchars(number_format($transcript['gpa'] ?: 0, 2)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="back-button-container mt-4 mb-4">
        <a href="academic_dashboard.php" class="btn btn-secondary back-button">ย้อนกลับ</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById("menu-toggle").addEventListener("click", function() {
            let sidebar = document.getElementById("sidebar");
            let content = document.getElementById("content");
            sidebar.classList.toggle("hidden");
            content.classList.toggle("expanded");
        });
    </script>
</body>
</html>