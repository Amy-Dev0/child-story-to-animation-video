<?php
session_start();
include 'db_connect.php';

// Check if guardianID is set in the session
if (!isset($_SESSION['guardianID'])) {
    die("Error: Guardian not logged in. Session lost.");
}

$guardianID = $_SESSION['guardianID'];

// Fetch the latest child for this guardian
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
$prohibitedWordsRaw = explode(',', $child['prohibitedWords']);
$prohibitedWords = array_filter(array_map('trim', $prohibitedWordsRaw), function($word) {
    return !empty($word);
});
$storyLanguage = isset($_SESSION['storyLanguage']) ? $_SESSION['storyLanguage'] : $child['storyLanguage'];
$showMessage = isset($_SESSION['showMessage']) ? $_SESSION['showMessage'] : false;
$motivationMessage = isset($_SESSION['motivationMessage']) ? $_SESSION['motivationMessage'] : "";
$errorMessage = isset($_SESSION['errorMessage']) ? $_SESSION['errorMessage'] : "";

// Reset session variables after displaying the message
if ($showMessage) {
    unset($_SESSION['showMessage']);
    unset($_SESSION['motivationMessage']);
    unset($_SESSION['storyLanguage']);
}
if ($errorMessage) {
    unset($_SESSION['errorMessage']);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Story - DremScribeAI</title>
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
            background: url('images/9952276.jpg') no-repeat center center fixed;
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

        /* Story Section */
        .story-section {
            padding: 50px 40px;
            z-index: 2;
        }

        .story-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 1s ease-in-out;
        }

        .story-container h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2em;
            color: #003a53;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
        }

        .left-section {
            width: 40%;
            text-align: left;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .option {
            text-align: left;
        }

        .option img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid #003a53;
        }

        .option label {
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: bold;
            color: #003a53;
            margin-bottom: 5px;
            display: block;
        }

        .option select,
        .option input {
            font-size: 14px;
            color: #333;
            display: block;
            margin-top: 5px;
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: 'Open Sans', sans-serif;
        }

        .option .title-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .notebook {
            width: 55%;
            height: 400px;
            background: url('images/notebook.png') no-repeat center center;
            background-size: cover;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .notebook textarea {
            width: 100%;
            height: 100%;
            border: none;
            background: transparent;
            font-size: 18px;
            line-height: 1.8;
            padding: 15px;
            resize: none;
            outline: none;
            font-family: 'Open Sans', sans-serif;
        }

        .word-counter {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 14px;
            color: #003a53;
            background: rgba(255, 255, 255, 0.7);
            padding: 5px 10px;
            border-radius: 5px;
        }

        .generate-btn,
        .back-button,
        .ai-title-btn {
            background-color: rgb(205, 56, 2);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-family: 'Open Sans', sans-serif;
            transition: background-color 0.3s, transform 0.3s;
        }

        .ai-title-btn {
            padding: 8px 15px;
            font-size: 14px;
        }

        .generate-btn:hover,
        .back-button:hover,
        .ai-title-btn:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }

        .generate-btn:disabled,
        .ai-title-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .motivation {
            text-align: center;
            font-size: 24px;
            color: #003a53;
            margin-top: 20px;
            animation: fadeIn 2s ease-in-out;
        }

        .error-message {
            text-align: center;
            font-size: 20px;
            color: #ff0000;
            margin-top: 20px;
            animation: fadeIn 2s ease-in-out;
        }

        .error-text {
            color: red;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .back-button-container {
            text-align: center;
            margin-top: 20px;
        }

        .star {
            position: absolute;
            width: 20px;
            height: 20px;
            background: url('images/star.png') no-repeat center center;
            background-size: contain;
            animation: floatUp 1s ease-in-out forwards;
        }

        @keyframes floatUp {
            0% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-100px); }
        }

        .loading-message {
            display: none;
            color: #003a53;
            font-size: 14px;
            margin-top: 5px;
            font-style: italic;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            animation: bounce 1s infinite;
        }

        .loading-content p {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 1.5em;
            color: #003a53;
            margin-bottom: 10px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 5px solid #ffcc00;
            border-top: 5px solid #003a53;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <!-- Navbar -->
    <div class="navbar">
        <h1>DremScribeAI</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>WRITE YOUR STORY</h1>
            <p>Unleash your creativity and craft a magical tale for <?php echo htmlspecialchars($childName); ?>!</p>
        </div>
    </div>

    <!-- Story Section -->
    <div class="story-section">
        <div class="story-container">
            <h2>STORY CREATION</h2>
            <div class="content">
                <div class="left-section">
                    <form id="story_form" method="POST" action="animation_video.php" accept-charset="UTF-8" onsubmit="return validateForm()">
                        <div class="options">
                            <div class="option">
                                <label for="story_title">Story Title:</label>
                                <div class="title-container">
                                    <input type="text" id="story_title" name="story_title" required>
                                    <button type="button" id="ai-title-btn" class="ai-title-btn">AI Title</button>
                                </div>
                                <div id="loading-message" class="loading-message">Generating story...</div>
                                <p id="title-error" class="error-text"></p>
                            </div>
                            <div class="option">
                                <label for="character_description">Character Description:</label>
                                <input type="text" id="character_description" name="character_description" required>
                                <p id="description-error" class="error-text"></p>
                            </div>
                            <div class="option">
                                <img src="images/language.jpg" alt="Language Icon">
                                <label for="language">Choose Language:</label>
                                <select id="language" name="language" required>
                                    <option value="en" <?php echo $storyLanguage === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="ar" <?php echo $storyLanguage === 'ar' ? 'selected' : ''; ?>>Arabic</option>
                                </select>
                            </div>
                            <div class="option">
                                <img src="images/narrator.jpg" alt="Narrator Icon">
                                <label for="voice">Choose Narrator’s Voice:</label>
                                <select id="voice" name="voice" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="option">
                                <img src="images/r.jpg" alt="AI Generation Icon">
                                <button type="submit" class="generate-btn">Generate Animation</button>
                            </div>
                        </div>
                        <input type="hidden" name="childID" value="<?php echo htmlspecialchars($childID); ?>">
                        <input type="hidden" name="story_text" id="story_text_hidden">
                    </form>
                </div>
                <div class="notebook">
                    <textarea id="story" name="story" placeholder="Start writing here..." required></textarea>
                    <div class="word-counter" id="word-counter">500 characters left</div>
                </div>
            </div>

            <?php if ($showMessage): ?>
                <div class="motivation">❤️ <?= htmlspecialchars($motivationMessage) ?> ❤️</div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <div class="back-button-container">
                <button class="back-button" onclick="window.location.href='story_options.php'">Back</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-content">
            <p>Generating your magical story! ✨ Please wait...</p>
            <div class="spinner"></div>
        </div>
    </div>

    <audio id="femaleVoice" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" preload="auto"></audio>
    <audio id="clapSound" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3" preload="auto"></audio>
    <audio id="magicSound" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3" preload="auto"></audio>

    <script>
        const prohibitedWords = <?php echo json_encode($prohibitedWords); ?>;
        const maxStoryLength = 500;

        // Real-time validation for story title
        document.getElementById('story_title').addEventListener('input', function() {
            const title = this.value;
            const titleError = document.getElementById('title-error');
            const titleRegex = /^[a-zA-Z\sء-ي]+$/;

            if (!titleRegex.test(title)) {
                titleError.textContent = "Title must contain only Arabic or English letters.";
                titleError.style.display = 'block';
            } else if (title.length > 30) {
                titleError.textContent = "Title cannot exceed 30 characters.";
                titleError.style.display = 'block';
            } else {
                titleError.style.display = 'none';
            }
        });

        // Real-time validation for character description
        document.getElementById('character_description').addEventListener('input', function() {
            const description = this.value;
            const descriptionError = document.getElementById('description-error');

            if (description.length > 100) {
                descriptionError.textContent = "Character description cannot exceed 100 characters.";
                descriptionError.style.display = 'block';
            } else {
                descriptionError.style.display = 'none';
            }
        });

        // Character counter and prohibited words check for story textarea
        const storyTextarea = document.getElementById('story');
        storyTextarea.addEventListener('input', function() {
            let text = this.value;
            const counter = document.getElementById('word-counter');
            const remaining = maxStoryLength - text.length;

            counter.textContent = remaining + " characters left";

            if (remaining < 0) {
                this.value = text.substring(0, maxStoryLength);
                counter.textContent = "0 characters left";
                return;
            }

            prohibitedWords.forEach(word => {
                if (word) {
                    const regex = new RegExp(`\\b${word}\\b`, "gi");
                    if (regex.test(text)) {
                        text = text.replace(regex, '***');
                        speakWarning();
                        showStars();
                    }
                }
            });
            this.value = text;
        });

        // Show loading overlay on form submit
        document.getElementById('story_form').addEventListener('submit', function(event) {
            if (!validateForm()) {
                event.preventDefault();
                return;
            }
            document.getElementById('loading-overlay').style.display = 'flex';
            document.getElementById('story_text_hidden').value = storyTextarea.value;
        });

        function validateForm() {
            const storyTitle = document.getElementById("story_title").value;
            const characterDescription = document.getElementById("character_description").value;
            const storyText = document.getElementById("story").value;
            const language = document.getElementById("language").value;

            console.log("Story Title:", storyTitle);
            console.log("Character Description:", characterDescription);
            console.log("Story Text:", storyText);
            console.log("Language:", language);

            if (language === "ar") {
                const arabicRegex = /[\u0600-\u06FF]/;
                if (!arabicRegex.test(storyText) && !arabicRegex.test(storyTitle) && !arabicRegex.test(characterDescription)) {
                    alert("Please ensure the story, title, or character description contains Arabic text when selecting Arabic language.");
                    return false;
                }
            }

            return true;
        }

        function speakWarning() {
            const audio = document.getElementById("femaleVoice");
            audio.play();
        }

        function showStars() {
            const count = 20;
            for (let i = 0; i < count; i++) {
                const star = document.createElement("div");
                star.classList.add("star");
                star.style.left = Math.random() * window.innerWidth + "px";
                star.style.top = Math.random() * window.innerHeight + "px";
                document.body.appendChild(star);
                setTimeout(() => document.body.removeChild(star), 1000);
            }
        }

        // Handle "AI Title" button click
        document.getElementById("ai-title-btn").addEventListener("click", function() {
            const title = document.getElementById("story_title").value.trim();
            const language = document.getElementById("language").value;
            const voice = document.getElementById("voice").value;
            const loadingMessage = document.getElementById("loading-message");
            const titleError = document.getElementById("title-error");
            const characterDescriptionInput = document.getElementById("character_description");
            const aiTitleBtn = this;
            const generateBtn = document.querySelector(".generate-btn");
            const storyTextarea = document.getElementById("story");

            titleError.textContent = "";
            titleError.style.display = "none";
            loadingMessage.style.display = "block";
            aiTitleBtn.disabled = true;
            generateBtn.disabled = true;

            if (!title) {
                loadingMessage.style.display = "none";
                titleError.textContent = "Please enter a story title.";
                titleError.style.display = "block";
                aiTitleBtn.disabled = false;
                generateBtn.disabled = false;
                return;
            }

            const formData = new FormData();
            formData.append('title', title);
            formData.append('language', language);
            formData.append('voice', voice);

            fetch("generate_title.php", {
                method: "POST",
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success") {
                    storyTextarea.value = data.story || "";
                    characterDescriptionInput.value = data.description || "No description available.";
                    const inputEvent = new Event('input', { bubbles: true });
                    storyTextarea.dispatchEvent(inputEvent);
                    characterDescriptionInput.dispatchEvent(inputEvent);
                } else {
                    titleError.textContent = data.message || "Failed to generate story.";
                    titleError.style.display = "block";
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                titleError.textContent = "An error occurred while generating the story. Please try again.";
                titleError.style.display = "block";
            })
            .finally(() => {
                loadingMessage.style.display = "none";
                aiTitleBtn.disabled = false;
                generateBtn.disabled = false;
            });
        });

        window.onload = function() {
            <?php if ($showMessage): ?>
                const msg = "<?= htmlspecialchars($motivationMessage) ?>";
                const lang = "<?= htmlspecialchars($storyLanguage) ?>";
                const utterance = new SpeechSynthesisUtterance(msg);
                utterance.lang = lang === "ar" ? "ar-SA" : "en-US";
                utterance.rate = 1;
                utterance.pitch = 1.2;
                speechSynthesis.speak(utterance);
                document.getElementById("clapSound").play();
                showStars();
            <?php endif; ?>
        };
    </script>
</body>
</html>