<?php
session_start();
include 'db_connect.php';

// Check if the guardian is logged in
if (!isset($_SESSION['guardianID'])) {
    die("Error: Guardian not logged in. Session lost.");
}

$guardianID = $_SESSION['guardianID'];

// Fetch the childID for the logged-in guardian
$stmt = $conn->prepare("SELECT childID, name FROM child WHERE guardianID = ? ORDER BY childID DESC LIMIT 1");
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

// Handle story deletion
if (isset($_POST['deleteStoryID'])) {
    $deleteStoryID = $_POST['deleteStoryID'];

    // Fetch the file paths to delete
    $stmt = $conn->prepare("SELECT audioFilePath, videoFilePath, thumbnailPath FROM Story WHERE storyID = ? AND childID = ?");
    $stmt->bind_param("ii", $deleteStoryID, $childID);
    $stmt->execute();
    $result = $stmt->get_result();
    $story = $result->fetch_assoc();
    $stmt->close();

    if ($story) {
        // Delete the associated files
        $baseDir = "C:/xampp/htdocs/DreamScribeAi/";
        $filesToDelete = [$story['audioFilePath'], $story['videoFilePath'], $story['thumbnailPath']];
        foreach ($filesToDelete as $file) {
            if ($file && file_exists($baseDir . $file)) {
                unlink($baseDir . $file);
            }
        }

        // Delete the story record from the database
        $stmt = $conn->prepare("DELETE FROM Story WHERE storyID = ? AND childID = ?");
        $stmt->bind_param("ii", $deleteStoryID, $childID);
        $stmt->execute();
        $stmt->close();

        // Redirect to refresh the page after deletion
        header("Location: animation_video.php");
        exit();
    }
}

// Handle video generation
$videoFilePath = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['story_title']) && !isset($_POST['deleteStoryID'])) {
    $story_title = isset($_POST['story_title']) ? trim($_POST['story_title']) : '';
    $character_description = isset($_POST['character_description']) ? trim($_POST['character_description']) : '';
    $story_text = isset($_POST['story_text']) ? trim($_POST['story_text']) : '';
    $language = isset($_POST['language']) ? trim($_POST['language']) : 'en';
    $voice = isset($_POST['voice']) ? trim($_POST['voice']) : 'male';
    $childID = isset($_POST['childID']) ? intval($_POST['childID']) : 0;

    // Log the received POST data for debugging
    $post_data_log = "POST Data Received:\n";
    $post_data_log .= "story_title: $story_title\n";
    $post_data_log .= "character_description: $character_description\n";
    $post_data_log .= "story_text: $story_text\n";
    $post_data_log .= "language: $language\n";
    $post_data_log .= "voice: $voice\n";
    $post_data_log .= "childID: $childID\n";
    file_put_contents('C:/xampp/htdocs/DreamScribeAi/generate.log', $post_data_log . "\n", FILE_APPEND);

    if (empty($story_title) || empty($character_description) || empty($story_text) || empty($childID)) {
        $_SESSION['errorMessage'] = "Missing required fields for video generation.";
        header("Location: write_story.php");
        exit();
    }

    // Write arguments to a temporary JSON file
    $temp_json_path = 'C:/xampp/htdocs/DreamScribeAi/temp_args.json';
    $args_data = [
        'story_title' => $story_title,
        'character_description' => $character_description,
        'story_text' => $story_text,
        'language' => $language,
        'narrator_voice' => $voice
    ];
    file_put_contents($temp_json_path, json_encode($args_data, JSON_UNESCAPED_UNICODE));

    // Log the JSON data
    $json_log = "JSON Data Written to Temp File:\n" . json_encode($args_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents('C:/xampp/htdocs/DreamScribeAi/generate.log', $json_log . "\n", FILE_APPEND);

    // Call app.py with the path to the JSON file
    $command = "python C:/xampp/htdocs/DreamScribeAi/app.py --args-file " . escapeshellarg($temp_json_path);
    exec("$command 2>&1", $output, $return_var);

    // Combine output lines into a single string, ensure all lines are captured
    $output_str = implode("\n", $output);

    // Log the command, full output, and return code for debugging
    file_put_contents('C:/xampp/htdocs/DreamScribeAi/generate.log', "Command: $command\nOutput: $output_str\nReturn: $return_var\n\n", FILE_APPEND);

    // Check for specific error messages in the output
    $error_detected = false;
    $error_message = "Unknown error occurred.";
    foreach ($output as $line) {
        if (strpos($line, '{"status": "error"') !== false) {
            $error_detected = true;
            $error_message = $line;
            break;
        }
    }

    if ($return_var !== 0 || $error_detected) {
        $_SESSION['errorMessage'] = "Failed to generate video. Error: $error_message. Check generate.log for details.";
        header("Location: write_story.php");
        exit();
    }

    // The last three lines of output should be audio path, video path, and thumbnail path
    $lines = array_filter(array_map('trim', $output));
    $last_three_lines = array_slice($lines, -3);

    // Extract paths
    $audioFilePath = $last_three_lines[0] ?? '';
    $videoFilePath = $last_three_lines[1] ?? '';
    $thumbnailPath = $last_three_lines[2] ?? '';

    if (empty($audioFilePath) || empty($videoFilePath)) {
        $_SESSION['errorMessage'] = "Failed to generate video. Paths not generated. Check generate.log for details.";
        header("Location: write_story.php");
        exit();
    }

    // Save to database
    $stmt = $conn->prepare("INSERT INTO story (childID, title, storyContent, characterDescription, audioFilePath, videoFilePath, thumbnailPath) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $childID, $story_title, $story_text, $character_description, $audioFilePath, $videoFilePath, $thumbnailPath);

    if ($stmt->execute()) {
        $_SESSION['showMessage'] = true;
        $_SESSION['motivationMessage'] = "Great job! Your animation has been generated successfully!";
        $_SESSION['storyLanguage'] = $language;
    } else {
        $_SESSION['errorMessage'] = "Failed to save story to database.";
        header("Location: write_story.php");
        exit();
    }

    $stmt->close();
}

// Fetch all stories for the child to display in the gallery
$stories = [];
$stmt = $conn->prepare("SELECT storyID, title, storyContent, videoFilePath, thumbnailPath FROM Story WHERE childID = ? ORDER BY storyID DESC");
$stmt->bind_param("i", $childID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stories[] = $row;
}
$stmt->close();

$conn->close();

// Retrieve the story, voice, and language from query parameters using PHP
$storyText = isset($_GET['story']) ? trim($_GET['story']) : '';
$voice = isset($_GET['voice']) ? trim($_GET['voice']) : '';
$language = isset($_GET['language']) ? trim($_GET['language']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream Scribe AI - Generate Video</title>
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

        /* Navbar */
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

        /* Header Section */
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
            font-size: 4em;
            color: #003a53;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-content p {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
            background: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 8px;
        }

        /* Main Section */
        .main-section {
            padding: 50px 40px;
            z-index: 2;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 1s ease-in-out;
        }

        .main-container h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2em;
            color: #003a53;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .main-container p {
            font-size: 1em;
            color: #333;
            margin-bottom: 15px;
        }

        .main-container strong {
            color: #003a53;
        }

        .video-player {
            width: 100%;
            max-width: 800px;
            margin: 0 auto 30px;
            border-radius: 10px;
            overflow: hidden;
        }

        .scene {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            text-align: left;
        }

        .scene h3 {
            font-family: 'Open Sans', sans-serif;
            font-size: 1.2em;
            color: #003a53;
            margin-bottom: 10px;
        }

        .scene p {
            font-size: 0.95em;
            color: #666;
        }

        /* Gallery Section */
        .gallery-section {
            margin-top: 40px;
        }

        .gallery-section h3 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 1.8em;
            color: #003a53;
            margin-bottom: 20px;
            text-align: center;
        }

        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .gallery-item {
            width: 200px;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .gallery-item:hover {
            transform: scale(1.05);
        }

        .gallery-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .gallery-item p {
            font-size: 0.9em;
            color: #333;
            margin-bottom: 10px;
        }

        .gallery-item button {
            background-color: rgb(205, 56, 2);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Open Sans', sans-serif;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
            margin: 0 5px;
        }

        .gallery-item button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.05);
        }

        /* Video Card */
        .video-card {
            display: none; /* Hidden by default */
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            transition: transform 0.2s ease-in-out;
        }

        .video-card.visible {
            display: block;
        }

        .video-card:hover {
            transform: scale(1.05);
        }

        .video-card video {
            width: 100%;
            height: 180px;
            border-radius: 6px;
        }

        /* Back Button */
        .back-button-container {
            text-align: center;
            margin-top: 30px;
        }

        .back-button {
            background-color: rgb(205, 56, 2);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Open Sans', sans-serif;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
        }

        .back-button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <!-- Navbar -->
    <div class="navbar">
        <h1>DreamScribeAi</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>YOUR ANIMATION</h1>
            <p>Watch the magical story of <?php echo htmlspecialchars($childName); ?> come to life!</p>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="main-container">
            <h2>Generated Story & Animation</h2>
            <p><strong>Story:</strong> <span id="story"><?php echo htmlspecialchars($storyText); ?></span></p>
            <p><strong>Narrator's Voice:</strong> <span id="voice"><?php echo htmlspecialchars($voice); ?></span></p>
            <p><strong>Language:</strong> <span id="language"><?php echo htmlspecialchars($language); ?></span></p>
            <?php if ($storyText): ?>
                <div id="scenes"></div>
            <?php endif; ?>
            <?php if ($videoFilePath): ?>
                <div class="video-player">
                    <video width="100%" controls>
                        <source src="<?php echo htmlspecialchars($videoFilePath); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php endif; ?>

            <!-- Gallery of Stories -->
            <div class="gallery-section">
                <h3>STORY GALLERY</h3>
                <div class="gallery">
                    <?php foreach ($stories as $story): ?>
                        <div class="gallery-item">
                            <?php if ($story['thumbnailPath']): ?>
                                <img src="<?php echo htmlspecialchars($story['thumbnailPath']); ?>" alt="Thumbnail">
                            <?php else: ?>
                                <img src="images/default_thumbnail.jpg" alt="Default Thumbnail">
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($story['title']); ?></p>
                            <p><?php echo htmlspecialchars(substr($story['storyContent'], 0, 50)) . '...'; ?></p>
                            <button onclick="showVideo(<?php echo $story['storyID']; ?>)">Review</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="deleteStoryID" value="<?php echo $story['storyID']; ?>">
                                <button type="submit">Delete</button>
                            </form>
                            <div class="video-card" id="videoCard-<?php echo $story['storyID']; ?>">
                                <video controls>
                                    <source src="<?php echo htmlspecialchars($story['videoFilePath']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="back-button-container">
                <button class="back-button" onclick="window.location.href='write_story.php'">Back to Write Story</button>
            </div>
        </div>
    </div>

    <script>
        // Function to show video card
        function showVideo(storyID) {
            // Hide all video cards
            document.querySelectorAll('.video-card').forEach(card => {
                card.classList.remove('visible');
            });

            // Show the clicked video card
            const videoCard = document.getElementById(`videoCard-${storyID}`);
            if (videoCard) {
                videoCard.classList.toggle('visible');
            }
        }

        // Split the story into scenes and generate mock animation descriptions
        const storyText = '<?php echo addslashes($storyText); ?>';
        const voice = '<?php echo addslashes($voice); ?>';
        const language = '<?php echo addslashes($language); ?>';

        if (storyText) {
            const scenes = storyText.split('. ').filter(scene => scene.trim() !== '');

            const scenesContainer = document.getElementById('scenes');
            scenes.forEach((scene, index) => {
                scene = scene.trim();
                if (scene.endsWith('.')) {
                    scene = scene.slice(0, -1);
                }

                const animationDescription = `Animation Video for Scene ${index + 1}: A tracking shot shows the main character in a whimsical animated style. The scene depicts "${scene}" with smooth movements and vibrant colors, narrated by a ${voice} voice in ${language}. The background reflects the setting described in the scene, with playful transitions to the next moment.`;

                const sceneElement = document.createElement('div');
                sceneElement.className = 'scene';
                sceneElement.innerHTML = `
                    <h3>Scene ${index + 1}</h3>
                    <p><strong>Text:</strong> ${scene}.</p>
                    <p><strong>Animation Description:</strong> ${animationDescription}</p>
                `;
                scenesContainer.appendChild(sceneElement);
            });
        }
    </script>
</body>
</html>