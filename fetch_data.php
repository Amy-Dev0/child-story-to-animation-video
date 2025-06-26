<?php
session_start();

// التحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['guardianID'])) {
    echo json_encode(['error' => 'Guardian not logged in']);
    http_response_code(401); // Unauthorized
    exit();
}

// تضمين ملف الاتصال بقاعدة البيانات
include 'db_connect.php';

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Invalid request method']);
    http_response_code(405); // Method Not Allowed
    exit();
}

// الحصول على اسم الجدول من الطلب (إذا تم إرساله)
$table = isset($_GET['table']) ? $_GET['table'] : '';

// قائمة الجداول المسموح بها
$allowedTables = ['Guardian', 'admin', 'child', 'Story', 'Library', 'CoreAI'];

// التحقق من أن اسم الجدول مسموح به
if (in_array($table, $allowedTables)) {
    // استخدام prepared statement
    $limit = 100; // الحد الأقصى لعدد النتائج
    $sql = "SELECT * FROM $table LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = array();
    while ($row = $result->fetch_assoc()) {
        // لا ترسل كلمة المرور المشفرة في الاستجابة
        unset($row['password']);
        $data[] = $row;
    }

    // إرسال البيانات كـ JSON
    echo json_encode($data);

    $stmt->close();
} else {
    // إذا كان اسم الجدول غير صحيح
    echo json_encode(['error' => 'Invalid table name']);
    http_response_code(400); // Bad Request
}

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>
