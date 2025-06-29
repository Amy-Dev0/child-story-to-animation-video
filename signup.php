<?php
ob_start();
session_start();
include 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    if (isset($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validate firstName: only English letters, max 15 characters
        if (strlen($firstName) > 15) {
            echo json_encode(["status" => "error", "message" => "First name must not exceed 15 characters."]);
            exit;
        }
        if (!preg_match("/^[a-zA-Z\s]+$/", $firstName)) {
            echo json_encode(["status" => "error", "message" => "First name must contain only English letters (A-Z, a-z)."]);
            exit;
        }

        // Validate lastName: only English letters, max 15 characters
        if (strlen($lastName) > 15) {
            echo json_encode(["status" => "error", "message" => "Last name must not exceed 15 characters."]);
            exit;
        }
        if (!preg_match("/^[a-zA-Z\s]+$/", $lastName)) {
            echo json_encode(["status" => "error", "message" => "Last name must contain only English letters (A-Z, a-z)."]);
            exit;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(["status" => "error", "message" => "Passwords do not match!"]);
            exit;
        }

        // Check for email and password presence
        if (empty($email) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "Please enter both email and password."]);
            exit;
        }

        // Validate email format and constraints
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid email."]);
            exit;
        }
        if (strlen($email) > 50) {
            echo json_encode(["status" => "error", "message" => "Email must not exceed 50 characters."]);
            exit;
        }
        $allowedDomains = ['@gmail.com', '@hotmail.com', '@mail.com'];
        $isValidDomain = false;
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($email, $domain)) {
                $isValidDomain = true;
                break;
            }
        }
        if (!$isValidDomain) {
            echo json_encode(["status" => "error", "message" => "Email must be from @gmail.com, @hotmail.com, or @mail.com."]);
            exit;
        }

        // Validate password conditions
        if (strlen($password) < 8) {
            echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long."]);
            exit;
        }
        if (strlen($password) > 30) {
            echo json_encode(["status" => "error", "message" => "Password must not exceed 30 characters."]);
            exit;
        }
        if (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
            echo json_encode(["status" => "error", "message" => "Password must contain at least one uppercase letter, one lowercase letter, and one special character."]);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists in Guardian table
        $checkEmailStmt = $conn->prepare("SELECT * FROM Guardian WHERE email = ?");
        if (!$checkEmailStmt) {
            echo json_encode(["status" => "error", "message" => "Failed to prepare Guardian email check: " . $conn->error]);
            exit;
        }
        $checkEmailStmt->bind_param("s", $email);
        if (!$checkEmailStmt->execute()) {
            echo json_encode(["status" => "error", "message" => "Failed to execute Guardian email check: " . $checkEmailStmt->error]);
            exit;
        }
        $result = $checkEmailStmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Email already exists!"]);
            exit;
        }
        $checkEmailStmt->close();

        // Check if email already exists in admin table
        $checkAdminStmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        if (!$checkAdminStmt) {
            echo json_encode(["status" => "error", "message" => "Failed to prepare admin email check: " . $conn->error]);
            exit;
        }
        $checkAdminStmt->bind_param("s", $email);
        if (!$checkAdminStmt->execute()) {
            echo json_encode(["status" => "error", "message" => "Failed to execute admin email check: " . $checkAdminStmt->error]);
            exit;
        }
        $adminResult = $checkAdminStmt->get_result();

        if ($adminResult->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Email already exists in admin table!"]);
            exit;
        }
        $checkAdminStmt->close();

        // Insert into Guardian table
        $stmt = $conn->prepare("INSERT INTO Guardian (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Failed to prepare Guardian insert: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashed_password);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Account created successfully! Please log in."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error registering user: " . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
        exit;
    } else {
        echo json_encode(["status" => "error", "message" => "All fields are required!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - DreamScribeAi</title>
    <link href="https://fonts.googleapis.com/css2?family=Annie+Use+Your+Telescope&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            background: url('images/9952258.jpg') no-repeat center center fixed; /* Using HTML's background */
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

        .signup-form {
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

        .signup-form h1 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2.2em;
            color: #003a53;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .signup-form p.subtitle {
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
            position: relative;
        }

        .signup-form label {
            font-size: 0.9em;
            color: #333;
            margin-bottom: 4px;
            text-align: left;
        }

        .signup-form input,
        .signup-form select {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9em;
            transition: border-color 0.3s;
        }

        .signup-form input:focus,
        .signup-form select:focus {
            border-color: #003a53;
            outline: none;
        }

        .signup-form .icon {
            position: absolute;
            left: 10px;
            top: 38px;
            color: #666;
            font-size: 1em;
        }

        .signup-form .toggle-password {
            position: absolute;
            right: 10px;
            top: 38px;
            color: #666;
            font-size: 1em;
            cursor: pointer;
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
            color: #d32f2f;
        }

        .password-suggestion {
            margin-top: 8px;
            font-size: 0.8em;
            color: #003a53;
            font-style: italic;
            margin-bottom: 15px;
            text-align: left;
        }

        .signup-form button {
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

        .signup-form button:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }

        .error-message {
            color: #d32f2f;
            font-size: 0.9em;
            font-weight: 500;
            margin-top: 5px;
            margin-bottom: 8px;
            padding: 5px 8px;
            background-color: rgba(255, 235, 235, 0.8);
            border-radius: 4px;
            display: none;
            text-align: left;
        }

        .login-link {
            text-align: center;
            margin-top: 12px;
            font-size: 0.8em;
        }

        .login-link a {
            color: #003a53;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #ffcc00;
        }
    </style>
</head>
<body>
    <div class="storybook-border"></div>

    <div class="navbar">
        <h1>DreamScribeAi</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="login.php" class="login-btn">Log In</a>
        </div>
    </div>

    <div class="container">
        <div class="signup-form" id="signup">
            <h1>SIGN UP</h1>
            <p class="subtitle">Create your account to get started!</p>

            <form id="signup-form" method="POST">
                <p class="error-message" id="error-message"></p>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <i class="fas fa-user icon"></i>
                        <input type="text" id="firstName" name="firstName" placeholder="Your First Name" required>
                        <p class="error-message" id="firstNameLengthError">First name must not exceed 15 characters.</p>
                        <p class="error-message" id="firstNameCharError">First name must contain only English letters (A-Z, a-z).</p>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <i class="fas fa-user icon"></i>
                        <input type="text" id="lastName" name="lastName" placeholder="Your Last Name" required>
                        <p class="error-message" id="lastNameLengthError">Last name must not exceed 15 characters.</p>
                        <p class="error-message" id="lastNameCharError">Last name must contain only English letters (A-Z, a-z).</p>
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        <p class="error-message" id="emailFormatError">Please enter a valid email.</p>
                        <p class="error-message" id="emailLengthError">Email must not exceed 50 characters.</p>
                        <p class="error-message" id="emailDomainError">Email must be from @gmail.com, @hotmail.com, or @mail.com.</p>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        <p class="error-message" id="passwordError">Password must meet all conditions.</p>
                        <p class="error-message" id="passwordLengthError">Password must not exceed 30 characters.</p>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        <p class="error-message" id="confirmPasswordError">Passwords do not match.</p>
                    </div>
                </div>

                <ul class="password-conditions" id="password-conditions">
                    <li id="condition-length">At least 8 characters</li>
                    <li id="condition-uppercase">At least one uppercase letter</li>
                    <li id="condition-lowercase">At least one lowercase letter</li>
                    <li id="condition-special">At least one special character</li>
                    <li id="condition-max-length">Maximum 30 characters</li>
                </ul>

                <div class="password-suggestion" id="password-suggestion"></div>

                <button type="submit" name="submit">CREATE</button>

                <div class="login-link">
                    Already have an account? <a href="login.php">LOG IN</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            console.log("Page loaded!");

            const form = document.getElementById("signup-form");
            const passwordInput = document.getElementById("password");
            const confirmPasswordInput = document.getElementById("confirm_password");
            const passwordSuggestion = document.getElementById("password-suggestion");
            const errorMessage = document.getElementById("error-message");
            const firstNameInput = document.getElementById("firstName");
            const lastNameInput = document.getElementById("lastName");
            const emailInput = document.getElementById("email");
            const firstNameLengthError = document.getElementById("firstNameLengthError");
            const firstNameCharError = document.getElementById("firstNameCharError");
            const lastNameLengthError = document.getElementById("lastNameLengthError");
            const lastNameCharError = document.getElementById("lastNameCharError");
            const emailFormatError = document.getElementById("emailFormatError");
            const emailLengthError = document.getElementById("emailLengthError");
            const emailDomainError = document.getElementById("emailDomainError");
            const passwordError = document.getElementById("passwordError");
            const passwordLengthError = document.getElementById("passwordLengthError");
            const confirmPasswordError = document.getElementById("confirmPasswordError");
            const togglePassword = document.getElementById("togglePassword");
            const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");

            function generatePasswordSuggestion() {
                const uppercaseLetters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                const lowercaseLetters = "abcdefghijklmnopqrstuvwxyz";
                const numbers = "0123456789";
                const specialCharacters = "!@#$%^&*(),.?\":{}|<>";

                let password = uppercaseLetters[Math.floor(Math.random() * uppercaseLetters.length)];
                password += lowercaseLetters[Math.floor(Math.random() * lowercaseLetters.length)];
                password += numbers[Math.floor(Math.random() * numbers.length)];
                password += specialCharacters[Math.floor(Math.random() * specialCharacters.length)];

                const allCharacters = uppercaseLetters + lowercaseLetters + numbers + specialCharacters;
                for (let i = 4; i < 12; i++) {
                    password += allCharacters[Math.floor(Math.random() * allCharacters.length)];
                }

                return password.split('').sort(() => Math.random() - 0.5).join('');
            }

            function validatePassword() {
                const password = passwordInput.value;
                const conditionLength = document.getElementById("condition-length");
                const conditionUppercase = document.getElementById("condition-uppercase");
                const conditionLowercase = document.getElementById("condition-lowercase");
                const conditionSpecial = document.getElementById("condition-special");
                const conditionMaxLength = document.getElementById("condition-max-length");

                conditionLength.className = password.length >= 8 ? "valid" : "invalid";
                conditionUppercase.className = /[A-Z]/.test(password) ? "valid" : "invalid";
                conditionLowercase.className = /[a-z]/.test(password) ? "valid" : "invalid";
                conditionSpecial.className = /[!@#$%^&*(),.?":{}|<>]/.test(password) ? "valid" : "invalid";
                conditionMaxLength.className = password.length <= 30 ? "valid" : "invalid";

                const isValidPassword = password.length >= 8 && password.length <= 30 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /[!@#$%^&*(),.?":{}|<>]/.test(password);
                passwordSuggestion.textContent = isValidPassword ? "" : `Try this password: ${generatePasswordSuggestion()}`;

                return isValidPassword;
            }

            function validateName(name) {
                return /^[a-zA-Z\s]+$/.test(name);
            }

            // Real-time validation for firstName
            firstNameInput.addEventListener("input", function () {
                const firstName = firstNameInput.value.trim();
                firstNameLengthError.style.display = "none";
                firstNameCharError.style.display = "none";

                if (firstName === "") {
                    return;
                }
                if (firstName.length > 15) {
                    firstNameLengthError.style.display = "block";
                }
                if (!validateName(firstName)) {
                    firstNameCharError.style.display = "block";
                }
            });

            // Real-time validation for lastName
            lastNameInput.addEventListener("input", function () {
                const lastName = lastNameInput.value.trim();
                lastNameLengthError.style.display = "none";
                lastNameCharError.style.display = "none";

                if (lastName === "") {
                    return;
                }
                if (lastName.length > 15) {
                    lastNameLengthError.style.display = "block";
                }
                if (!validateName(lastName)) {
                    lastNameCharError.style.display = "block";
                }
            });

            // Validate email on blur
            emailInput.addEventListener("blur", function () {
                const email = emailInput.value.trim();
                emailFormatError.style.display = "none";
                emailLengthError.style.display = "none";
                emailDomainError.style.display = "none";

                if (email === "") {
                    return;
                }
                if (!email.includes("@") || email.length < 5) {
                    emailFormatError.style.display = "block";
                    return;
                }
                if (email.length > 50) {
                    emailLengthError.style.display = "block";
                    return;
                }
                const allowedDomains = ['@gmail.com', '@hotmail.com', '@mail.com'];
                let isValidDomain = false;
                for (let domain of allowedDomains) {
                    if (email.endsWith(domain)) {
                        isValidDomain = true;
                        break;
                    }
                }
                if (!isValidDomain) {
                    emailDomainError.style.display = "block";
                }
            });

            // Validate password on input
            passwordInput.addEventListener("input", function () {
                passwordLengthError.style.display = "none";
                if (passwordInput.value.length > 30) {
                    passwordLengthError.style.display = "block";
                }
                validatePassword();
            });

            // Toggle password visibility
            togglePassword.addEventListener("click", function () {
                const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
                passwordInput.setAttribute("type", type);
                this.classList.toggle("fa-eye");
                this.classList.toggle("fa-eye-slash");
            });

            // Toggle confirm password visibility
            toggleConfirmPassword.addEventListener("click", function () {
                const type = confirmPasswordInput.getAttribute("type") === "password" ? "text" : "password";
                confirmPasswordInput.setAttribute("type", type);
                this.classList.toggle("fa-eye");
                this.classList.toggle("fa-eye-slash");
            });

            // Validate confirm password on blur
            confirmPasswordInput.addEventListener("blur", function () {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                if (confirmPassword === "") {
                    confirmPasswordError.style.display = "none";
                } else if (password !== confirmPassword) {
                    confirmPasswordError.style.display = "block";
                } else {
                    confirmPasswordError.style.display = "none";
                }
            });

            form.addEventListener("submit", function(event) {
                event.preventDefault();

                errorMessage.style.display = "none";
                firstNameLengthError.style.display = "none";
                firstNameCharError.style.display = "none";
                lastNameLengthError.style.display = "none";
                lastNameCharError.style.display = "none";
                emailFormatError.style.display = "none";
                emailLengthError.style.display = "none";
                emailDomainError.style.display = "none";
                passwordError.style.display = "none";
                passwordLengthError.style.display = "none";
                confirmPasswordError.style.display = "none";

                const firstName = firstNameInput.value.trim();
                const lastName = lastNameInput.value.trim();
                const email = emailInput.value.trim();
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                let isValid = true;

                if (firstName.length > 15) {
                    firstNameLengthError.style.display = "block";
                    isValid = false;
                }
                if (!validateName(firstName)) {
                    firstNameCharError.style.display = "block";
                    isValid = false;
                }
                if (lastName.length > 15) {
                    lastNameLengthError.style.display = "block";
                    isValid = false;
                }
                if (!validateName(lastName)) {
                    lastNameCharError.style.display = "block";
                    isValid = false;
                }
                if (!email.includes("@") || email.length < 5) {
                    emailFormatError.style.display = "block";
                    isValid = false;
                } else {
                    if (email.length > 50) {
                        emailLengthError.style.display = "block";
                        isValid = false;
                    }
                    const allowedDomains = ['@gmail.com', '@hotmail.com', '@mail.com'];
                    let isValidDomain = false;
                    for (let domain of allowedDomains) {
                        if (email.endsWith(domain)) {
                            isValidDomain = true;
                            break;
                        }
                    }
                    if (!isValidDomain) {
                        emailDomainError.style.display = "block";
                        isValid = false;
                    }
                }
                if (password.length > 30) {
                    passwordLengthError.style.display = "block";
                    isValid = false;
                }
                if (!validatePassword()) {
                    passwordError.style.display = "block";
                    isValid = false;
                }
                if (password !== confirmPassword) {
                    confirmPasswordError.style.display = "block";
                    isValid = false;
                }

                if (!isValid) return;

                const formData = new FormData(form);
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }

                fetch("signup.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => {
                    console.log("Raw response:", response);
                    if (!response.ok) {
                        throw new Error("HTTP error, status = " + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Parsed JSON:", data);
                    if (data.status === "success") {
                        window.location.href = "login.php";
                    } else {
                        errorMessage.innerText = data.message;
                        errorMessage.style.display = "block";
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                    errorMessage.innerText = "An error occurred, please try again: " + error.message;
                    errorMessage.style.display = "block";
                });
            });
        });
    </script>
</body>
</html>