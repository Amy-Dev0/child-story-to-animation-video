<?php
include 'db_connect.php';

// جلب جميع الفيديوهات من جدول library
$stmt = $conn->prepare("SELECT title, videoPath, description FROM library ORDER BY uploadDate DESC");
$stmt->execute();
$result = $stmt->get_result();
$adminVideos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$baseUrl = "http://localhost/DreamScribeAi/";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Interface</title>
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
            background: url('images/12.jpg') no-repeat center center fixed;
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
            max-width: 1000px;
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

        /* Books Grid */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .book-card {
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }

        .book-card:hover {
            transform: scale(1.05);
        }

        .book-card video,
        .book-card iframe {
            width: 100%;
            height: 180px;
            border-radius: 6px;
        }

        .book-card p {
            font-size: 14px;
            color: #333;
            margin: 5px 0;
        }

        .book-card .description {
            font-size: 12px;
            color: #666;
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
            margin-top: 20px;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
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
        <h1>DreamScribeAi</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>YOUR LIBRARY</h1>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="main-container">
            <h2>LIBRARY</h2>

            <!-- Books Grid -->
            <div class="grid-container">
                <!-- Admin Uploaded Videos -->
                <?php foreach ($adminVideos as $video): ?>
                    <div class="book-card">
                        <video controls>
                            <source src="<?php echo htmlspecialchars($baseUrl . $video['videoPath']); ?>" type="video/mp4">
                            Your browser does not support video playback.
                        </video>
                        <p><?php echo htmlspecialchars($video['title']); ?></p>
                        <p class="description"><?php echo htmlspecialchars($video['description']); ?></p>
                    </div>
                <?php endforeach; ?>

                <!-- Card 1: YouTube video 1 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/HvijxnEwVak" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 2: YouTube video 2 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/NGTjcY_V8sk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 3: YouTube video 3 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/MYCwYgrwuI8" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 4: YouTube video 4 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/9U13fj95-c4" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 5: YouTube video 5 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/piMIVJTIvtw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 6: YouTube video 6 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/Q2Q_WSz4Ca0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 7: YouTube video 7 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/fpckcR0pOQU" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 8: YouTube video 8 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/b8jN8DxLBbM" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <!-- Card 9: YouTube video 9 -->
                <div class="book-card">
                    <iframe src="https://www.youtube.com/embed/mbWbRl4ZYC0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>

            <!-- Back Button -->
            <button onclick="window.location.href='more.html'" class="button">Back</button>
        </div>
    </div>
</body>
</html>