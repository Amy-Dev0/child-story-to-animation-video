<?php
session_start();

// Include database connection if you want to save form data (optional, uncomment and configure if needed)
// include 'db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Basic validation (mirrors JavaScript for consistency)
    $errors = [];
    $nameRegex = '/^[a-zA-Z\sء-ي]+$/';
    $emailRegex = '/^[a-zA-Z0-9._%+-]+@(hotmail|gmail|mail)\.[a-zA-Z]{2,}$/';
    $phoneRegex = '/^\+?[0-9]+$/';
    $isValidPhoneLength = (strlen($phone) === 10 || (strpos($phone, '+966') === 0 && strlen($phone) === 13));

    if (empty($name)) $errors[] = "Name cannot be empty.";
    elseif (!preg_match($nameRegex, $name)) $errors[] = "Name must contain only Arabic or English letters.";
    elseif (strlen($name) > 30) $errors[] = "Name cannot exceed 30 characters.";

    if (empty($email)) $errors[] = "Email cannot be empty.";
    elseif (!preg_match($emailRegex, $email)) $errors[] = "Email must be from @hotmail, @gmail, or @mail.";

    if (empty($phone)) $errors[] = "Phone cannot be empty.";
    elseif (!preg_match($phoneRegex, $phone)) $errors[] = "Phone number must contain only digits (optional + at the start).";
    elseif (!$isValidPhoneLength) $errors[] = "Phone number must be 10 digits, or 13 digits with +966.";

    if (empty($message)) $errors[] = "Message cannot be empty.";

    if (empty($errors)) {
        // Save to a log file or database (example log file approach)
        $logEntry = "Date: " . date('Y-m-d H:i:s') . "\nName: $name\nEmail: $email\nPhone: $phone\nMessage: $message\n\n";
        file_put_contents('C:/xampp/htdocs/DreamScribeAi/contact_logs.txt', $logEntry, FILE_APPEND);

        // Optionally, redirect or show success message
        $_SESSION['contactSuccess'] = "Thank you for your message! We will get back to you soon.";
        header("Location: contact.php");
        exit();
    } else {
        $_SESSION['contactErrors'] = $errors;
        header("Location: contact.php");
        exit();
    }
}

// Retrieve any session messages
$successMessage = isset($_SESSION['contactSuccess']) ? $_SESSION['contactSuccess'] : '';
$errors = isset($_SESSION['contactErrors']) ? $_SESSION['contactErrors'] : [];
unset($_SESSION['contactSuccess']);
unset($_SESSION['contactErrors']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - DreamScribeAi</title>
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
            background: url('images/9952242.jpg') no-repeat center center fixed;
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
            font-size: 3.7em;
            color: #003a53;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-content p {
            font-size: 1.25em;
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
        input[type="email"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-family: 'Open Sans', sans-serif;
        }

        textarea {
            resize: vertical;
        }

        .submit-btn {
            background-color: rgb(205, 56, 2);
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            font-family: 'Open Sans', sans-serif;
        }

        .submit-btn:hover {
            background-color: #ffcc00;
            color: #003a53;
            transform: scale(1.02);
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .success-message {
            color: green;
            font-size: 16px;
            margin-top: 10px;
            display: <?php echo $successMessage ? 'block' : 'none'; ?>;
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
            <h1>CONTACT US</h1>
            <p>We’d love to hear from you! Fill out the form below to get in touch.</p>
        </div>
    </div>

    <!-- Form Section -->
    <div class="form-section">
        <div class="form-container">
            <h2>GET IN TOUCH</h2>
            <form id="contact-form" action="" method="POST">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required>
                    <p id="name-error" class="error-message"></p>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <p id="email-error" class="error-message"></p>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" required>
                    <p id="phone-error" class="error-message"></p>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                    <p id="message-error" class="error-message"></p>
                </div>
                <button type="submit" class="submit-btn">Submit</button>
                <p class="success-message"><?php echo htmlspecialchars($successMessage); ?></p>
                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <p class="error-message" style="display: block;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Real-time validation for name
            document.getElementById('name').addEventListener('input', function() {
                const name = this.value.trim();
                const nameError = document.getElementById('name-error');
                const nameRegex = /^[a-zA-Z\sء-ي]+$/;

                if (name.length === 0) {
                    nameError.textContent = "This field cannot be empty.";
                    nameError.style.display = 'block';
                } else if (!nameRegex.test(name)) {
                    nameError.textContent = "Name must contain only Arabic or English letters.";
                    nameError.style.display = 'block';
                } else if (name.length > 30) {
                    nameError.textContent = "Name cannot exceed 30 characters.";
                    nameError.style.display = 'block';
                } else {
                    nameError.style.display = 'none';
                }
            });

            // Real-time validation for email
            document.getElementById('email').addEventListener('input', function() {
                const email = this.value.trim();
                const emailError = document.getElementById('email-error');
                const emailRegex = /^[a-zA-Z0-9._%+-]+@(hotmail|gmail|mail)\.[a-zA-Z]{2,}$/;

                if (email.length === 0) {
                    emailError.textContent = "This field cannot be empty.";
                    emailError.style.display = 'block';
                } else if (!emailRegex.test(email)) {
                    emailError.textContent = "Email must be from @hotmail, @gmail, or @mail.";
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            });

            // Real-time validation for phone
            document.getElementById('phone').addEventListener('input', function() {
                const phone = this.value.trim();
                const phoneError = document.getElementById('phone-error');
                const phoneRegex = /^\+?[0-9]+$/;
                const isValidLength = (phone.length === 10 || (phone.startsWith('+966') && phone.length === 13));

                if (phone.length === 0) {
                    phoneError.textContent = "This field cannot be empty.";
                    phoneError.style.display = 'block';
                } else if (!phoneRegex.test(phone)) {
                    phoneError.textContent = "Phone number must contain only digits (optional + at the start).";
                    phoneError.style.display = 'block';
                } else if (!isValidLength) {
                    phoneError.textContent = "Phone number must be 10 digits, or 13 digits with +966.";
                    phoneError.style.display = 'block';
                } else {
                    phoneError.style.display = 'none';
                }
            });

            // Real-time validation for message
            document.getElementById('message').addEventListener('input', function() {
                const message = this.value.trim();
                const messageError = document.getElementById('message-error');

                if (message.length === 0) {
                    messageError.textContent = "This field cannot be empty.";
                    messageError.style.display = 'block';
                } else {
                    messageError.style.display = 'none';
                }
            });

            // Handle form submission
            const contactForm = document.getElementById("contact-form");
            if (contactForm) {
                contactForm.addEventListener("submit", function(event) {
                    // Prevent default submission to allow PHP to handle it
                    event.preventDefault();
                    this.submit(); // Let PHP handle the form
                });
            }
        });
    </script>
</body>
</html>