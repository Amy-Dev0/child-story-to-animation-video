<?php
session_start();
session_regenerate_id(true); // Update session ID to prevent hijacking
include 'db_connect.php';

// Check if guardianID or adminID is set in the session
if (!isset($_SESSION['guardianID']) && !isset($_SESSION['adminID'])) {
    header("Location: login.php"); // Redirect to login if not authenticated
    exit();
}

$guardian_id = isset($_SESSION['guardianID']) ? $_SESSION['guardianID'] : null;
$admin_id = isset($_SESSION['adminID']) ? $_SESSION['adminID'] : null;

// Handle delete request
if (isset($_GET['delete_child'])) {
    $child_id = intval($_GET['delete_child']);
    error_log("Delete request received: child_id=$child_id, guardian_id=$guardian_id");

    if ($guardian_id) {
        $stmt = $conn->prepare("SELECT guardianID FROM child WHERE childID = ?");
        $stmt->bind_param("i", $child_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $child = $result->fetch_assoc();
        $stmt->close();

        error_log("Child query result: " . print_r($child, true));

        if ($child && $child['guardianID'] == $guardian_id) {
            $stmt = $conn->prepare("DELETE FROM child WHERE childID = ?");
            $stmt->bind_param("i", $child_id);
            if ($stmt->execute()) {
                $message = "Room deleted successfully.";
                $message_class = "success-message";
            } else {
                $message = "Error: Failed to delete the room. " . $stmt->error;
                $message_class = "error-message";
                error_log("Delete query failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $message = "Error: Room not found or you don't have permission to delete it.";
            $message_class = "error-message";
            error_log("Permission check failed: child=" . print_r($child, true) . ", guardian_id=$guardian_id");
        }
    }

    header("Location: room.php?message=" . urlencode($message) . "&message_class=" . urlencode($message_class));
    exit();
}

// Handle message from URL
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$message_class = isset($_GET['message_class']) ? urldecode($_GET['message_class']) : '';

// Fetch children data
$children = [];
if ($guardian_id) {
    $sql = "SELECT childID, name, age, gender FROM child WHERE guardianID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $guardian_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $children = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$room_count = count($children);
$max_rooms = 20;
$can_add_room = $room_count < $max_rooms;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - DreamScribeAi</title>
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
            background: url('images/3773592.jpg') no-repeat center center fixed;
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

        /* Rooms Section */
        .rooms-section {
            padding: 50px 40px;
            z-index: 2;
        }

        .rooms-container {
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

        .rooms-container h2 {
            font-family: 'Annie Use Your Telescope', cursive;
            font-size: 2em;
            color: #003a53;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .room-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .room {
            text-align: center;
            transition: transform 0.3s ease;
            position: relative;
        }

        .room:hover {
            transform: scale(1.1);
        }

        .room img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #003a53;
        }

        .room p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        .room p strong {
            color: #003a53;
        }

        .delete-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(255, 77, 77, 0.8);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .room:hover .delete-icon {
            display: flex;
        }

        .delete-icon:hover {
            background-color: #cc0000;
        }

        .max-rooms-message {
            font-size: 14px;
            color: #ff4d4d;
            margin-top: 10px;
        }

        .success-message {
            font-size: 14px;
            color: #28a745;
            margin-bottom: 10px;
            text-align: center;
        }

        .error-message {
            font-size: 14px;
            color: #ff4d4d;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const roomContainer = document.getElementById("room-container");

            // Retrieve room data from localStorage
            const newRoomData = JSON.parse(localStorage.getItem("newRoom"));

            if (newRoomData) {
                const newRoom = document.createElement("div");
                newRoom.className = "room";

                const roomImage = newRoomData.gender === "girl" ? "images/girl1.jpg" : "images/boy.jpg";
                newRoom.innerHTML = `
                    <a href="more.php"><img src="${roomImage}" alt="${newRoomData.name}"></a>
                    <span class="delete-icon" onclick="confirmDelete(${newRoomData.childID})">✖</span>
                    <p><strong>${newRoomData.name}</strong></p>
                    <p>Age: ${newRoomData.age}</p>
                `;

                roomContainer.insertBefore(newRoom, roomContainer.lastElementChild);
                localStorage.removeItem("newRoom");
            }
        });

        function confirmDelete(childId) {
            if (confirm("Are you sure you want to delete this room?")) {
                window.location.href = "room.php?delete_child=" + childId;
            }
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
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <h1>YOUR ROOMS</h1>
            <p>Explore the magical spaces created for your children!</p>
        </div>
    </div>

    <!-- Rooms Section -->
    <div class="rooms-section">
        <div class="rooms-container">
            <h2>ROOM LIST</h2>
            <?php if ($message): ?>
                <p class="<?php echo htmlspecialchars($message_class); ?>"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <div class="room-container" id="room-container">
                <?php foreach ($children as $child): ?>
                    <div class="room">
                        <a href="story_options.php">
                            <img src="<?php echo ($child['gender'] == 'girl') ? 'images/girl1.jpg' : 'images/boy.jpg'; ?>" alt="<?php echo htmlspecialchars($child['name']); ?>">
                        </a>
                        <span class="delete-icon" onclick="confirmDelete(<?php echo $child['childID']; ?>)">✖</span>
                        <p><strong><?php echo htmlspecialchars($child['name']); ?></strong></p>
                        <p>Age: <?php echo htmlspecialchars($child['age']); ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if ($can_add_room): ?>
                    <div class="room">
                        <a href="create-room.php">
                            <img src="https://cdn-icons-png.flaticon.com/512/1828/1828817.png" alt="Add Room">
                        </a>
                        <p><strong>Add Room</strong></p>
                    </div>
                <?php else: ?>
                    <p class="max-rooms-message">Maximum of 20 rooms reached.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>