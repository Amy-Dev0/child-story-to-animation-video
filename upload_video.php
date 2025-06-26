<?php
session_start();
include 'db_connect.php';

// Enable error reporting for debugging (can be disabled in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and is an admin
if (!isset($_SESSION['adminID']) || $_SESSION['isAdmin'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle video upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $uploadedBy = $_SESSION['adminID'];
    $uploadDate = date('Y-m-d H:i:s');

    // Directory to store uploaded videos
    $uploadDir = "uploads/videos/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Check if a file was uploaded
    if (isset($_FILES['video']) && $_FILES['video']['error'] == UPLOAD_ERR_OK) {
        $videoFile = $_FILES['video'];
        $videoName = basename($videoFile['name']);
        $videoPath = $uploadDir . time() . "_" . $videoName;

        // Validate file type (only allow MP4 for now)
        $allowedTypes = ['video/mp4'];
        $fileType = mime_content_type($videoFile['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Only MP4 videos are allowed.";
        } elseif ($videoFile['size'] > 50 * 1024 * 1024) { // Limit to 50MB
            $error = "Video file is too large. Maximum size is 50MB.";
        } else {
            // Move the uploaded file to the uploads directory
            if (move_uploaded_file($videoFile['tmp_name'], $videoPath)) {
                // Insert video details into the database
                $stmt = $conn->prepare("INSERT INTO library (videoPath, title, description, uploadedBy, uploadDate) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sssds", $videoPath, $title, $description, $uploadedBy, $uploadDate);
                if ($stmt->execute()) {
                    $success = "Video uploaded successfully!";
                } else {
                    $error = "Failed to save video details to the database.";
                }
                $stmt->close();
            } else {
                $error = "Failed to upload the video file.";
            }
        }
    } else {
        $error = "Please select a video file to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - DremScribeAI</title>
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
            background: url('images/lucaLogin.jpeg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Parchment overlay */
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

        /* Parchment border */
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
            font-size: 3em;
            color: #003a53;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
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
            max-width: 600px;
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

        .main-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #003a53;
        }

        .main-container input,
        .main-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .main-container button {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 12px 20px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
        }

        .main-container button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.05);
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <!-- Navbar -->
    <div class="navbar">
        <h1>DremScribeAI</h1>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="about.html">About Us</a>
            <a href="library.php">Library</a>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>UPLOAD A NEW VIDEO</h1>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="main-container">
            <h2>UPLOAD NEW VIDEO</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST" action="upload_video.php" enctype="multipart/form-data">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4"></textarea>

                <label for="video">Select Video (MP4, max 50MB):</label>
                <input type="file" id="video" name="video" accept="video/mp4" required>

                <button type="submit">Upload</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>