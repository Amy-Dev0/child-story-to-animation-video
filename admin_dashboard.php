<?php
session_start();
include 'db_connect.php';

// Enable error reporting for debugging (can be disabled in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and is an admin
if (!isset($_SESSION['guardianID']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    header("Location: login.php");
    exit;
}

// Verify that the guardianID exists in the admin table
$guardianID = $_SESSION['guardianID'];
$stmt = $conn->prepare("SELECT adminID FROM admin");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
//$stmt->bind_param("i", $guardianID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: login.php");
    exit;
}
$admin = $result->fetch_assoc();
$adminID = $admin['adminID'];
$stmt->close();

// Fetch videos from the library table
$videosStmt = $conn->prepare("SELECT v.libraryID, v.videoPath, v.title, v.description, v.uploadDate, a.userName
                             FROM library v 
                             JOIN admin a ON v.uploadedBy = a.adminID");
if (!$videosStmt) {
    die("Videos query prepare failed: " . $conn->error);
}
$videosStmt->execute();
$videosResult = $videosStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DremScribeAI</title>
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
            background: url('images/Luca 2021.jpg') no-repeat center center fixed;
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
            max-width: 1200px;
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

        .upload-link {
            display: inline-block;
            margin: 20px 0;
            padding: 12px 20px;
            background-color: rgb(205, 56, 2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
        }

        .upload-link:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.05);
        }

        .video-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .video-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
        }

        .video-card video {
            width: 100%;
            max-height: 200px;
            border-radius: 5px;
        }

        .video-card h3 {
            font-family: 'Open Sans', sans-serif;
            font-size: 18px;
            color: #003a53;
            margin: 10px 0;
        }

        .video-card p {
            font-size: 14px;
            color: #666;
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
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>WELCOME ADMIN, <?php echo htmlspecialchars($_SESSION['userName'] ?? 'Admin'); ?>!</h1>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="main-container">
            <a href="upload_video.php" class="upload-link">Upload New Video</a>
            <h2>VIDEO LIBRARY</h2>
            <div class="video-list">
                <?php while ($video = $videosResult->fetch_assoc()): ?>
                    <div class="video-card">
                        <video controls>
                            <source src="<?php echo htmlspecialchars($video['videoPath']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <h3><?php echo htmlspecialchars($video['title'] ?: 'Untitled Video'); ?></h3>
                        <p><strong>Uploaded by:</strong> <?php echo htmlspecialchars($video['userName']); ?></p>
                        <p><strong>Upload Date:</strong> <?php echo htmlspecialchars($video['uploadDate']); ?></p>
                        <p><?php echo htmlspecialchars($video['description'] ?: 'No description available.'); ?></p>
                    </div>
                <?php endwhile; ?>
                <?php if ($videosResult->num_rows == 0): ?>
                    <p>No videos found in the library.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$videosStmt->close();
$conn->close();
?>