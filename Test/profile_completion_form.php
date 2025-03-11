<?php
// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=โปรดเข้าสู่ระบบก่อนใช้งาน");
    exit();
}

// Include database connection
require_once 'db_connect.php';

try {
    // Check if profile is already completed
    $check_profile_sql = "SELECT * FROM user_profiles WHERE id_account = ? AND 
                          (first_name IS NOT NULL AND last_name IS NOT NULL AND 
                           thai_first_name IS NOT NULL AND thai_last_name IS NOT NULL)";
    $check_profile_stmt = $conn->prepare($check_profile_sql);
    $check_profile_stmt->bindParam(1, $_SESSION['user_id']);
    $check_profile_stmt->execute();
    
    // If profile is completed, redirect to dashboard
    if ($check_profile_stmt->rowCount() > 0) {
        $profile = $check_profile_stmt->fetch();
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'student':
                header("Location: student_dashboard.php");
                break;
            case 'teacher':
                header("Location: teacher_dashboard.php");
                break;
            case 'academic':
                header("Location: academic_dashboard.php");
                break;
            default:
                header("Location: index.php");
                break;
        }
        exit();
    }
    
    // Get user info
    $user_sql = "SELECT a.username_account, a.email_account, a.Role_account 
                 FROM account a WHERE a.id_account = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bindParam(1, $_SESSION['user_id']);
    $user_stmt->execute();
    $user = $user_stmt->fetch();
    
    // Get faculty list
    $faculty_sql = "SELECT id, faculty_name, thai_faculty_name FROM faculty ORDER BY faculty_name";
    $faculty_stmt = $conn->prepare($faculty_sql);
    $faculty_stmt->execute();
    $faculties = $faculty_stmt->fetchAll();
    
    // Get department list (will be filtered by JavaScript)
    $department_sql = "SELECT department_id, faculty_id, department_name, thai_department_name 
                       FROM department ORDER BY department_name";
    $department_stmt = $conn->prepare($department_sql);
    $department_stmt->execute();
    $departments = $department_stmt->fetchAll();
    
    // Get majors list (will be filtered by JavaScript)
    $major_sql = "SELECT major_id, department_id, major_name, thai_major_name 
                  FROM major ORDER BY major_name";
    $major_stmt = $conn->prepare($major_sql);
    $major_stmt->execute();
    $majors = $major_stmt->fetchAll();
    
    // Get student specific info if role is student
    $student = null;
    if ($user['Role_account'] === 'student') {
        $student_sql = "SELECT * FROM student_details WHERE id_account = ?";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bindParam(1, $_SESSION['user_id']);
        $student_stmt->execute();
        $student = $student_stmt->fetch();
    }
    
    // Get teacher specific info if role is teacher
    $teacher = null;
    if ($user['Role_account'] === 'teacher') {
        $teacher_sql = "SELECT * FROM teacher_details WHERE id_account = ?";
        $teacher_stmt = $conn->prepare($teacher_sql);
        $teacher_stmt->bindParam(1, $_SESSION['user_id']);
        $teacher_stmt->execute();
        $teacher = $teacher_stmt->fetch();
    }
    
} catch (PDOException $e) {
    die("การดึงข้อมูลล้มเหลว: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กรอกข้อมูลส่วนตัว - Suan Dusit University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #063D8C, #225EC4);
            color: white;
            font-weight: bold;
            padding: 15px 20px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #ced4da;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #063D8C, #225EC4);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #225EC4, #4691D3);
        }
        
        .required-field::after {
            content: " *";
            color: red;
        }
        
        .logo-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University" width="80">
            <h2 class="mt-3">ระบบลงทะเบียนนักศึกษา</h2>
        </div>
        
        <div class="progress-container">
            <div class="progress">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p class="text-center mt-2">ขั้นตอน 1/4: กรอกข้อมูลส่วนตัว</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>กรอกข้อมูลส่วนตัว</h4>
            </div>
            <div class="card-body">
                <form action="save_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label required-field">ชื่อ (ภาษาอังกฤษ)</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label required-field">นามสกุล (ภาษาอังกฤษ)</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="thai_first_name" class="form-label required-field">ชื่อ (ภาษาไทย)</label>
                            <input type="text" class="form-control" id="thai_first_name" name="thai_first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="thai_last_name" class="form-label required-field">นามสกุล (ภาษาไทย)</label>
                            <input type="text" class="form-control" id="thai_last_name" name="thai_last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="phone" class="form-label required-field">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10}" title="กรุณาใส่หมายเลขโทรศัพท์ 10 หลัก" required>
                        </div>
                        <div class="col-md-6">
                            <label for="profile_image" class="form-label">รูปโปรไฟล์</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <label for="address" class="form-label required-field">ที่อยู่</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="faculty_id" class="form-label required-field">คณะ</label>
                            <select class="form-select" id="faculty_id" name="faculty_id" required onchange="updateDepartments()">
                                <option value="" selected disabled>เลือกคณะ</option>
                                <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo $faculty['id']; ?>">
                                    <?php echo $faculty['thai_faculty_name']; ?> (<?php echo $faculty['faculty_name']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="department_id" class="form-label required-field">ภาควิชา</label>
                            <select class="form-select" id="department_id" name="department_id" required onchange="updateMajors()" disabled>
                                <option value="" selected disabled>เลือกภาควิชา</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="major_id" class="form-label required-field">สาขาวิชา</label>
                            <select class="form-select" id="major_id" name="major_id" required disabled>
                                <option value="" selected disabled>เลือกสาขาวิชา</option>
                            </select>
                        </div>
                    </div>

                    <?php if ($user['Role_account'] === 'teacher'): ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>ข้อมูลอาจารย์</h5>
                            <hr>
                        </div>
                        <div class="col-md-6">
                            <label for="position" class="form-label required-field">ตำแหน่ง</label>
                            <input type="text" class="form-control" id="position" name="position" required>
                        </div>
                        <div class="col-md-6">
                            <label for="office_location" class="form-label">สถานที่ทำงาน</label>
                            <input type="text" class="form-control" id="office_location" name="office_location">
                        </div>
                        <div class="col-md-12">
                            <label for="expertise" class="form-label">ความเชี่ยวชาญ</label>
                            <textarea class="form-control" id="expertise" name="expertise" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="office_hours" class="form-label">ชั่วโมงทำการ</label>
                            <input type="text" class="form-control" id="office_hours" name="office_hours" placeholder="เช่น จันทร์-ศุกร์ 9:00-16:00">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Store departments and majors data
        const departmentsData = <?php echo json_encode($departments); ?>;
        const majorsData = <?php echo json_encode($majors); ?>;
        
        // Update departments dropdown based on selected faculty
        function updateDepartments() {
            const facultyId = document.getElementById('faculty_id').value;
            const departmentSelect = document.getElementById('department_id');
            
            // Reset department and major dropdowns
            departmentSelect.innerHTML = '<option value="" selected disabled>เลือกภาควิชา</option>';
            document.getElementById('major_id').innerHTML = '<option value="" selected disabled>เลือกสาขาวิชา</option>';
            document.getElementById('major_id').disabled = true;
            
            if (facultyId) {
                // Filter departments by faculty ID
                const filteredDepartments = departmentsData.filter(dep => dep.faculty_id == facultyId);
                
                // Add departments to dropdown
                filteredDepartments.forEach(dep => {
                    const option = document.createElement('option');
                    option.value = dep.department_id;
                    option.textContent = `${dep.thai_department_name} (${dep.department_name})`;
                    departmentSelect.appendChild(option);
                });
                
                departmentSelect.disabled = filteredDepartments.length === 0;
            } else {
                departmentSelect.disabled = true;
            }
        }
        
        // Update majors dropdown based on selected department
        function updateMajors() {
            const departmentId = document.getElementById('department_id').value;
            const majorSelect = document.getElementById('major_id');
            
            // Reset major dropdown
            majorSelect.innerHTML = '<option value="" selected disabled>เลือกสาขาวิชา</option>';
            
            if (departmentId) {
                // Filter majors by department ID
                const filteredMajors = majorsData.filter(maj => maj.department_id == departmentId);
                
                // Add majors to dropdown
                filteredMajors.forEach(maj => {
                    const option = document.createElement('option');
                    option.value = maj.major_id;
                    option.textContent = `${maj.thai_major_name} (${maj.major_name})`;
                    majorSelect.appendChild(option);
                });
                
                majorSelect.disabled = filteredMajors.length === 0;
            } else {
                majorSelect.disabled = true;
            }
        }
        
        // Preview profile image
        document.getElementById('profile_image').addEventListener('change', function(event) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview functionality here if needed
                    console.log("Image selected:", e.target.result);
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>