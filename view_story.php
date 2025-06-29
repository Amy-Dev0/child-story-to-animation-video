<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['guardianID'])) {
    die("Error: Guardian not logged in.");
}

$guardianID = $_SESSION['guardianID'];

// جلب آخر طفل مسجل لهذا الوصي
$stmt = $conn->prepare("SELECT childID, name, storyLanguage FROM child WHERE guardianID = ? ORDER BY childID DESC LIMIT 1");
$stmt->bind_param("i", $guardianID);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();
$stmt->close();

if (!$child) {
    die("Error: No child found for this guardian.");
}

$childID = $child['childID'];
$storyLanguage = $child['storyLanguage'];

// جلب تفاصيل القصة
if (!isset($_GET['storyID'])) {
    die("Error: Story ID not provided.");
}

$storyID = $_GET['storyID'];
$stmt = $conn->prepare("SELECT title, storyContent, characterDescription, videoFilePath, thumbnailPath FROM Story WHERE storyID = ? AND childID = ?");
$stmt->bind_param("ii", $storyID, $childID);
$stmt->execute();
$result = $stmt->get_result();
$story = $result->fetch_assoc();
$stmt->close();

if (!$story) {
    die("Error: Story not found or you do not have access to it.");
}

$baseUrl = "http://localhost/DreamScribeAi/";
$videoUrl = $story['videoFilePath'] ? $baseUrl . $story['videoFilePath'] : null;
$thumbnailUrl = $story['thumbnailPath'] ? $baseUrl . $story['thumbnailPath'] : null;
?>

<!DOCTYPE html>
<html lang="<?php echo $storyLanguage; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Story</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            direction: <?php echo ($storyLanguage == "ar") ? "rtl" : "ltr"; ?>;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
        }
        .video-player {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 0 auto 20px;
        }
        .video-player video {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
        .video-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #333;
            padding: 10px;
            border-radius: 0 0 8px 8px;
            color: #fff;
        }
        .video-controls button {
            background: none;
            border: none;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        .video-controls button:hover {
            color: #2196F3;
        }
        .story-details {
            margin-top: 20px;
        }
        .story-details p {
            margin: 10px 0;
        }
        .thumbnail {
            max-width: 200px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2196F3;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo ($storyLanguage == "ar") ? "عرض القصة" : "View Story"; ?></h1>

        <?php if ($thumbnailUrl): ?>
            <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" alt="Story Thumbnail" class="thumbnail" onerror="this.src='https://via.placeholder.com/200';">
        <?php endif; ?>

        <?php if ($videoUrl): ?>
            <div class="video-player">
                <video id="storyVideo" preload="auto">
                    <source src="<?php echo htmlspecialchars($videoUrl); ?>" type="video/mp4">
                    <?php echo ($storyLanguage == "ar") ? "المتصفح الخاص بك لا يدعم تشغيل الفيديو." : "Your browser does not support video playback."; ?>
                </video>
                <div class="video-controls">
                    <button onclick="playPauseVideo()">► / ||</button>
                    <button onclick="toggleMute()">🔊</button>
                    <button onclick="toggleFullScreen()">⛶</button>
                </div>
            </div>
        <?php else: ?>
            <p><?php echo ($storyLanguage == "ar") ? "لم يتم العثور على الفيديو." : "No video found."; ?></p>
        <?php endif; ?>

        <div class="story-details">
            <h2><?php echo htmlspecialchars($story['title']); ?></h2>
            <p><strong><?php echo ($storyLanguage == "ar") ? "القصة:" : "Story:"; ?></strong> <?php echo htmlspecialchars($story['storyContent']); ?></p>
            <p><strong><?php echo ($storyLanguage == "ar") ? "وصف الشخصية:" : "Character Description:"; ?></strong> <?php echo htmlspecialchars($story['characterDescription']); ?></p>
        </div>

        <a href="animation_video.php" class="btn">
            <?php echo ($storyLanguage == "ar") ? "العودة إلى فيديو الرسوم المتحركة" : "Back to Animation Video"; ?>
        </a>
    </div>

    <script>
        const video = document.getElementById('storyVideo');

        function playPauseVideo() {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }

        function toggleMute() {
            video.muted = !video.muted;
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                video.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    </script>
</body>
</html>