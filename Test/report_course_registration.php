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

// Fetch Current Semester
try {
    $semester_stmt = $conn->query("SELECT * FROM semesters WHERE is_current = 1");
    $current_semester = $semester_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Course Registration Details
    $courses_sql = "
        SELECT 
            c.Course_Code, 
            c.Course_Name, 
            cs.section_number,
            d.department_name,
            d.thai_department_name,
            cs.instructor_name,
            COUNT(cr.Student_ID) as registered_students,
            cs.max_students,
            ROUND(COUNT(cr.Student_ID) / cs.max_students * 100, 2) as fill_percentage
        FROM course c
        JOIN course_sections cs ON c.Course_Code = cs.Course_Code
        LEFT JOIN course_registration cr ON cs.section_id = cr.section_id
        LEFT JOIN curriculum cur ON c.Curriculum_ID = cur.Curriculum_ID
        LEFT JOIN department d ON cur.department_id = d.department_id
        WHERE cs.semester_id = :semester_id
        GROUP BY 
            c.Course_Code, 
            c.Course_Name, 
            cs.section_number,
            d.department_name,
            d.thai_department_name,
            cs.instructor_name,
            cs.max_students
        ORDER BY fill_percentage DESC
    ";
    
    $stmt = $conn->prepare($courses_sql);
    $stmt->bindParam(':semester_id', $current_semester['semester_id']);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching course registrations: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายงานการลงทะเบียนรายวิชา - Suan Dusit University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        .report-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .semester-info {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .progress {
            height: 20px;
        }
        .fill-low { background-color: #dc3545; }
        .fill-medium { background-color: #ffc107; }
        .fill-high { background-color: #28a745; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="report-container">
            <div class="report-header">
                <h2>รายงานการลงทะเบียนรายวิชา</h2>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> พิมพ์รายงาน
                    </button>
                </div>
            </div>

            <div class="semester-info">
                <h3>
                    <?php 
                    echo htmlspecialchars($current_semester['name'] . ' ') . 
                         htmlspecialchars($current_semester['thai_name']) . 
                         ' ปีการศึกษา ' . 
                         htmlspecialchars($current_semester['academic_year_id']); 
                    ?>
                </h3>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>กลุ่มเรียน</th>
                            <th>ภาควิชา</th>
                            <th>ผู้สอน</th>
                            <th>จำนวนที่รับ</th>
                            <th>จำนวนลงทะเบียน</th>
                            <th>อัตราการเต็ม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_department = null;
                        foreach ($courses as $course): 
                            // Prepare department name
                            $department_name = htmlspecialchars(
                                $course['thai_department_name'] ?? $course['department_name'] ?? 'ไม่ระบุ'
                            );

                            // Department header
                            if ($current_department !== $department_name) {
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='8'><strong>$department_name</strong></td>";
                                echo "</tr>";
                                $current_department = $department_name;
                            }

                            // Determine fill percentage class
                            $fill_percentage = $course['fill_percentage'];
                            $progress_class = $fill_percentage < 33 ? 'fill-low' : 
                                              ($fill_percentage < 66 ? 'fill-medium' : 'fill-high');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($course['section_number']); ?></td>
                                <td><?php echo $department_name; ?></td>
                                <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                <td><?php echo number_format($course['max_students']); ?></td>
                                <td><?php echo number_format($course['registered_students']); ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?php echo $progress_class; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $fill_percentage; ?>%"
                                             aria-valuenow="<?php echo $fill_percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo $fill_percentage; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="8" class="text-center">ไม่มีข้อมูลการลงทะเบียนในภาคการศึกษานี้</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>