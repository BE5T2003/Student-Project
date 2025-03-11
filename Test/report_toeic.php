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

// Fetch TOEIC Statistics
try {
    // Overall TOEIC Statistics
    $overall_stats_sql = "
        SELECT 
            COUNT(*) as total_tests,
            ROUND(AVG(TOEIC_Score), 2) as average_score,
            MIN(TOEIC_Score) as min_score,
            MAX(TOEIC_Score) as max_score,
            ROUND(STDDEV(TOEIC_Score), 2) as score_deviation
        FROM toeic
    ";
    $overall_stmt = $conn->query($overall_stats_sql);
    $overall_stats = $overall_stmt->fetch(PDO::FETCH_ASSOC);

    // TOEIC Scores by Major
    $major_stats_sql = "
        SELECT 
            m.major_name,
            m.thai_major_name,
            COUNT(t.TOEIC_ID) as test_count,
            ROUND(AVG(t.TOEIC_Score), 2) as average_score,
            MIN(t.TOEIC_Score) as min_score,
            MAX(t.TOEIC_Score) as max_score
        FROM toeic t
        JOIN student_details sd ON t.Student_ID = sd.student_code
        LEFT JOIN major m ON sd.major_id = m.major_id
        GROUP BY m.major_id, m.major_name, m.thai_major_name
        ORDER BY average_score DESC
    ";
    $major_stmt = $conn->query($major_stats_sql);
    $major_stats = $major_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Score Distribution
    $distribution_sql = "
        SELECT 
            CASE 
                WHEN TOEIC_Score < 200 THEN 'ต่ำกว่า 200'
                WHEN TOEIC_Score BETWEEN 200 AND 349 THEN '200-349'
                WHEN TOEIC_Score BETWEEN 350 AND 499 THEN '350-499'
                WHEN TOEIC_Score BETWEEN 500 AND 649 THEN '500-649'
                WHEN TOEIC_Score BETWEEN 650 AND 799 THEN '650-799'
                WHEN TOEIC_Score BETWEEN 800 AND 990 THEN '800-990'
                ELSE 'อื่นๆ'
            END as score_range,
            COUNT(*) as count
        FROM toeic
        GROUP BY score_range
        ORDER BY 
            CASE score_range
                WHEN 'ต่ำกว่า 200' THEN 1
                WHEN '200-349' THEN 2
                WHEN '350-499' THEN 3
                WHEN '500-649' THEN 4
                WHEN '650-799' THEN 5
                WHEN '800-990' THEN 6
                ELSE 7
            END
    ";
    $distribution_stmt = $conn->query($distribution_sql);
    $distribution_stats = $distribution_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching TOEIC statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายงานผลสอบ TOEIC - Suan Dusit University</title>
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
        .overall-stats {
            display: flex;
            justify-content: space-around;
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        .stat-item h4 {
            color: #3871c1;
            margin-bottom: 10px;
        }
        .distribution-bar {
            display: flex;
            height: 30px;
            margin-bottom: 10px;
        }
        .distribution-bar .bar {
            flex-grow: 1
        }
        .distribution-bar .bar-low { background-color: #dc3545; }
        .distribution-bar .bar-medium { background-color: #ffc107; }
        .distribution-bar .bar-high { background-color: #28a745; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="report-container">
            <div class="report-header">
                <h2>รายงานผลสอบ TOEIC</h2>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> พิมพ์รายงาน
                    </button>
                </div>
            </div>

            <div class="overall-stats">
                <div class="stat-item">
                    <h4>จำนวนผู้สอบ</h4>
                    <p class="h3"><?php echo number_format($overall_stats['total_tests']); ?></p>
                </div>
                <div class="stat-item">
                    <h4>คะแนนเฉลี่ย</h4>
                    <p class="h3"><?php echo number_format($overall_stats['average_score'], 2); ?></p>
                </div>
                <div class="stat-item">
                    <h4>คะแนนต่ำสุด</h4>
                    <p class="h3"><?php echo number_format($overall_stats['min_score']); ?></p>
                </div>
                <div class="stat-item">
                    <h4>คะแนนสูงสุด</h4>
                    <p class="h3"><?php echo number_format($overall_stats['max_score']); ?></p>
                </div>
                <div class="stat-item">
                    <h4>ส่วนเบี่ยงเบนมาตรฐาน</h4>
                    <p class="h3"><?php echo number_format($overall_stats['score_deviation'], 2); ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>คะแนน TOEIC รายสาขา</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>สาขาวิชา</th>
                                            <th>จำนวนผู้สอบ</th>
                                            <th>คะแนนเฉลี่ย</th>
                                            <th>คะแนนต่ำสุด</th>
                                            <th>คะแนนสูงสุด</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($major_stats as $major): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    echo htmlspecialchars(
                                                        $major['thai_major_name'] ?? 
                                                        $major['major_name'] ?? 
                                                        'ไม่ระบุ'
                                                    ); 
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($major['test_count']); ?></td>
                                                <td><?php echo number_format($major['average_score'], 2); ?></td>
                                                <td><?php echo number_format($major['min_score']); ?></td>
                                                <td><?php echo number_format($major['max_score']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>การกระจายคะแนน</h3>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Find the max count for percentage calculation
// Find the max count for percentage calculation
$max_count = !empty($distribution_stats) ? 
    max(array_column($distribution_stats, 'count')) : 
    1; // Default to 1 to prevent division by zero
?>

<!-- In the distribution section -->
<?php if (empty($distribution_stats)): ?>
    <div class="alert alert-info">
        ยังไม่มีข้อมูลการกระจายคะแนน TOEIC
    </div>
<?php else: ?>
    <?php 
    foreach ($distribution_stats as $dist): 
        $percentage = $max_count > 0 ? 
            round(($dist['count'] / $max_count) * 100) : 
            0;
        $bar_class = match(true) {
            $dist['score_range'] === 'ต่ำกว่า 200' => 'bar-low',
            $dist['score_range'] === '800-990' => 'bar-high',
            default => 'bar-medium'
        };
    ?>
        <div class="mb-2">
            <div class="d-flex justify-content-between">
                <span><?php echo htmlspecialchars($dist['score_range']); ?></span>
                <span><?php echo number_format($dist['count']); ?> คน</span>
            </div>
            <div class="distribution-bar">
                <div class="bar <?php echo $bar_class; ?>" 
                     style="width: <?php echo $percentage; ?>%">
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>