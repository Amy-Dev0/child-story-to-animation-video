<?php
// Load environment variables from .env in the same directory
$envFile = parse_ini_file(__DIR__ . '/.env', true);
if ($envFile === false) {
    error_log("Failed to parse .env file at " . __DIR__ . '/.env');
    // Fallback to hardcoded values if .env fails
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'dreamscribeai';
} else {
    foreach ($envFile as $key => $value) {
        putenv("$key=$value");
    }
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db = getenv('DB_NAME') ?: 'dreamscribeai';
}

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4"); // Support for Arabic
?>