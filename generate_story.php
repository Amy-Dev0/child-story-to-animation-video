<?php
session_start();
header('Content-Type: application/json');

$story_title = isset($_POST['story_title']) ? trim($_POST['story_title']) : '';
$character_description = isset($_POST['character_description']) ? trim($_POST['character_description']) : '';
$story_text = isset($_POST['story']) ? trim($_POST['story']) : '';
$language = isset($_POST['language']) ? trim($_POST['language']) : 'en';
$voice = isset($_POST['voice']) ? trim($_POST['voice']) : 'male';
$childID = isset($_POST['childID']) ? intval($_POST['childID']) : 0;

if (empty($story_title) || empty($character_description) || empty($story_text) || empty($childID)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

$story_title_arg = escapeshellarg($story_title);
$character_description_arg = escapeshellarg($character_description);
$story_text_arg = escapeshellarg($story_text);
$language_arg = escapeshellarg($language);
$voice_arg = escapeshellarg($voice);

// Call app.py without --generate-from-title flag for video generation
$command = "python C:/xampp/htdocs/DreamScribeAi/app.py $story_title_arg $character_description_arg $story_text_arg $language_arg $voice_arg";
exec("$command 2>&1", $output, $return_var);

// Combine output lines into a single string
$output_str = implode("\n", $output);

// Log the command and output for debugging
file_put_contents('C:/xampp/htdocs/DreamScribeAi/generate.log', "Command: $command\nOutput: $output_str\nReturn: $return_var\n", FILE_APPEND);

// The last three lines of output should be audio path, video path, and thumbnail path
$lines = array_filter(array_map('trim', $output));
$last_three_lines = array_slice($lines, -3);

// Extract paths
$audioFilePath = $last_three_lines[0] ?? '';
$videoFilePath = $last_three_lines[1] ?? '';
$thumbnailPath = $last_three_lines[2] ?? '';

if ($return_var !== 0 || empty($audioFilePath) || empty($videoFilePath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to generate video. Check generate.log for details.', 'output' => $output_str]);
    exit();
}

include 'db_connect.php';

$stmt = $conn->prepare("INSERT INTO story (childID, title, storyContent, characterDescription, audioFilePath, videoFilePath, thumbnailPath) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss", $childID, $story_title, $story_text, $character_description, $audioFilePath, $videoFilePath, $thumbnailPath);

if ($stmt->execute()) {
    $_SESSION['showMessage'] = true;
    $_SESSION['motivationMessage'] = "Great job! Your story has been generated successfully!";
    $_SESSION['storyLanguage'] = $language;
    echo json_encode(['status' => 'success', 'redirect' => 'write_story.php']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save story to database']);
}

$stmt->close();
$conn->close();
?>