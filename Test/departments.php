<?php
// Initialize session
session_start();

// Check if user is logged in and has academic role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['academic', 'teacher'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

try {
    // Fetch academic staff information
    $staff_sql = "SELECT a.*, up.* FROM account a 
            JOIN user_profiles up ON a.id_account = up.id_account
            WHERE a.id_account = ?";
            
    $staff_stmt = $conn->prepare($staff_sql);
    $staff_stmt->bindParam(1, $_SESSION['user_id']);
    $staff_stmt->execute();
    
    $user = $staff_stmt->fetch();

    // Fetch comprehensive department and faculty statistics
    $departments_sql = "SELECT 
        f.id as faculty_id,
        f.faculty_name,
        f.thai_faculty_name,
        f.description,
        f.dean,
        COUNT(DISTINCT d.department_id) as department_count,
        COUNT(DISTINCT m.major_id) as major_count,
        COUNT(DISTINCT sd.id_account) as student_count
    FROM faculty f
    LEFT JOIN department d ON f.id = d.faculty_id
    LEFT JOIN major m ON d.department_id = m.department_id
    LEFT JOIN student_details sd ON m.major_id = sd.major_id
    GROUP BY f.id
    ORDER BY student_count DESC";

    $departments_stmt = $conn->prepare($departments_sql);
    $departments_stmt->execute();
    $faculties = $departments_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Detailed department information
    $detailed_departments_sql = "SELECT 
        f.id as faculty_id,
        f.faculty_name,
        f.thai_faculty_name,
        d.department_id,
        d.department_name,
        d.thai_department_name,
        d.description,
        d.head_of_department,
        COUNT(DISTINCT m.major_id) as major_count,
        COUNT(DISTINCT sd.id_account) as student_count,
        GROUP_CONCAT(DISTINCT c.Curriculum_Name SEPARATOR ', ') as curricula
    FROM faculty f
    JOIN department d ON f.id = d.faculty_id
    LEFT JOIN major m ON d.department_id = m.department_id
    LEFT JOIN curriculum c ON m.Curriculum_ID = c.Curriculum_ID
    LEFT JOIN student_details sd ON m.major_id = sd.major_id
    GROUP BY d.department_id
    ORDER BY f.faculty_name, d.department_name";

    $detailed_departments_stmt = $conn->prepare($detailed_departments_sql);
    $detailed_departments_stmt->execute();
    $detailed_departments = $detailed_departments_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching department information: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คณะและภาควิชา - Suan Dusit University</title>
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

        .table-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Additional custom styles for departments page */
        .faculty-card {
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }

        .faculty-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .department-stats {
            display: flex;
            gap: 15px;
        }

        .department-stats .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Sidebar (same as academic_dashboard.php) -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University">
            <h3>Suan Dusit University</h3>
        </div>
        <a href="academic_dashboard.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
        <a href="students.php"><i class="fas fa-user-graduate"></i> ข้อมูลนักศึกษา</a>
        <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลอาจารย์</a>
        <a href="courses.php"><i class="fas fa-book"></i> รายวิชา</a>
        <a href="departments.php" class="active"><i class="fas fa-graduation-cap"></i> สาขา/หลักสูตร</a>
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
            <div class="dashboard-title">คณะและภาควิชา</div>
            <div class="search-container ms-auto">
                <input type="text" class="form-control" placeholder="ค้นหาคณะ/ภาควิชา" id="search-input">
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

        <!-- Faculty Overview -->
        <div class="row">
            <?php foreach ($faculties as $faculty): ?>
            <div class="col-md-4">
                <div class="card-stats faculty-card">
                    <div class="card-body">
                        <h4><?php echo htmlspecialchars($faculty['thai_faculty_name'] ?: $faculty['faculty_name']); ?></h4>
                        <div class="department-stats mt-3">
                            <div class="stat">
                                <strong><?php echo $faculty['department_count']; ?></strong>
                                <small>ภาควิชา</small>
                            </div>
                            <div class="stat">
                                <strong><?php echo $faculty['major_count']; ?></strong>
                                <small>สาขา</small>
                            </div>
                            <div class="stat">
                                <strong><?php echo $faculty['student_count']; ?></strong>
                                <small>นักศึกษา</small>
                            </div>
                        </div>
                        <p class="mt-3 text-muted small">
                            <?php echo htmlspecialchars(substr($faculty['description'] ?? 'ไม่มีคำอธิบาย', 0, 100) . '...'); ?>
                        </p>
                        <div class="text-muted mt-2">
                            <i class="fas fa-user-tie me-2"></i> 
                            <?php echo htmlspecialchars($faculty['dean'] ?? 'ยังไม่ระบุคณบดี'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Detailed Departments -->
        <div class="section-header">
            <i class="fas fa-building me-2"></i> รายละเอียดภาควิชา
        </div>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>คณะ</th>
                            <th>ภาควิชา</th>
                            <th>จำนวนสาขา</th>
                            <th>จำนวนนักศึกษา</th>
                            <th>หลักสูตร</th>
                            <th>หัวหน้าภาควิชา</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailed_departments as $department): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($department['thai_faculty_name'] ?: $department['faculty_name']); ?></td>
                            <td><?php echo htmlspecialchars($department['thai_department_name'] ?: $department['department_name']); ?></td>
                            <td><?php echo $department['major_count']; ?></td>
                            <td><?php echo $department['student_count']; ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($department['curricula'] ?? 'ไม่มีหลักสูตร', 0, 50) . '...'); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($department['head_of_department'] ?? 'ไม่ระบุ'); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="department_detail.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="department_edit.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle script (same as academic_dashboard.php)
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            sidebar.classList.toggle('hidden');
            content.classList.toggle('expanded');
            
            if (sidebar.classList.contains('hidden')) {
                content.style.marginLeft = '0';
            } else {
                content.style.marginLeft = '260px';
            }
        });

        // Search functionality
        document.getElementById('search-btn').addEventListener('click', function() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchInput) ? '' : 'none';
            });
        });
    </script>
</body>
</html>