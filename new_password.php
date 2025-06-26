<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: reset_password.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Server-side validation for password conditions
    if (strlen($newPassword) < 8 || !preg_match("/[A-Z]/", $newPassword) || !preg_match("/[a-z]/", $newPassword) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $newPassword)) {
        $message = "Password must be at least 8 characters long, with one uppercase letter, one lowercase letter, and one special character.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("UPDATE Guardian SET password = ? WHERE email = ?");
        if (!$stmt) {
            $message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $hashedPassword, $email);

            if ($stmt->execute()) {
                unset($_SESSION['reset_email']); // Remove email from session
                header("Location: login.php?reset=success");
                exit;
            } else {
                $message = "An error occurred while setting the new password.";
            }

            $stmt->close();
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - DremScribeAI</title>
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

        .new-password-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .new-password-form h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2.2em;
            color: #003a53;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .new-password-form p.subtitle {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .new-password-form label {
            font-size: 0.9em;
            color: #333;
            margin-bottom: 4px;
            text-align: left;
        }

        .new-password-form input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9em;
            transition: border-color 0.3s;
        }

        .new-password-form input:focus {
            border-color: #003a53;
            outline: none;
        }

        .password-conditions {
            margin-top: 8px;
            font-size: 0.8em;
            color: #003a53;
            text-align: left;
            width: 100%;
            margin-bottom: 8px;
        }

        .password-conditions li {
            list-style-type: none;
            margin-bottom: 4px;
        }

        .password-conditions .valid {
            color: #28a745;
        }

        .password-conditions .invalid {
            color: #dc3545;
        }

        .new-password-form button {
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

        .new-password-form button:hover {
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
        <div class="new-password-form" id="new-password">
            <h1>SET NEW PASSWORD</h1>
            <p class="subtitle">Enter your new password below</p>

            <form id="new-password-form" method="POST">
                <p class="error-message" id="error-message"><?php echo !empty($message) ? $message : ''; ?></p>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        <p class="error-message" id="passwordError">Password must meet all conditions.</p>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <p class="error-message" id="confirmPasswordError">Passwords do not match.</p>
                    </div>
                </div>

                <ul class="password-conditions" id="password-conditions">
                    <li id="condition-length">At least 8 characters</li>
                    <li id="condition-uppercase">At least one uppercase letter</li>
                    <li id="condition-lowercase">At least one lowercase letter</li>
                    <li id="condition-special">At least one special character</li>
                </ul>

                <button type="submit" name="submit">SAVE PASSWORD</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            console.log("Page loaded!");

            const form = document.getElementById("new-password-form");
            const passwordInput = document.getElementById("new_password");
            const confirmPasswordInput = document.getElementById("confirm_password");
            const passwordError = document.getElementById("passwordError");
            const confirmPasswordError = document.getElementById("confirmPasswordError");
            const errorMessage = document.getElementById("error-message");

            function validatePassword() {
                const password = passwordInput.value;
                const conditionLength = document.getElementById("condition-length");
                const conditionUppercase = document.getElementById("condition-uppercase");
                const conditionLowercase = document.getElementById("condition-lowercase");
                const conditionSpecial = document.getElementById("condition-special");

                // Update visual indicators
                const isLengthValid = password.length >= 8;
                const isUppercaseValid = /[A-Z]/.test(password);
                const isLowercaseValid = /[a-z]/.test(password);
                const isSpecialValid = /[!@#$%^&*(),.?":{}|<>]/.test(password);

                conditionLength.className = isLengthValid ? "valid" : "invalid";
                conditionUppercase.className = isUppercaseValid ? "valid" : "invalid";
                conditionLowercase.className = isLowercaseValid ? "valid" : "invalid";
                conditionSpecial.className = isSpecialValid ? "valid" : "invalid";

                // Return true only if all conditions are met
                return isLengthValid && isUppercaseValid && isLowercaseValid && isSpecialValid;
            }

            // Update password conditions on input
            passwordInput.addEventListener("input", function () {
                validatePassword();
                // Also validate confirm password if it's already filled
                if (confirmPasswordInput.value) {
                    confirmPasswordInput.dispatchEvent(new Event("input"));
                }
            });

            // Validate confirm password on input
            confirmPasswordInput.addEventListener("input", function () {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                confirmPasswordError.style.display = (password === confirmPassword && password !== "") ? "none" : "block";
            });

            form.addEventListener("submit", function(event) {
                // Reset all error messages
                errorMessage.style.display = "none";
                passwordError.style.display = "none";
                confirmPasswordError.style.display = "none";

                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                let isValid = true;

                // Validate password conditions
                if (!validatePassword()) {
                    passwordError.style.display = "block";
                    isValid = false;
                    event.preventDefault();
                }

                // Validate confirm password match
                if (password !== confirmPassword) {
                    confirmPasswordError.style.display = "block";
                    isValid = false;
                    event.preventDefault();
                }

                if (!isValid) {
                    console.log("Validation failed. Form submission prevented.");
                    return;
                }

                console.log("Submitting new password:", password);
                // Form will submit to PHP if validation passes
            });
        });
    </script>
</body>
</html>