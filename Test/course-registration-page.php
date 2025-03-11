php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="registration.php" class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i> ลงทะเบียนเรียนเพิ่ม
            </a>
            <a href="student_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i> กลับสู่หน้าหลัก
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
    // Get student information
    $student_sql = "SELECT sd.student_code, v.* 
                    FROM student_details sd
                    JOIN vw_student_info v ON sd.id_account = v.id_account
                    WHERE sd.id_account = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(1, $_SESSION['user_id']);
    $student_stmt->execute();
    $student = $student_stmt->fetch();

    // Get course registrations
    $registrations_sql = "SELECT vr.*, s.name as semester_name, s.thai_name as semester_thai_name, 
                            ay.year as academic_year, cs.section_number, 
                            cls.day_of_week, cls.start_time, cls.end_time,
                            CONCAT(b.building_name, ' - ', r.room_name) as location
                         FROM vw_student_registrations vr
                         JOIN course_registration cr ON vr.Registration_ID = cr.Registration_ID
                         JOIN course_sections cs ON cr.section_id = cs.section_id
                         JOIN semesters s ON (cr.Semester = s.semester_number AND cr.Academic_Year = s.academic_year_id)
                         JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
                         LEFT JOIN class_schedules cls ON cs.section_id = cls.section_id
                         LEFT JOIN rooms r ON cls.room_id = r.room_id
                         LEFT JOIN buildings b ON r.building_id = b.building_id
                         WHERE vr.student_code = ?
                         ORDER BY cr.Academic_Year DESC, cr.Semester DESC, vr.Course_Name";
    $registrations_stmt = $conn->prepare($registrations_sql);
    $registrations_stmt->bindParam(1, $student['student_code']);
    $registrations_stmt->execute();
    $registrations = $registrations_stmt->fetchAll();

} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายวิชาที่ลงทะเบียน - Suan Dusit University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }

        .course-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .course-card .card-header {
            background-color: #063D8C;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grade-badge {
            font-size: 0.9rem;
        }

        .schedule-badge {
            background-color: rgba(13, 110, 253, 0.1);
            color: #063D8C;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 10px;
        }

        .registration-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h2 class="mb-4">
            <i class="fas fa-book me-2"></i> 
            รายวิชาที่ลงทะเบียน 
            <?php 
            // Display student name and code
            echo htmlspecialchars($student['thai_first_name'] . ' ' . $student['thai_last_name']); 
            echo " (". htmlspecialchars($student['student_code']) .")";
            ?>
        </h2>

        <?php 
        // Check for success/error messages from registration
        if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> 
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php 
        if (isset($_GET['errors'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i> 
                <?php echo htmlspecialchars($_GET['errors']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($registrations)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> 
                คุณยังไม่ได้ลงทะเบียนเรียนในภาคการศึกษานี้
            </div>
        <?php else: ?>
            <!-- Group registrations by semester and academic year -->
            <?php 
            $current_semester = null;
            $current_year = null;
            foreach ($registrations as $registration):
                // Check if this is a new semester/year group
                if ($current_semester !== $registration['semester_name'] || 
                    $current_year !== $registration['academic_year']):
                    // Close previous group if exists
                    if ($current_semester !== null): ?>
                        </div>
                    <?php endif; ?>

                    <!-- Start new semester group -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?php 
                                echo htmlspecialchars($registration['semester_thai_name'] . ' ปีการศึกษา ' . $registration['academic_year']); 
                                ?>
                            </h5>
                        </div>
                        <div class="card-body">
                <?php 
                    $current_semester = $registration['semester_name'];
                    $current_year = $registration['academic_year'];
                endif; 
                ?>

                <!-- Course Registration Card -->
                <div class="card course-card mb-3">
                    <div class="card-header">
                        <span>
                            <?php echo htmlspecialchars($registration['Course_Name']); ?>
                            <span class="badge bg-light text-dark ms-2">
                                <?php echo htmlspecialchars($registration['Course_Code']); ?>
                            </span>
                        </span>
                        <div>
                            <span class="badge bg-warning">
                                <?php echo htmlspecialchars($registration['Credits']); ?> หน่วยกิต
                            </span>
                            <?php if ($registration['Grade']): ?>
                                <span class="badge bg-success grade-badge ms-2">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    <?php echo htmlspecialchars($registration['Grade']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>รายละเอียดวิชา</h6>
                                <p class="mb-1">
                                    <strong>กลุ่มเรียน:</strong> 
                                    <span class="schedule-badge">
                                        <?php echo htmlspecialchars($registration['section_number']); ?>
                                    </span>
                                </p>
                                <?php if ($registration['start_time'] && $registration['end_time']): ?>
                                    <p class="mb-1">
                                        <strong>เวลาเรียน:</strong> 
                                        <?php 
                                        echo htmlspecialchars(
                                            date('H:i', strtotime($registration['start_time'])) . 
                                            ' - ' . 
                                            date('H:i', strtotime($registration['end_time']))
                                        ); 
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($registration['location']): ?>
                                    <p class="mb-1">
                                        <strong>สถานที่:</strong> 
                                        <?php echo htmlspecialchars($registration['location']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($registration['day_of_week']): ?>
                                    <p class="mb-1">
                                        <strong>วันเรียน:</strong> 
                                        <?php 
                                        $thai_days = [
                                            'monday' => 'วันจันทร์',
                                            'tuesday' => 'วันอังคาร',
                                            'wednesday' => 'วันพุธ',
                                            'thursday' => 'วันพฤหัสบดี',
                                            'friday' => 'วันศุกร์',
                                            'saturday' => 'วันเสาร์',
                                            'sunday' => 'วันอาทิตย์'
                                        ];
                                        echo htmlspecialchars($thai_days[$registration['day_of_week']] ?? $registration['day_of_week']); 
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>สถานะ</h6>
                                <?php 
                                $status_types = [
                                    'registered' => ['bg-success', 'ลงทะเบียนแล้ว'],
                                    'withdrawn' => ['bg-warning', 'ถอนรายวิชา'],
                                    'dropped' => ['bg-danger', 'ยกเลิก']
                                ];
                                $status_color = $status_types[$registration['status']][0] ?? 'bg-secondary';
                                $status_text = $status_types[$registration['status']][1] ?? 'ไม่ทราบสถานะ';
                                ?>
                                <span class="registration-status <?php echo $status_color; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?