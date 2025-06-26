<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['guardianID'])) {
        echo "<script>alert('Error: Guardian not logged in.'); window.location.href='login.php';</script>";
        exit();
    }

    $child_name = trim($_POST['room-name']);
    $child_age = intval($_POST['room-age']);
    $child_gender = trim($_POST['room-gender']);
    $guardianID = $_SESSION['guardianID'];

    // Validate child name: max 15 characters, only Arabic or English letters
    if (strlen($child_name) > 15) {
        echo "<script>alert('Error: Name must not exceed 15 characters.'); window.location.href='create-room.php';</script>";
        exit();
    }
    if (!preg_match("/^[\p{L}\s]+$/u", $child_name)) {
        echo "<script>alert('Error: Name must contain only Arabic or English letters.'); window.location.href='create-room.php';</script>";
        exit();
    }

    // Validate age: between 7 and 12
    if ($child_age < 7 || $child_age > 12) {
        echo "<script>alert('Error: Age must be between 7 and 12.'); window.location.href='create-room.php';</script>";
        exit();
    }

    // Insert child data
    $sql = "INSERT INTO child (name, age, gender, guardianID) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $child_name, $child_age, $child_gender, $guardianID);

    if ($stmt->execute()) {
        echo "<script>alert('Child registered successfully!'); window.location.href='list.php';</script>";
    } else {
        echo "<script>alert('Error: Could not register child.'); window.location.href='create-room.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Room - DremScribeAI</title>
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

        /* Form Section */
        .form-section {
            padding: 50px 40px;
            z-index: 2;
        }

        .form-container {
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

        .form-container h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2em;
            color: #003a53;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        form {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: bold;
            color: #003a53;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-family: 'Open Sans', sans-serif;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: url('data:image/svg+xml;utf8,<svg fill="%23333" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center;
            background-size: 16px;
        }

        .error-message {
            color: red;
            font-size: 14px;
            display: none;
            margin-top: 5px;
        }

        .create-room-btn,
        .already-have-room {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            font-family: 'Open Sans', sans-serif;
            width: 100%;
            margin-top: 10px;
        }

        .already-have-room {
            background-color: rgb(205, 56, 2);
        }

        .create-room-btn:hover,
        .already-have-room:hover {
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
        <h1>DremScribeAI</h1>
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
            <h1>ADD YOUR CHILD</h1>
            <p>Set up a magical space for your child to explore creative stories!</p>
        </div>
    </div>

    <!-- Form Section -->
    <div class="form-section">
        <div class="form-container">
            <h2>ROOM DETAILS</h2>
            <form id="create-room-form" method="POST" action="create-room.php" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="room-name">Name</label>
                    <input type="text" id="room-name" name="room-name" placeholder="Enter Your Child's Name" required oninput="checkName()">
                    <p class="error-message" id="name-length-error">Error: Name must not exceed 15 characters.</p>
                    <p class="error-message" id="name-char-error">Error: Name must contain only Arabic or English letters.</p>
                </div>
                <div class="form-group">
                    <label for="room-age">Age</label>
                    <input type="number" id="room-age" name="room-age" placeholder="Enter Your Child's Age" min="7" max="12" required oninput="checkAge()">
                    <p class="error-message" id="age-error">Error: Age must be between 7 and 12.</p>
                </div>
                <div class="form-group">
                    <label for="room-gender">Gender</label>
                    <select id="room-gender" name="room-gender" required>
                        <option value="girl">Girl</option>
                        <option value="boy">Boy</option>
                    </select>
                </div>
                <button type="submit" class="create-room-btn">CREATE ROOM</button>
                <button type="button" class="already-have-room" onclick="window.location.href='room.php'">I already have a room</button>
            </form>
        </div>
    </div>

    <script>
        function checkName() {
            const nameInput = document.getElementById('room-name');
            const nameLengthError = document.getElementById('name-length-error');
            const nameCharError = document.getElementById('name-char-error');
            const name = nameInput.value.trim();

            nameLengthError.style.display = "none";
            nameCharError.style.display = "none";

            if (name.length > 15) {
                nameLengthError.style.display = "block";
            }
            if (!/^[\p{L}\s]+$/u.test(name) && name !== "") {
                nameCharError.style.display = "block";
            }
        }

        function checkAge() {
            const ageInput = document.getElementById('room-age');
            const errorText = document.getElementById('age-error');
            if (ageInput.value < 7 || ageInput.value > 12) {
                errorText.style.display = "block";
            } else {
                errorText.style.display = "none";
            }
        }

        function validateForm() {
            const nameInput = document.getElementById('room-name');
            const ageInput = document.getElementById('room-age');
            const name = nameInput.value.trim();

            if (name.length > 15) {
                alert("Error: Name must not exceed 15 characters.");
                return false;
            }
            if (!/^[\p{L}\s]+$/u.test(name)) {
                alert("Error: Name must contain only Arabic or English letters.");
                return false;
            }
            if (ageInput.value < 7 || ageInput.value > 12) {
                alert("Error: Age must be between 7 and 12.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>