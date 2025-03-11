<?php
// Initialize session
session_start();

// Debug session
// echo "<pre>SESSION: "; print_r($_SESSION); echo "</pre>";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Debug redirect
    // echo "Redirecting based on role: " . $_SESSION['role'];
    
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
            // If role is not recognized, destroy session and reload page
            session_destroy();
            header("Location: index.php");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - Suan Dusit University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #95FCF2, #6EC7E2, #4691D3, #225EC4, #063D8C);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Sarabun', sans-serif;
        }

        .card {
            width: 400px;
            padding: 25px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #063D8C, #225EC4);
            border: none;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #063D8C, #4691D3);
        }

        .toggle-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 200px;
            background: white;
            border-radius: 20px;
            padding: 5px;
            cursor: pointer;
            position: relative;
            box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }

        .toggle-btn span {
            width: 50%;
            text-align: center;
            font-weight: bold;
            padding: 5px;
            cursor: pointer;
            position: relative;
            z-index: 2;
        }

        .toggle-slider {
            position: absolute;
            width: 50%;
            height: 100%;
            background: linear-gradient(135deg, #063D8C, #225EC4);
            border-radius: 20px;
            transition: 0.4s;
            left: 0;
            z-index: 1;
        }

        .form-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            height: 350px;
        }

        .form {
            position: absolute;
            width: 100%;
            transition: 0.5s;
        }

        .login-form {
            left: 0;
        }

        .register-form {
            left: 100%;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }

        h4 {
            font-weight: 900;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success-message {
            color: green;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="card text-center">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University"
                width="90">
        </div>
        <h4 class="mt-3">เข้าสู่ระบบ</h4>

        <!-- แสดงข้อความข้อผิดพลาด (ถ้ามี) -->
        <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>
        
        <!-- แสดงข้อความสำเร็จ (ถ้ามี) -->
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <div class="toggle-btn my-3" onclick="switchForm()">
            <div class="toggle-slider"></div>
            <span id="login-span">Login</span>
            <span id="register-span">Register</span>
        </div>

        <div class="form-container">
            <form class="form login-form" action="login_process.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            </form>

            <form class="form register-form" action="register_process.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="อีเมล" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
                </div>
                <div class="form-group">
                    <select name="role" class="form-control" required>
                        <option value="" selected disabled>เลือกบทบาทของคุณ</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="academic">Academic department</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="faculty" class="form-control" required>
                        <option value="" selected disabled>คณะ</option>
                        <option value="Management Science">คณะวิทยาการจัดการ</option>
                        <option value="Science and Technology">คณะวิทยาศาสตร์และเทคโนโลยี</option>
                        <option value="Tourism and Hospitality School">คณะโรงเรียนการท่องเที่ยวและการบริการ</option>
                        <option value="education">คณะครุศาสตร์</option>
                        <option value="Humanities and Social Sciences">คณะมนุษยศาสตร์และสังคมศาสตร์</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>
            </form>
        </div>
    </div>

    <script>
        let isLogin = true;

        function switchForm() {
            const loginForm = document.querySelector('.login-form');
            const registerForm = document.querySelector('.register-form');
            const toggleSlider = document.querySelector('.toggle-slider');
            const heading = document.querySelector('h4');

            if (isLogin) {
                loginForm.style.left = '-100%';
                registerForm.style.left = '0%';
                toggleSlider.style.left = '50%';
                heading.textContent = 'สมัครสมาชิก';
            } else {
                loginForm.style.left = '0%';
                registerForm.style.left = '100%';
                toggleSlider.style.left = '0';
                heading.textContent = 'เข้าสู่ระบบ';
            }
            isLogin = !isLogin;
        }
        
        // Switch to registration form if there was a registration error
        <?php if(isset($_GET['error']) && strpos($_GET['error'], 'สมัครสมาชิก') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            switchForm();
        });
        <?php endif; ?>
    </script>
</body>

</html>