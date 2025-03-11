['student_code'], 
                            2, $section['Course_Code'], 
                            3, $section_id, 
                            4, $current_semester['semester_number'], 
                            5, $current_semester['academic_year_id'], 
                            6, $section['Credits']);
        $registration_stmt->execute();

        // Update section current students
        $update_section_sql = "UPDATE course_sections 
                               SET current_students = current_students + 1 
                               WHERE section_id = ?";
        $update_section_stmt = $conn->prepare($update_section_sql);
        $update_section_stmt->bindParam(1, $section_id);
        $update_section_stmt->execute();

        // Track successfully registered courses
        $registered_courses[] = [
            'course_code' => $section['Course_Code'],
            'credits' => $section['Credits']
        ];
    }

    // Log registration activity
    $log_sql = "INSERT INTO logs (id_account, action, details) 
                VALUES (?, 'course_registration', ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_details = "Registered " . count($registered_courses) . " courses: " . 
                   implode(', ', array_column($registered_courses, 'course_code'));
    $log_stmt->bindParam(1, $_SESSION['user_id']);
    $log_stmt->bindParam(2, $log_details);
    $log_stmt->execute();

    // Commit transaction
    $conn->commit();

    // Prepare success message
    $success_message = "ลงทะเบียนสำเร็จ " . count($registered_courses) . " รายวิชา";
    if (!empty($registration_errors)) {
        $success_message .= " มี " . count($registration_errors) . " รายวิชาที่ไม่สามารถลงทะเบียนได้";
    }

    // Redirect with success/error messages
    header("Location: registration.php?success=" . urlencode($success_message) . 
           (!empty($registration_errors) ? 
            "&errors=" . urlencode(implode('; ', $registration_errors)) : 
            ""));
    exit();

} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();

    // Log error
    error_log("Course Registration Error: " . $e->getMessage());

    // Redirect with error message
    header("Location: registration.php?error=" . urlencode("เกิดข้อผิดพลาดในการลงทะเบียน: " . $e->getMessage()));
    exit();
} catch (Exception $e) {
    // Rollback transaction on any other error
    $conn->rollBack();

    // Log error
    error_log("Unexpected Course Registration Error: " . $e->getMessage());

    // Redirect with error message
    header("Location: registration.php?error=" . urlencode("เกิดข้อผิดพลาดที่ไม่คาดคิด: " . $e->getMessage()));
    exit();
}
?><?php
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
    // Start transaction
    $conn->beginTransaction();

    // Get student information
    $student_sql = "SELECT sd.student_code, sd.major_id, sd.Curriculum_ID
                    FROM student_details sd
                    WHERE sd.id_account = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(1, $_SESSION['user_id']);
    $student_stmt->execute();
    $student = $student_stmt->fetch();

    // Get current semester
    $semester_sql = "SELECT semester_id, semester_number, academic_year_id
                    FROM semesters 
                    WHERE is_current = 1 LIMIT 1";
    $semester_stmt = $conn->prepare($semester_sql);
    $semester_stmt->execute();
    $current_semester = $semester_stmt->fetch();

    // Validate input
    $registration_errors = [];
    $registered_courses = [];

    // Validate and process each course registration
    foreach ($_POST as $key => $section_id) {
        // Skip non-course inputs
        if (strpos($key, 'course_') !== 0) continue;

        // Extract course code
        $course_code = str_replace('course_', '', $key);

        // Validate section
        $section_sql = "SELECT cs.section_id, cs.Course_Code, cs.current_students, cs.max_students, c.Credits
                        FROM course_sections cs
                        JOIN course c ON cs.Course_Code = c.Course_Code
                        WHERE cs.section_id = ? 
                        AND cs.semester_id = ?
                        AND cs.status = 'active'";
        $section_stmt = $conn->prepare($section_sql);
        $section_stmt->bindParam(1, $section_id);
        $section_stmt->bindParam(2, $current_semester['semester_id']);
        $section_stmt->execute();
        $section = $section_stmt->fetch();

        // Validate section availability
        if (!$section) {
            $registration_errors[] = "กลุ่มเรียนไม่ถูกต้องหรือไม่พร้อมลงทะเบียน: $course_code";
            continue;
        }

        // Check if section is full
        if ($section['current_students'] >= $section['max_students']) {
            $registration_errors[] = "กลุ่มเรียนเต็ม: " . $course_code;
            continue;
        }

        // Insert course registration
        $registration_sql = "INSERT INTO course_registration 
                            (Student_ID, Course_Code, section_id, Semester, Academic_Year, Credits, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'registered')";
        $registration_stmt = $conn->prepare($registration_sql);
        $registration_stmt->bindParam(1, $student