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

// Fetch Advisor Statistics
try {
    // Advisors with their student counts and academic information
    $advisors_sql = "
        SELECT 
            td.teacher_code,
            up.first_name,
            up.last_name,
            up.thai_first_name,
            up.thai_last_name,
            d.department_name,
            d.thai_department_name,
            COUNT(DISTINCT sd.id_account) as total_students,
            COUNT(DISTINCT CASE WHEN sd.academic_status = 'normal' THEN sd.id_account END) as normal_students,
            COUNT(DISTINCT CASE WHEN sd.academic_status = 'probation' THEN sd.id_account END) as probation_students,
            ROUND(AVG(COALESCE((
                SELECT AVG(cr.Grade) 
                FROM course_registration cr 
                WHERE cr.Student_ID = sd.student_code
            ), 0)), 2) as avg_student_gpa
        FROM teacher_details td
        JOIN account a ON td.id_account = a.id_account
        JOIN user_profiles up ON a.id_account = up.id_account
        LEFT JOIN department d ON up.department_id = d.department_id
        LEFT JOIN student_details sd ON 1=1  # Placeholder join to count students
        GROUP BY 
            td.teacher_code,
            up.first_name,
            up.last_name,
            up.thai_first_name,
            up.thai_last_name,
            d.department_name,
            d.thai_department_name
        ORDER BY total_students DESC
    ";
    
    $stmt = $conn->prepare($advisors_sql);
    $stmt->execute();
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total students count
    $total_students_sql = "SELECT COUNT(*) as total FROM student_details";
    $total_stmt = $conn->prepare($total_students_sql);
    $total_stmt->execute();
    $total_students = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    die("Error fetching advisor statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายงานอาจารย์ที่ปรึกษา - Suan Dusit University</title>
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
        .total-students {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .advisor-stats {
            display: flex;
            justify-content: space-around;
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .status-normal { 
            background-color: #28a745;
            color: white;
        }
        .status-probation { 
            background-color: #ffc107;
            color: black;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="report-container">
            <div class="report-header">
                <h2>รายงานอาจารย์ที่ปรึกษา</h2>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> พิมพ์รายงาน
                    </button>
                </div>
            </div>

            <div class="total-students">
                <h3>จำนวนนักศึกษาทั้งหมด: <?php echo number_format($total_students); ?> คน</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>รหัสอาจารย์</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ภาควิชา</th>
                            <th>จำนวนนักศึกษา</th>
                            <th>นักศึกษาปกติ</th>
                            <th>นักศึกษารอพินิจ</th>
                            <th>เกรดเฉลี่ยนักศึกษา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advisors as $advisor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($advisor['teacher_code']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars(
                                        ($advisor['thai_first_name'] ?? $advisor['first_name']) . 
                                        ' ' . 
                                        ($advisor['thai_last_name'] ?? $advisor['last_name'])
                                    ); 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars(
                                        $advisor['thai_department_name'] ?? 
                                        $advisor['department_name'] ?? 
                                        'ไม่ระบุ'
                                    ); 
                                    ?>
                                </td>
                                <td><?php echo number_format($advisor['total_students']); ?></td>
                                <td>
                                    <span class="status-badge status-normal">
                                        <?php echo number_format($advisor['normal_students']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-probation">
                                        <?php echo number_format($advisor['probation_students']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($advisor['avg_student_gpa'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>