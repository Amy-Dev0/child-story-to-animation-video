<?php
session_start();
include 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please enter your email and password!"]);
        exit;
    }

    // Step 1: Check if the user is an admin
    $stmt = $conn->prepare("SELECT adminID, userName, email, password FROM admin WHERE email = ?");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['adminID'] = $admin['adminID'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['userName'] = $admin['userName'];
            $_SESSION['isAdmin'] = true;
            error_log("Admin Login Successful: " . print_r($_SESSION, true));
            echo json_encode(["status" => "success", "redirect" => "admin_dashboard.php"]);
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "The password is incorrect!!"]);
            exit;
        }
    }
    $stmt->close();

    // Step 2: Check if the user is a guardian
    $stmt = $conn->prepare("SELECT guardianID, firstName, email, password FROM Guardian WHERE email = ?");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['guardianID'] = $user['guardianID'];
            $_SESSION['firstName'] = $user['firstName'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['isAdmin'] = false;
            error_log("Guardian Login Successful: " . print_r($_SESSION, true));
            echo json_encode(["status" => "success", "redirect" => "dashboard.php"]);
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "The password is incorrect!!"]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "There is no user with this email address!"]);
        exit;
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
    <title>Login - DremScribeAI</title>
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

        .signup-btn {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .signup-btn:hover {
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

        .login-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: left;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-form h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2.5em;
            color: #003a53;
            text-align: center;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-form p.subtitle {
            font-size: 1em;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }

        .login-form label {
            font-size: 1em;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }

        .login-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .login-form input:focus {
            border-color: #003a53;
            outline: none;
        }

        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: rgb(205, 56, 2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .login-form button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }

        .error-message {
            color: #ff0000;
            font-size: 0.9em;
            margin-bottom: 10px;
            display: none;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.9em;
        }

        .form-options label {
            display: flex;
            align-items: center;
            margin: 0;
        }

        .form-options input[type="checkbox"] {
            margin-right: 5px;
            width: auto;
        }

        .form-options a {
            color: #003a53;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .form-options a:hover {
            color: #ffcc00;
        }

        .signup-link {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9em;
        }

        .signup-link a {
            color: #003a53;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .signup-link a:hover {
            color: #ffcc00;
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <div class="navbar">
        <h1>DremScribeAI</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="signup.php" class="signup-btn">Sign Up</a>
        </div>
    </div>

    <div class="container">
        <div class="login-form" id="login">
            <h1>LOG IN</h1>
            <p class="subtitle">Welcome back! Please login to your account.</p>
            <form id="loginForm" method="POST" action="login.php">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="E-mail" required>
                <p class="error-message" id="emailError">Please enter a valid email.</p>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <p class="error-message" id="passwordError">Password must be at least 5 characters.</p>

                <p class="error-message" id="error-message"></p>

                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="reset_password.php">Forgot your password?</a>
                </div>

                <button type="submit" id="login-button">LOG IN</button>

                <div class="signup-link">
                    Don't have an account? <a href="signup.php">SIGN UP</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            event.preventDefault();

            let email = document.getElementById("email").value.trim();
            let password = document.getElementById("password").value.trim();
            let emailError = document.getElementById("emailError");
            let passwordError = document.getElementById("passwordError");
            let errorMessage = document.getElementById("error-message");

            emailError.style.display = "none";
            passwordError.style.display = "none";
            errorMessage.style.display = "none";

            let isValid = true;

            if (!email.includes("@") || email.length < 5) {
                emailError.style.display = "block";
                isValid = false;
            }
            if (password.length < 5) {
                passwordError.style.display = "block";
                isValid = false;
            }

            if (isValid) {
                let formData = new FormData(this);
                fetch("login.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Login Response:", data);
                    if (data.status === "success") {
                        window.location.href = data.redirect;
                    } else {
                        errorMessage.innerText = data.message;
                        errorMessage.style.display = "block";
                    }
                })
                .catch(error => {
                    console.error("Fetch Error:", error);
                    errorMessage.innerText = "An error occurred, please try again.";
                    errorMessage.style.display = "block";
                });
            }
        });
    </script>
</body>
</html>