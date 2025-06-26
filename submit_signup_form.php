<?php
session_start(); // بدء الجلسة
include 'db_connect.php'; // تضمين ملف الاتصال بقاعدة البيانات

// ✅ اختبار طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method - Expected POST']);
    exit;
}

// ✅ اختبار وصول البيانات
if (!isset($_POST['first-name']) || !isset($_POST['email']) || !isset($_POST['password'])) {
    echo json_encode(['error' => 'Missing form fields']);
    exit;
}

$firstName = $_POST['first-name'];
$lastName = $_POST['last-name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // تشفير كلمة المرور

$sql = "INSERT INTO Guardian (firstName, lastName, email, password) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $firstName, $lastName, $email, $password);

if ($stmt->execute()) {
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['username'] = $firstName;
    echo json_encode(['message' => 'Signup successful']);
} else {
    echo json_encode(['error' => 'Signup failed']);
}

$stmt->close();
$conn->close();
?>
