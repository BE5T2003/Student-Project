<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://upload.wikimedia.org/wikipedia/th/1/16/SDU2016.png" alt="Suan Dusit University">
            <h3>Suan Dusit University</h3>
        </div>
        <a href="#">แดชบอร์ด</a>
        <a href="#">ข้อมูลนักศึกษา</a>
        <a href="#">ข้อมูลอาจารย์</a>
        <a href="#">รายวิชา</a>
        <a href="#">แผนการเรียน</a>
        <a href="#">รายงาน</a>
        <a href="ล็อคอิน.html">ออกจากระบบ</a>
    </div>
    
    <div class="content" id="content">
        <div class="topbar">
            <button class="menu-toggle" id="menu-toggle">☰</button>
            <div class="dashboard-title">การจัดการนักศึกษา </div>
            <div class="search-container ms-auto">
                <input type="text" class="form-control w-50" placeholder="ค้นหาที่นี่" id="search-input">
                <button class="btn btn-light" id="search-btn">🔍</button>
            </div>
            <div class="user-info">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User Profile">
                <div>
                    <strong>Olivia Wilson</strong>
                    <p class="m-0">Silver Member</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card p-3">
                    <h4>สาขา</h4>
                    <p><strong>121211</strong> เทคโนโลยีสารสนเทศ</p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="section-header">
                    <h4>รายชื่อนักศึกษา</h4>
                </div>
                <label for="year-filter">เลือกชั้นปี:</label>
                <select id="year-filter" class="form-control">
                    <option value="all">ทั้งหมด</option>
                    <option value="1">ปี 1</option>
                    <option value="2">ปี 2</option>
                    <option value="3">ปี 3</option>
                    <option value="4">ปี 4</option>
                </select>
                <table class="table table-bordered transaction-table" id="student-table">
                    <thead>
                        <tr>
                            <th>ชื่อนักเรียน</th>
                            <th>รหัส</th>
                            <th>สาขา</th>
                            <th>Toeic</th>
                        </tr>
                    </thead>
                    <tbody id="student-list">
                        <!-- Student data will be loaded dynamically -->
                    </tbody>
                </table>
                <button id="show-more-btn" class="btn btn-primary mt-3">ดูรายชื่อทั้งหมด</button>
            </div>
            
            <!-- ปุ่มใต้ตาราง -->
            <div class="button-container">
                <button class="btn btn-primary btn-sm" id="edit-btn">แก้ไขรายชื่อ</button>
                <button class="btn btn-success btn-sm" id="add-btn">เพิ่มรายชื่อ</button>
                <button class="btn btn-danger btn-sm" id="delete-btn">ลบรายชื่อ</button>
            </div>
        </div>
        
        <div class="print-btn-container">
            <button class="print-btn" id="print-btn">🖨 Print</button>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">เพิ่มรายชื่อนักศึกษา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <div class="mb-3">
                            <label for="add-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="add-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add-email" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="add-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-first-name" class="form-label">ชื่อ (ภาษาอังกฤษ)</label>
                            <input type="text" class="form-control" id="add-first-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-last-name" class="form-label">นามสกุล (ภาษาอังกฤษ)</label>
                            <input type="text" class="form-control" id="add-last-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-thai-first-name" class="form-label">ชื่อ (ภาษาไทย)</label>
                            <input type="text" class="form-control" id="add-thai-first-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-thai-last-name" class="form-label">นามสกุล (ภาษาไทย)</label>
                            <input type="text" class="form-control" id="add-thai-last-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-student-code" class="form-label">รหัสนักศึกษา</label>
                            <input type="text" class="form-control" id="add-student-code" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-major-id" class="form-label">สาขา</label>
                            <select class="form-control" id="add-major-id" required>
                                <option value="1">เทคโนโลยีสารสนเทศ</option>
                                <!-- Add more options from the database -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add-study-year" class="form-label">ชั้นปี</label>
                            <select class="form-control" id="add-study-year" required>
                                <option value="1">ปี 1</option>
                                <option value="2">ปี 2</option>
                                <option value="3">ปี 3</option>
                                <option value="4">ปี 4</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="save-add-btn">บันทึก</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">แก้ไขข้อมูลนักศึกษา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <div class="mb-3">
                            <label for="edit-student-code" class="form-label">รหัสนักศึกษา</label>
                            <input type="text" class="form-control" id="edit-student-code" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit-thai-first-name" class="form-label">ชื่อ (ภาษาไทย)</label>
                            <input type="text" class="form-control" id="edit-thai-first-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-thai-last-name" class="form-label">นามสกุล (ภาษาไทย)</label>
                            <input type="text" class="form-control" id="edit-thai-last-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-major-id" class="form-label">สาขา</label>
                            <select class="form-control" id="edit-major-id">
                                <option value="1">เทคโนโลยีสารสนเทศ</option>
                                <!-- Add more options from the database -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-toeic-score" class="form-label">คะแนน TOEIC</label>
                            <input type="number" class="form-control" id="edit-toeic-score" min="0" max="990">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="save-edit-btn">บันทึก</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Student Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStudentModalLabel">ยืนยันการลบข้อมูลนักศึกษา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>คุณต้องการลบข้อมูลนักศึกษารหัส <span id="delete-student-code"></span> ใช่หรือไม่?</p>
                    <p class="text-danger">คำเตือน: การลบข้อมูลไม่สามารถเรียกคืนได้</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">ลบข้อมูล</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variable to track displayed state
        let showingAllStudents = false;
        let selectedStudentCode = null;
        
        // Load students when page loads
        $(document).ready(function() {
            loadStudents();
            
            // Menu toggle
            $("#menu-toggle").click(function() {
                $("#sidebar").toggleClass("hidden");
                $("#content").toggleClass("expanded");
            });
            
            // Year filter change
            $("#year-filter").change(function() {
                let selectedYear = $(this).val();
                if (selectedYear === "all") {
                    loadStudents();
                } else {
                    filterStudentsByYear(selectedYear);
                }
            });
            
            // Show more button
            $("#show-more-btn").click(function() {
                showingAllStudents = !showingAllStudents;
                if (showingAllStudents) {
                    $(this).text("ซ่อนรายชื่อทั้งหมด");
                    loadAllStudents();
                } else {
                    $(this).text("ดูรายชื่อทั้งหมด");
                    loadStudents();
                }
            });
            
            // Search button
            $("#search-btn").click(function() {
                let searchTerm = $("#search-input").val().trim();
                if (searchTerm) {
                    searchStudents(searchTerm);
                } else {
                    loadStudents();
                }
            });
            
            // Add student button
            $("#add-btn").click(function() {
                $("#addStudentModal").modal('show');
            });
            
            // Save new student
            $("#save-add-btn").click(function() {
                addStudent();
            });
            
            // Edit student button
            $("#edit-btn").click(function() {
                if (selectedStudentCode) {
                    openEditModal(selectedStudentCode);
                } else {
                    alert("กรุณาเลือกนักศึกษาที่ต้องการแก้ไข");
                }
            });
            
            // Save edited student
            $("#save-edit-btn").click(function() {
                updateStudent();
            });
            
            // Delete student button
            $("#delete-btn").click(function() {
                if (selectedStudentCode) {
                    $("#delete-student-code").text(selectedStudentCode);
                    $("#deleteStudentModal").modal('show');
                } else {
                    alert("กรุณาเลือกนักศึกษาที่ต้องการลบ");
                }
            });
            
            // Confirm delete student
            $("#confirm-delete-btn").click(function() {
                deleteStudent(selectedStudentCode);
            });
            
            // Print button
            $("#print-btn").click(function() {
                window.print();
            });
            
            // Row selection functionality
            $(document).on('click', '#student-list tr', function() {
                // Highlight selected row
                $('#student-list tr').removeClass('table-primary');
                $(this).addClass('table-primary');
                
                // Store selected student code
                selectedStudentCode = $(this).find('td:nth-child(2)').text();
            });
        });
        
        // Load limited number of students
        function loadStudents() {
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: {
                    action: 'getAll'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayStudents(response.students.slice(0, 6)); // Show first 6 students
                    } else {
                        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Load all students
        function loadAllStudents() {
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: {
                    action: 'getAll'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayStudents(response.students); // Show all students
                    } else {
                        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Filter students by year
        function filterStudentsByYear(year) {
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: {
                    action: 'getByYear',
                    study_year: year
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayStudents(response.students);
                    } else {
                        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Search students
        function searchStudents(searchTerm) {
            // Load all students and filter on client side
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: {
                    action: 'getAll'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Filter students based on search term
                        let filteredStudents = response.students.filter(function(student) {
                            return (
                                student.thai_first_name.includes(searchTerm) || 
                                student.thai_last_name.includes(searchTerm) || 
                                student.student_code.includes(searchTerm)
                            );
                        });
                        
                        displayStudents(filteredStudents);
                    } else {
                        alert('เกิดข้อผิดพลาดในการค้นหาข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Display students in table
        function displayStudents(students) {
            let tableBody = $("#student-list");
            tableBody.empty();
            
            if (students.length === 0) {
                tableBody.append('<tr><td colspan="4" class="text-center">ไม่พบข้อมูลนักศึกษา</td></tr>');
                return;
            }
            
            students.forEach(function(student) {
                let hasToeic = student.TOEIC_Score && student.TOEIC_Score >= 0;
                let toeicCell = hasToeic 
                    ? student.TOEIC_Score + ' <span class="tick">✓</span>'
                    : 'ยังไม่มีคะแนน';
                
                let row = '<tr data-code="' + student.student_code + '">' +
                          '<td>' + student.thai_first_name + ' ' + student.thai_last_name + '</td>' +
                          '<td>' + student.student_code + '</td>' +
                          '<td>' + (student.thai_major_name || 'ไม่ระบุ') + '</td>' +
                          '<td>' + toeicCell + '</td>' +
                          '</tr>';
                
                tableBody.append(row);
            });
        }
        
        // Add new student
        function addStudent() {
            let studentData = {
                action: 'add',
                username: $("#add-username").val(),
                email: $("#add-email").val(),
                password: $("#add-password").val(),
                first_name: $("#add-first-name").val(),
                last_name: $("#add-last-name").val(),
                thai_first_name: $("#add-thai-first-name").val(),
                thai_last_name: $("#add-thai-last-name").val(),
                student_code: $("#add-student-code").val(),
                major_id: $("#add-major-id").val(),
                study_year: $("#add-study-year").val()
            };
            
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: studentData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('เพิ่มข้อมูลนักศึกษาเรียบร้อยแล้ว');
                        $("#addStudentModal").modal('hide');
                        $("#addStudentForm")[0].reset();
                        loadStudents();
                    } else {
                        alert('เกิดข้อผิดพลาดในการเพิ่มข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Open edit modal with student data
        function openEditModal(studentCode) {
            // Find student in table row
            let row = $(`#student-list tr[data-code="${studentCode}"]`);
            if (row.length === 0) return;
            
            let fullName = row.find('td:nth-child(1)').text();
            let nameParts = fullName.split(' ');
            let firstName = nameParts[0];
            let lastName = nameParts.slice(1).join(' ');
            let major = row.find('td:nth-child(3)').text();
            let toeicText = row.find('td:nth-child(4)').text();
            let toeicScore = toeicText.match(/\d+/);
            
            // Populate edit form
            $("#edit-student-code").val(studentCode);
            $("#edit-thai-first-name").val(firstName);
            $("#edit-thai-last-name").val(lastName);
            
            // Set major dropdown
            if (major === 'เทคโนโลยีสารสนเทศ') {
                $("#edit-major-id").val(1);
            }
            
            // Set TOEIC score if available
            if (toeicScore) {
                $("#edit-toeic-score").val(toeicScore[0]);
            } else {
                $("#edit-toeic-score").val('');
            }
            
            // Show edit modal
            $("#editStudentModal").modal('show');
        }
        
        // Update student data
        function updateStudent() {
            let studentData = {
                action: 'update',
                student_code: $("#edit-student-code").val(),
                thai_first_name: $("#edit-thai-first-name").val(),
                thai_last_name: $("#edit-thai-last-name").val(),
                major_id: $("#edit-major-id").val()
            };
            
            // Only include TOEIC score if it's provided
            if ($("#edit-toeic-score").val()) {
                studentData.toeic_score = $("#edit-toeic-score").val();
            }
            
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: studentData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('อัปเดตข้อมูลนักศึกษาเรียบร้อยแล้ว');
                        $("#editStudentModal").modal('hide');
                        loadStudents();
                    } else {
                        alert('เกิดข้อผิดพลาดในการอัปเดตข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Delete student
        function deleteStudent(studentCode) {
            $.ajax({
                url: 'student_operations.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    student_code: studentCode
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('ลบข้อมูลนักศึกษาเรียบร้อยแล้ว');
                        $("#deleteStudentModal").modal('hide');
                        selectedStudentCode = null;
                        loadStudents();
                    } else {
                        alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                    }
                },
                error: function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }