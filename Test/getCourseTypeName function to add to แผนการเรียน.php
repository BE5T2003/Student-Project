// Function to get course type name
function getCourseTypeName($typeID) {
    switch ($typeID) {
        case '1':
            return "วิชาทั่วไป";
        case '2':
            return "วิชาเฉพาะ";
        case '3':
            return "วิชาเสรี";
        default:
            return "ไม่ระบุ";
    }
}