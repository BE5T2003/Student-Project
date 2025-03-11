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

// Fetch Student Count by Faculty, Department, and Year
try {
    $student_count_sql = "
        SELECT 
            f.faculty_name,
            f.thai_faculty_name,
            d.department_name,
            d.thai_department_name,
            sd.study_year,
            COUNT(sd.id_account) as student_count
        FROM faculty f
        LEFT JOIN department d ON f.id = d.faculty_id
        LEFT JOIN major m ON d.department_id = m.department_id
        LEFT JOIN student_details sd ON m.major_id = sd.major_id
        GROUP BY 
            f.faculty_name, 
            f.thai_faculty_name, 
            d.department_name, 
            d.thai_department_name, 
            sd.study_year
        ORDER BY 
            f.faculty_name, 
            d.department_name, 
            sd.study_year
    ";
    
    $stmt = $conn->prepare($student_count_sql);
    $stmt->execute();
    $student_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total students
    $total_students_sql = "SELECT COUNT(*) as total FROM student_details";
    $total_stmt = $conn->prepare($total_students_sql);
    $total_stmt->execute();
    $total_students = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    die("Error fetching student counts: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายงานจำนวนนักศึกษา - Suan Dusit University</title>
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
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="report-container">
            <div class="report-header">
                <h2>รายงานจำนวนนักศึกษา</h2>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> พิมพ์รายงาน
                    </button>
                </div>
            </div>

            <div class="total-students mb-3">
                <h3>จำนวนนักศึกษาทั้งหมด: <?php echo number_format($total_students); ?> คน</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>คณะ</th>
                            <th>ภาควิชา</th>
                            <th>ชั้นปี 1</th>
                            <th>ชั้นปี 2</th>
                            <th>ชั้นปี 3</th>
                            <th>ชั้นปี 4</th>
                            <th>รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_faculty = null;
                        $faculty_total = [];
                        $grand_total = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 'total' => 0];

                        foreach ($student_counts as $count) {
                            // Prepare faculty and department names
                            $faculty_name = $count['thai_faculty_name'] ?? $count['faculty_name'];
                            $department_name = $count['thai_department_name'] ?? $count['department_name'];
                            
                            // Initialize faculty totals if new faculty
                            if ($current_faculty !== $faculty_name) {
                                // Print previous faculty totals if exists
                                if ($current_faculty !== null) {
                                    echo '<tr class="table-secondary">';
                                    echo '<td colspan="2">รวม ' . $current_faculty . '</td>';
                                    echo '<td>' . number_format($faculty_total[1] ?? 0) . '</td>';
                                    echo '<td>' . number_format($faculty_total[2] ?? 0) . '</td>';
                                    echo '<td>' . number_format($faculty_total[3] ?? 0) . '</td>';
                                    echo '<td>' . number_format($faculty_total[4] ?? 0) . '</td>';
                                    echo '<td>' . number_format(
                                        ($faculty_total[1] ?? 0) + 
                                        ($faculty_total[2] ?? 0) + 
                                        ($faculty_total[3] ?? 0) + 
                                        ($faculty_total[4] ?? 0)
                                    ) . '</td>';
                                    echo '</tr>';
                                }
                                
                                // Reset faculty totals
                                $faculty_total = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
                                $current_faculty = $faculty_name;
                            }

                            // Accumulate data
                            $year = $count['study_year'];
                            $count_val = $count['student_count'];
                            
                            // Print row
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($faculty_name) . '</td>';
                            echo '<td>' . htmlspecialchars($department_name) . '</td>';
                            
                            // Print counts for each year
                            for ($y = 1; $y <= 4; $y++) {
                                $display_count = ($year == $y) ? $count_val : 0;
                                echo '<td>' . number_format($display_count) . '</td>';
                                
                                // Accumulate totals
                                $faculty_total[$y] += $display_count;
                                $grand_total[$y] += $display_count;
                                $grand_total['total'] += $display_count;
                            }
                            
                            // Total column
                            echo '<td>' . number_format($count_val) . '</td>';
                            echo '</tr>';
                        }

                        // Print last faculty totals
                        if ($current_faculty !== null) {
                            echo '<tr class="table-secondary">';
                            echo '<td colspan="2">รวม ' . $current_faculty . '</td>';
                            echo '<td>' . number_format($faculty_total[1]) . '</td>';
                            echo '<td>' . number_format($faculty_total[2]) . '</td>';
                            echo '<td>' . number_format($faculty_total[3]) . '</td>';
                            echo '<td>' . number_format($faculty_total[4]) . '</td>';
                            echo '<td>' . number_format(
                                $faculty_total[1] + 
                                $faculty_total[2] + 
                                $faculty_total[3] + 
                                $faculty_total[4]
                            ) . '</td>';
                            echo '</tr>';
                        }

                        // Grand total row
                        echo '<tr class="table-dark">';
                        echo '<td colspan="2">รวมทั้งหมด</td>';
                        echo '<td>' . number_format($grand_total[1]) . '</td>';
                        echo '<td>' . number_format($grand_total[2]) . '</td>';
                        echo '<td>' . number_format($grand_total[3]) . '</td>';
                        echo '<td>' . number_format($grand_total[4]) . '</td>';
                        echo '<td>' . number_format($grand_total['total']) . '</td>';
                        echo '</tr>';
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>