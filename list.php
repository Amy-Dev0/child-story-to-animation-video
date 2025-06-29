<?php
session_start();
session_regenerate_id(true);
include 'db_connect.php';

if (!isset($_SESSION['guardianID'])) {
    echo "<script>alert('Error: Guardian not logged in. Session is empty.'); window.location.href='login.php';</script>";
    exit();
}

$guardianID = $_SESSION['guardianID'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $language = isset($_POST['language']) ? $_POST['language'] : null;
    $prohibitedWords = isset($_POST['prohibitedWords']) ? $_POST['prohibitedWords'] : "";

    // Map the form language to the database format ('en' or 'ar')
    $languageMap = [
        'English' => 'en',
        'Arabic' => 'ar'
    ];
    $dbLanguage = isset($languageMap[$language]) ? $languageMap[$language] : 'en';

    // Update the child's storyLanguage and prohibitedWords
    $stmt = $conn->prepare("UPDATE child SET storyLanguage = ?, prohibitedWords = ? WHERE guardianID = ? ORDER BY childID DESC LIMIT 1");
    $stmt->bind_param("ssi", $dbLanguage, $prohibitedWords, $guardianID);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    header("Location: room.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control - Dream Scribe AI</title>
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
            background: url('images/6639790.jpg') no-repeat center center fixed;
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

        .main-container select,
        .main-container input,
        .main-container textarea {
            width: 90%;
            padding: 10px;
            margin: 10px auto;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            display: block;
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
            margin: 10px;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
        }

        .main-container button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.05);
        }

        .error-message {
            color: red;
            font-size: 14px;
            display: none;
            margin-top: 5px;
            text-align: center;
        }
    </style>
    <script>
        let prohibitedWords = [];

        function isArabic(text) {
            return /[\u0600-\u06FF]/.test(text);
        }

        function isEnglish(text) {
            return /^[A-Za-z]+$/.test(text);
        }

        function addWord() {
            const language = document.querySelector("select[name='language']").value;
            const wordInput = document.getElementById("badWord");
            const word = wordInput.value.trim();
            const wordList = document.getElementById("wordList");
            const errorMessage = document.getElementById("word-error");

            errorMessage.style.display = "none";
            errorMessage.innerText = "";

            if (!language) {
                errorMessage.innerText = "Please select a language first.";
                errorMessage.style.display = "block";
                return;
            }

            if (!word) return;

            // Check minimum length (at least 2 characters)
            if (word.length < 2) {
                errorMessage.innerText = "Error: A prohibited word must be at least 2 characters long.";
                errorMessage.style.display = "block";
                return;
            }

            // Check maximum length (20 characters)
            if (word.length > 20) {
                errorMessage.innerText = "Error: A prohibited word cannot exceed 20 characters.";
                errorMessage.style.display = "block";
                return;
            }

            // Check if the word contains only Arabic or English letters
            if (!/^[\p{L}\s]+$/u.test(word)) {
                errorMessage.innerText = "Error: A prohibited word must contain only Arabic or English letters.";
                errorMessage.style.display = "block";
                return;
            }

            let valid = false;
            if (language === "Arabic" && isArabic(word)) valid = true;
            if (language === "English" && isEnglish(word)) valid = true;

            if (!valid) {
                errorMessage.innerText = "The word does not match the selected language.";
                errorMessage.style.display = "block";
                return;
            }

            console.log("Current number of words:", prohibitedWords.length);
            console.log("Word being added:", word);

            if (prohibitedWords.length >= 25) {
                errorMessage.innerText = "Error: You can only add up to 25 words.";
                errorMessage.style.display = "block";
                return;
            }

            if (!prohibitedWords.includes(word)) {
                prohibitedWords.push(word);
                wordList.value = prohibitedWords.join(", ");
                wordInput.value = "";
                console.log("Updated prohibitedWords:", prohibitedWords);
            }
        }

        function saveWordsAndSubmit() {
            document.getElementById("hiddenWords").value = prohibitedWords.join(", ");
            document.getElementById("mainForm").submit();
        }
    </script>
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
            <h1>CONTROL YOUR CHILD'S STORY</h1>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="main-container">
            <h2>CONTROL</h2>
            <form id="mainForm" method="post" action="">
                <select name="language" class="dropdown" required>
                    <option value="">Choose preferred language</option>
                    <option value="English">English</option>
                    <option value="Arabic">Arabic</option>
                </select>
                <input type="text" id="badWord" placeholder="Enter a prohibited word that you do not want your child to use.">
                <p class="error-message" id="word-error"></p>
                <input type="hidden" name="prohibitedWords" id="hiddenWords">
                <button type="button" onclick="addWord()">Add</button>
                <textarea id="wordList" rows="4" readonly placeholder="Prohibited words will appear here"></textarea>
                <button type="button" onclick="saveWordsAndSubmit()">Next</button>
            </form>
        </div>
    </div>
</body>
</html>