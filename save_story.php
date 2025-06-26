<?php  
session_start();  
include 'db_connect.php';  

if (!isset($_SESSION['guardianID'])) {  
    die("Error: Guardian not logged in. Session lost.");  
}  

$guardianID = $_SESSION['guardianID'];  

$stmt = $conn->prepare("SELECT childID, name, prohibitedWords, storyLanguage FROM child WHERE guardianID = ? ORDER BY childID DESC LIMIT 1");  
$stmt->bind_param("i", $guardianID);  
$stmt->execute();  
$result = $stmt->get_result();  
$child = $result->fetch_assoc();  
$stmt->close();  

if (!$child) {  
    die("Error: No child found for this guardian.");  
}  

$childID = $child['childID'];  
$childName = $child['name'];  
$prohibitedWords = explode(',', $child['prohibitedWords']);  
$storyLanguage = $child['storyLanguage'];  

if ($_SERVER["REQUEST_METHOD"] == "POST") {  
    $story_title = $_POST['story_title'];  
    $story = $_POST['story'];  
    $character_description = $_POST['character_description'];  
    $language = $_POST['language'];  
    $voice = $_POST['voice'];  
    $badWords = $prohibitedWords; // Use the prohibited words from the database

    // Check for prohibited words and replace them with ***
    $containsBadWord = false;
    foreach ($badWords as $word) {
        $word = trim($word);
        if (!empty($word) && stripos($story, $word) !== false) {
            $story = preg_replace("/\b(" . preg_quote($word, '/') . ")\b/i", "***", $story);
            $containsBadWord = true;
        }
    }

    if ($containsBadWord) {
        $_SESSION['errorMessage'] = "Your story contains inappropriate words. They have been replaced with (***). Please check your story before saving again.";
        header("Location: write_story.php");
        exit();
    }

    // حفظ القصة في جدول Story
    $stmt = $conn->prepare("INSERT INTO Story (childID, title, storyContent, characterDescription) VALUES (?, ?, ?, ?)");  
    $stmt->bind_param("isss", $childID, $story_title, $story, $character_description);  
    $stmt->execute();  
    $stmt->close();  

    // تحديث لغة القصة وصوت الراوي في جدول child
    $stmt = $conn->prepare("UPDATE child SET storyLanguage = ?, narratorVoice = ? WHERE childID = ?");  
    $stmt->bind_param("ssi", $language, $voice, $childID);  
    $stmt->execute();  
    $stmt->close();  

    // إعداد رسالة التحفيز
    $motivationMessage = ($language == "ar") ? "أحسنت يا $childName! أنت كاتب رائع!" : "Great job $childName! You're a wonderful storyteller!";
    $_SESSION['motivationMessage'] = $motivationMessage;
    $_SESSION['storyLanguage'] = $language;
    $_SESSION['showMessage'] = true;

    // إعادة التوجيه إلى write_story.php لعرض رسالة التحفيز
    header("Location: write_story.php");
    exit();  
}  
?>