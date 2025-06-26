<?php
session_start();
include 'db_connect.php';

$email = '';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Server-side email validation (already exists in the database)
    $stmt = $conn->prepare("SELECT * FROM Guardian WHERE email = ?");
    if (!$stmt) {
        $message = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // If the user is found
        if ($result->num_rows === 1) {
            $_SESSION['reset_email'] = $email;
            header("Location: new_password.php");
            exit;
        } else {
            $message = "This email is not registered.";
        }

        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - DremScribeAI</title>
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
            background: url('images/9952258.jpg') no-repeat center center fixed;
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

        .container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 40px;
            z-index: 2;
        }

        .reset-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px; /* Slightly narrower since there's only one field */
            width: 100%;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reset-form h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2.2em;
            color: #003a53;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .reset-form p.subtitle {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .reset-form label {
            font-size: 0.9em;
            color: #333;
            margin-bottom: 4px;
            text-align: left;
        }

        .reset-form input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9em;
            transition: border-color 0.3s;
        }

        .reset-form input:focus {
            border-color: #003a53;
            outline: none;
        }

        .reset-form button {
            width: 100%;
            padding: 10px;
            background-color: rgb(205, 56, 2);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .reset-form button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }

        .error-message {
            color: #ff0000;
            font-size: 0.8em;
            margin-top: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <div class="navbar">
        <h1>DremScribeAI</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.html">About Us</a>
            <a href="login.php" class="login-btn">Log In</a>
        </div>
    </div>

    <div class="container">
        <div class="reset-form" id="reset">
            <h1>RESET PASSWORD</h1>
            <p class="subtitle">Enter your email to reset your password</p>

            <form id="reset-form" method="POST">
                <p class="error-message" id="error-message"><?php echo !empty($message) ? $message : ''; ?></p>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <p class="error-message" id="emailError">Please enter a valid email.</p>
                </div>

                <button type="submit" name="submit">CONTINUE</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            console.log("Page loaded!");

            const form = document.getElementById("reset-form");
            const emailInput = document.getElementById("email");
            const emailError = document.getElementById("emailError");
            const errorMessage = document.getElementById("error-message");

            form.addEventListener("submit", function(event) {
                // Client-side validation
                emailError.style.display = "none";
                errorMessage.style.display = "none";

                const email = emailInput.value.trim();
                let isValid = true;

                if (!email.includes("@") || email.length < 5) {
                    emailError.style.display = "block";
                    isValid = false;
                    event.preventDefault(); // Prevent form submission if validation fails
                }

                if (!isValid) return;

                // If client-side validation passes, the form will submit to PHP for server-side validation
                console.log("Submitting email:", email);
            });
        });
    </script>
</body>
</html>