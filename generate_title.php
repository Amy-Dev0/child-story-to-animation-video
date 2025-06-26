<?php
session_start();
include 'db_connect.php';

// Clear output buffer to prevent any unwanted output
ob_clean();

// Check if the user is logged in
if (!isset($_SESSION['guardianID'])) {
    $response = ["status" => "error", "message" => "User not logged in."];
    file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response) . "\n\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// Check if required POST data is present
if (!isset($_POST['title']) || !isset($_POST['language']) || !isset($_POST['voice'])) {
    $response = ["status" => "error", "message" => "Missing required fields: title, language, or voice."];
    file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response) . "\n\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

$title = trim($_POST['title']);
$language = trim($_POST['language']);
$voice = trim($_POST['voice']);

// Validate inputs
if (empty($title) || empty($language) || empty($voice)) {
    $response = ["status" => "error", "message" => "Title, language, and voice cannot be empty."];
    file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response) . "\n\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// Escape the arguments for the shell command
$title_arg = escapeshellarg($title);
$language_arg = escapeshellarg($language);
$voice_arg = escapeshellarg($voice);
$empty_arg = escapeshellarg(''); // For character_description and story_text

// Construct the command to call app.py with all expected arguments
$command = "python C:/xampp/htdocs/DremScribeAi/app.py $title_arg $empty_arg $empty_arg $language_arg $voice_arg --generate-from-title";

// Execute the command and capture output
exec("$command 2>&1", $output, $return_var);

// Log the command, output, and return code for debugging
$log = "Command: $command\n";
$log .= "Output: " . implode("\n", $output) . "\n";
$log .= "Return: $return_var\n";
file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', $log, FILE_APPEND);

// Check if the command was successful
if ($return_var !== 0) {
    $response = ["status" => "error", "message" => "Failed to generate story. Check logs for details."];
    file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response) . "\n\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// The last line of output should be the JSON response from app.py
$json_output = end($output);
file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Last Output Line: $json_output\n", FILE_APPEND);

// Parse the JSON output
$json_output = trim($json_output);
$result = json_decode($json_output, true);

if (!$result || !isset($result['status'])) {
    $response = ["status" => "error", "message" => "Invalid response from story generation. Raw output: " . $json_output];
    file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response) . "\n\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

if ($result['status'] !== "success") {
    $response = ["status" => "error", "message" => "Story generation failed: " . ($result['message'] ?? "Unknown error")];
    file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response) . "\n\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// Return the successful result
$response = [
    "status" => "success",
    "story" => $result['story'],
    "description" => $result['description']
];
file_put_contents('C:/xampp/htdocs/DremScribeAi/generate.log', "Response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n\n", FILE_APPEND);
echo json_encode($response);
?>