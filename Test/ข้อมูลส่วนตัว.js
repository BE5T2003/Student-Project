// ฟังก์ชัน Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    if (!sidebar) {
        console.error("ไม่พบ #sidebar ตรวจสอบ id ของ Sidebar!");
        return;
    }

    // เพิ่ม/ลบคลาส hide ให้กับ Sidebar
    sidebar.classList.toggle("hide");
    console.log("กดปุ่มสามขีด Sidebar เปลี่ยนสถานะ");

    // ปรับตำแหน่งของ topbar เมื่อ sidebar ถูกพับ
    const topbar = document.querySelector(".topbar");
    if (topbar) {
        topbar.classList.toggle("sidebar-active");
    }
}

// ฟังก์ชันนำทางไปยังหน้าอื่นๆ
function navigateTo(page) {
    console.log("กำลังไปที่หน้า: " + page);
    // เพิ่มโค้ดที่จำเป็นเพื่อเปลี่ยนหน้าไปที่หน้าที่เลือก
}

// ฟังก์ชันสำหรับออกจากระบบ
function logout() {
    console.log("ออกจากระบบแล้ว");
    // เพิ่มโค้ดที่จำเป็นเพื่อออกจากระบบ
}
// ฟังก์ชันแสดงรูปที่เลือกจาก input
function displayImage(event) {
    const file = event.target.files[0]; // รับไฟล์ที่เลือก
    const reader = new FileReader(); // สร้าง FileReader เพื่ออ่านไฟล์

    reader.onload = function(e) {
        const image = document.getElementById("toeicImage"); // หาภาพที่จะใช้แสดง
        image.src = e.target.result; // ตั้งค่า src ของ <img> เป็นผลลัพธ์จาก FileReader
        image.style.display = "block"; // ทำให้รูปแสดงออกมา
    };

    if (file) {
        reader.readAsDataURL(file); // อ่านไฟล์เป็น Data URL
    }
}
function navigateTo(page) {
    if (page === 'profile') {
        window.location.href = '../ข้อมูลส่วนตัว/ข้อมูลส่วนตัว.html'; // ไปที่ข้อมูลส่วนตัว
    } else if (page === 'home') {
        window.location.href = '../หน้าเเรก/หน้าเเรก.html'; // กลับไปหน้าแรก
    }
}
document.getElementById("searchInput").addEventListener("keyup", function() {
    let searchValue = this.value.toLowerCase();
    let rows = document.querySelectorAll("#studentTable tbody tr");

    rows.forEach(row => {
        let subjectName = row.cells[1].textContent.toLowerCase(); // คอลัมน์ชื่อวิชา
        let subjectID = row.cells[0].textContent.toLowerCase(); // คอลัมน์รหัสวิชา

        // ตรวจสอบว่า 'searchValue' มีอยู่ในชื่อวิชาหรือรหัสวิชา
        if (subjectName.includes(searchValue) || subjectID.includes(searchValue)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
