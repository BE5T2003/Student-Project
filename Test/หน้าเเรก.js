document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("show-more-btn").addEventListener("click", function () {
        let transactionBody = document.getElementById("transaction-body");
        let showMoreBtn = document.getElementById("show-more-btn");

        if (showMoreBtn.innerText === "แสดงทั้งหมด ▼") {
            transactionBody.innerHTML += `
                <tr><td>4121316</td><td>การออกแบบกราฟิกส์และแอนิเมชันสำหรับธุรกิจดิจิทัล</td><td class="status">❌</td></tr>
                <tr><td>4747474</td><td>ปฏิบัติการการออกแบบกราฟิกส์และแอนิเมชันสำหรับธุรกิจดิจิทัล</td><td class="status">✅</td></tr>
                <tr><td>4774748</td><td>วิทยาศาสตร์และคณิตศาสตร์ในชีวิตประจำวัน/td><td class="status">❌</td></tr>
                <tr><td>4851659</td><td>ปรัชญา</td><td class="status">✅</td></tr>
                <tr><td>1104114</td><td>การตลาด</td><td class="status">✅</td></tr>
            `;
            showMoreBtn.innerText = "ซ่อนรายวิชา ▲";
        } else {
            transactionBody.innerHTML = transactionBody.innerHTML.split("<tr>").slice(0, 6).join("<tr>");
            showMoreBtn.innerText = "แสดงทั้งหมด ▼";
        }
    });

    function toggleSidebar() {
        document.querySelector(".sidebar").classList.toggle("hide");
        document.querySelector(".main-content").classList.toggle("full");
    }

    function navigateTo(page) {
        alert("กำลังเปลี่ยนไปยังหน้า: " + page);
    }

    function logout() {
        alert("กำลังออกจากระบบ...");
        window.location.href = "login.html";
    }
});
document.getElementById("searchInput").addEventListener("keyup", function() {
    let searchValue = this.value.toLowerCase();
    let rows = document.querySelectorAll("#studentTable tr");

    rows.forEach(row => {
        let studentName = row.cells[1].textContent.toLowerCase(); // คอลัมน์ชื่อ-นามสกุล
        let studentID = row.cells[0].textContent.toLowerCase(); // คอลัมน์รหัสนักศึกษา

        if (studentName.includes(searchValue) || studentID.includes(searchValue)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("hide");
    document.querySelector(".main-content").classList.toggle("full");
}

function navigateTo(page) {
    alert("กำลังเปลี่ยนไปยังหน้า: " + page);
}

function logout() {
    alert("กำลังออกจากระบบ...");
    window.location.href = "login.html";
    
}
function logout() {
    // แสดงข้อความยืนยันก่อนออกจากระบบ
    let confirmLogout = confirm("คุณต้องการออกจากระบบใช่หรือไม่?");
    if (confirmLogout) {
        // เปลี่ยนหน้าไปยัง login.html
        window.location.href = "../../Login/ล็อคอิน.html";
    }
}
function filterSubjects() {
    let input = document.getElementById("search-input").value.toLowerCase();
    let table = document.getElementById("transaction-body");
    let rows = table.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        let subjectId = rows[i].getElementsByTagName("td")[0]; // คอลัมน์เลขวิชา
        let subjectName = rows[i].getElementsByTagName("td")[1]; // คอลัมน์ชื่อวิชา
        
        if (subjectId || subjectName) {
            let idText = subjectId.textContent || subjectId.innerText;
            let nameText = subjectName.textContent || subjectName.innerText;

            // ถ้าข้อมูลตรงกับสิ่งที่พิมพ์ ให้แสดงแถว, ถ้าไม่ตรงให้ซ่อน
            if (idText.toLowerCase().includes(input) || nameText.toLowerCase().includes(input)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}
document.addEventListener("DOMContentLoaded", function () {
    // ดึง URL ของหน้าปัจจุบัน
    const currentPage = window.location.pathname.split("/").pop();

    // ลบ active class ออกจากทุกเมนู
    document.querySelectorAll(".menu li").forEach(item => {
        item.classList.remove("active");
    });

    // ตรวจสอบว่าตอนนี้อยู่หน้าไหน และเพิ่ม active class
    if (currentPage === "profile.html") {
        document.getElementById("profile-menu").classList.add("active");
    } else if (currentPage === "index.html" || currentPage === "") {
        document.getElementById("home-menu").classList.add("active");
    } else if (currentPage === "schedule.html") {
        document.getElementById("schedule-menu").classList.add("active");
    } else if (currentPage === "status.html") {
        document.getElementById("status-menu").classList.add("active");
    } else if (currentPage === "settings.html") {
        document.getElementById("settings-menu").classList.add("active");
    }
});
document.getElementById('toeicUpload').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const imgPreview = document.getElementById('toeicImage');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgPreview.src = e.target.result;
            imgPreview.style.display = "block"; // แสดงรูป
        };
        reader.readAsDataURL(file);
    }
});
