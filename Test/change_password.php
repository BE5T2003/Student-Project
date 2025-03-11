<?php
// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$error_message = '';
$success_message = '';

try {
    // Get current user's information
    $sql = "SELECT a.*, up.* 
            FROM account a
            JOIN user_profiles up ON a.id_account = up.id_account
            WHERE a.id_account = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Process password change
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate current password
        if (!password_verify($current_password, $user['password_account'])) {
            $error_message = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } 
        // Validate new password length
        elseif (strlen($new_password) < 8) {
            $error_message = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        } 
        // Ensure new password meets complexity requirements
        elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $new_password)) {
            $error_message = 'รหัสผ่านต้องประกอบด้วยตัวอักษรพิมพ์ใหญ่ พิมพ์เล็ก และตัวเลข';
        } 
        // Confirm password match
        elseif ($new_password !== $confirm_password) {
            $error_message = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
        } 
        // Prevent using current password as new password
        elseif (password_verify($new_password, $user['password_account'])) {
            $error_message = 'ไม่สามารถใช้รหัสผ่านเดิมเป็นรหัสผ่านใหม่ได้';
        }
        else {
            try {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database
                $update_sql = "UPDATE account 
                               SET password_account = :password, 
                                   updated_at = NOW() 
                               WHERE id_account = :id_account";

                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':id_account', $user['id_account'], PDO::PARAM_INT);
                $update_stmt->execute();

                // Log password change
                $log_sql = "INSERT INTO logs (id_account, action, details, ip_address) 
                            VALUES (?, 'password_changed', 'User changed password', ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->execute([$user['id_account'], $_SERVER['REMOTE_ADDR']]);

                // Destroy session and redirect
                session_destroy();
                header("Location: login.php?password_changed=1");
                exit();

            } catch (PDOException $e) {
                $error_message = 'การเปลี่ยนรหัสผ่านล้มเหลว: ' . $e->getMessage();
            }
        }
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
    <title>เปลี่ยนรหัสผ่าน - Suan Dusit University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f6f9;
        }
        .password-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .password-strength {
            height: 5px;
            background-color: #e0e0e0;
            margin-top: 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
        }
        .sdu-logo {
            max-width: 150px;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University Logo" class="sdu-logo">
            
            <h2 class="text-center mb-4">เปลี่ยนรหัสผ่าน</h2>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="change-password-form">
                <div class="mb-3">
                    <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               required minlength="8" 
                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <small class="form-text text-muted">
                        รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์ใหญ่ พิมพ์เล็ก และตัวเลข
                    </small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock me-2"></i> เปลี่ยนรหัสผ่าน
                    </button>
                    <a href="settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> ย้อนกลับ
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // Password strength meter
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('password-strength-bar');

        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Check length
            if (password.length >= 8) strength++;
            
            // Check for lowercase
            if (/[a-z]/.test(password)) strength++;
            
            // Check for uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Check for number
            if (/\d/.test(password)) strength++;

            // Update strength bar
            const colors = ['#FF4136', '#FF851B', '#FFDC00', '#2ECC40', '#0074D9'];
            strengthBar.style.width = `${strength * 25}%`;
            strengthBar.style.backgroundColor = colors[strength - 1] || colors[0];
        });

        // Confirm password validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        const form = document.getElementById('change-password-form');

        form.addEventListener('submit', function(event) {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword !== confirmPassword) {
                event.preventDefault();
                alert('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
            }
        });
    </script>
</body>
</html>