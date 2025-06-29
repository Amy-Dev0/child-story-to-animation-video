<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DreamScribeAi - A Magical Storytelling Adventure</title>
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
            justify-content: space-between;
            position: relative;
        }

        /* Background video styling */
        .background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures the video covers the entire area */
            z-index: -1; /* Places the video behind all other content */
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

        .navbar {
            background: rgba(255, 255, 255, 0.8); /* Original semi-transparent white */
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
        }

        .navbar h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2.5em;
            color: #003a53; /* Original deep teal */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .nav-links {
            display: flex;
            align-items: center;
        }

        .navbar a {
            font-family: 'Open Sans', sans-serif;
            color: #003a53; /* Original deep teal */
            text-decoration: none;
            margin: 0 15px;
            font-size: 1.1em;
            position: relative; /* For the underline effect */
            transition: color 0.3s;
        }

        /* Hover effect with red underline */
        .navbar a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background-color: #ff0000; /* Red underline as in the image */
            bottom: -5px;
            left: 0;
            transition: width 0.3s ease-in-out;
        }

        .navbar a:hover::after {
            width: 100%;
        }

        .navbar a:hover {
            color: #ffcc00; /* Original yellow on hover */
        }

        .main-content {
            text-align: center;
            padding: 50px 20px;
            z-index: 2;
        }

        .main-content h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 4.75em;
            color: #003a53; /* Original deep teal */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .main-content p {
            font-size: 1.4em;
            color: #333; /* Original dark gray */
            margin-bottom: 30px;
        }

        .get-started-btn {
            display: inline-block;
            padding: 15px 30px;
            background-color:rgb(205, 56, 2);
            color: white;
            text-decoration: none;
            font-size: 1.2em;
            border-radius: 25px;
            transition: background-color 0.3s, transform 0.3s;
        }

        .get-started-btn:hover {
            background-color: #ffcc00; /* Original yellow on hover */
            color: #003a53;
            transform: scale(1.05);
            animation: bounce 0.5s;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: scale(1);
            }
            40% {
                transform: scale(1.1);
            }
            60% {
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay muted loop>
        <source src="videos/background-video.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="storybook-border"></div>

    <div class="navbar">
        <h1>DreamScribeAi</h1>
        <div class="nav-links">
            <a href="about.php">About Us</a>
            <a href="signup.php">Sign Up</a>
            <a href="login.php">Log In</a>
            <a href="contact.php">Contact Us</a>
        </div>
    </div>

    <div class="main-content">
        <h2>Welcome to DreamScribeAi!</h2>
        <p>A magical place to share and explore video stories!</p>
        <a href="login.php" class="get-started-btn">Get Started</a>
    </div>
</body>
</html>