<?php
// No dynamic PHP logic needed for this page, but keeping it as .php for consistency
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - DreamScribeAi</title>
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
            background: url('images/6639797.jpg') no-repeat center center fixed;
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

        .login-btn {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
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

        .header-content p {
            font-size: 1.45em;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
            background: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 8px;
        }

        .header-content button {
            background-color: rgb(205, 56, 2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 1.45em;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .header-content button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }

        /* Teamwork Section */
        .teamwork-section {
            padding: 50px 40px;
            z-index: 2;
        }

        .teamwork-container {
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

        .teamwork-container h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2em;
            color: #003a53;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .teamwork-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .teamwork-item {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.8);
        }

        /* Center the 7th item */
        .teamwork-item:nth-child(7) {
            grid-column: 2 / 3; /* Center the 7th item in the third row */
        }

        .teamwork-item img {
            width: 100px; /* Increased size for better visibility */
            height: 100px;
            object-fit: cover; /* Ensure images fit well */
            border-radius: 50%; /* Optional: circular images */
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
            <a href="login.php" class="login-btn">Log In</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>ABOUT US</h1>
            <p>Dream Scribe AI aims to transform your imaginative stories into animated reality with our platform. We wish you an enjoyable and safe experience.</p>
            <button>Learn More</button>
        </div>
    </div>

    <!-- Teamwork Section -->
    <div class="teamwork-section">
        <div class="teamwork-container">
            <h2>OUR TEAMWORK</h2>
            <div class="teamwork-grid">
                <!-- First Row: 3 Characters -->
                <div class="teamwork-item">
                    <img src="images/4.jpg" alt="Character 1">
                    <h2>Khadijeh</h2>
                </div>
                <div class="teamwork-item">
                    <img src="images/2.jpg" alt="Character 2">
                    <h2>Amina</h2>
                </div>
                <div class="teamwork-item">
                    <img src="images/3.jpg" alt="Character 3">
                    <h2>Albandari</h2>
                </div>
                <!-- Second Row: 3 Characters -->
                <div class="teamwork-item">
                    <img src="images/1.jpg" alt="Character 4">
                    <h2>Farah</h2>
                </div>
                <div class="teamwork-item">
                    <img src="images/7.jpg" alt="Character 5">
                    <h2>Sara</h2>
                </div>
                <div class="teamwork-item">
                    <img src="images/6.jpg" alt="Character 6">
                    <h2>Amna</h2>
                </div>
                <!-- Third Row: 1 Character (Centered) -->
                <div class="teamwork-item">
                    <img src="images/5.jpg" alt="Character 7">
                    <h2>Aisha</h2>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Animations are already handled via CSS @keyframes fadeIn
        });
    </script>
</body>
</html>