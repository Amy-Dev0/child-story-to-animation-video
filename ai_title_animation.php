<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['guardianID'])) {
    header("Location: login.php");
    exit;
}

// Retrieve parameters from the URL
$story = isset($_GET['story']) ? urldecode($_GET['story']) : '';
$language = isset($_GET['language']) ? urldecode($_GET['language']) : 'English';
$voice = isset($_GET['voice']) ? urldecode($_GET['voice']) : 'Female';
$video_paths = isset($_GET['video_paths']) ? json_decode(urldecode($_GET['video_paths']), true) : [];
$audio_paths = isset($_GET['audio_paths']) ? json_decode(urldecode($_GET['audio_paths']), true) : [];
$thumbnail_path = isset($_GET['thumbnail_path']) ? urldecode($_GET['thumbnail_path']) : '';

// Debug: Log the received parameters
error_log("ai_title_animation.php - Received parameters: " . print_r($_GET, true));

// Validate video paths
$video_file = !empty($video_paths) ? $video_paths[0] : '';
$absolute_video_path = !empty($video_file) ? "C:/xampp/htdocs/DreamScribeAi/" . $video_file : '';
if (empty($video_file) || !file_exists($absolute_video_path)) {
    $error = "Video file not found at: " . htmlspecialchars($absolute_video_path);
    error_log("ai_title_animation.php - Error: $error");
} else {
    error_log("ai_title_animation.php - Video file found at: $absolute_video_path");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream Scribe AI - AI Title Story Animation</title>
    <link href="https://fonts.googleapis.com/css2?family=Annie+Use+Your+Telescope&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            background: url('images/8987826.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/paper-fibers.png');
            opacity: 0.3;
            z-index: 0;
        }

        .storybook-border {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 20px solid transparent;
            border-image: url('https://www.transparenttextures.com/patterns/old-wall.png') 30 stretch;
            z-index: 1;
        }

        .navbar {
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
        }

        .navbar h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2.5em;
            color: #003a53;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .nav-links {
            display: flex;
            align-items: center;
        }

        .nav-links a {
            font-family: 'Open Sans', sans-serif;
            color: #003a53;
            text-decoration: none;
            margin: 0 20px;
            font-size: 1.1em;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #ffcc00;
        }

        .logout-btn {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #ffcc00;
            color: #003a53;
        }

        .header-section {
            padding: 50px 40px;
            z-index: 2;
            text-align: center;
        }

        .header-content {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-content h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 3em;
            color: #003a53;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .main-section {
            padding: 50px 40px;
            z-index: 2;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        .main-container h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2em;
            color: #003a53;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .story-text {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .video-container {
            margin-top: 20px;
        }

        video {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            color: red;
            font-size: 1.1em;
            margin-top: 20px;
        }

        .button {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 12px 20px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
            margin-top: 20px;
        }

        .button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <!-- Navbar -->
    <div class="navbar">
        <h1>DreamScribeAI</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>YOUR AI TITLE STORY</h1>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="main-container">
            <h2><?php echo htmlspecialchars($story); ?></h2>
            <div class="story-text">
                <?php echo htmlspecialchars($story); ?>
            </div>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif (!empty($video_paths)): ?>
                <div class="video-container">
                    <video controls>
                        <source src="<?php echo htmlspecialchars($video_file); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php endif; ?>
            <a href="ai_title_tool.php" class="button">Generate Another Story</a>
        </div>
    </div>

    <script>
        // Debug: Log video source path
        console.log("Video source path: <?php echo htmlspecialchars($video_file); ?>");
    </script>
</body>
</html>