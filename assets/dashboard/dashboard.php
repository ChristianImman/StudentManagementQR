<?php
// Start the session if necessary
session_start();
require_once '../php/Database.php'; // Corrected path to Database.php

try {
    // Create a new database connection
    $database = new Database();
    $db = $database->getConnection();

    // Fetch logs from the database
    $query = "SELECT id, action, username, admin_id, timestamp, details FROM logs ORDER BY timestamp DESC"; // Added id
    $stmt = $db->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all logs as an associative array

    $database->closeConnection(); // Close the database connection
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title>USTP Dashboard</title>
</head>

<body>
    <header>
        <div class="logo">
            <a href="assets/dashboard/dashboard.php"> <img src="/qr/assets/bg/logo.png" alt="logo"> </a>
        </div>
        <nav>
            <ul id="menuList">
                <li><a href="/qr/assets/dashboard/dashboard.php"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="/qr/assets/students/student_file.php"><i class="fa-solid fa-file"></i> Student File</a></li>
                <li><a href="/qr/assets/QrScanner/qr_scanner.php"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li>
                <li><a href="/qr/assets/Profile/profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="/qr/logout.php"><i class="fa-solid fa-key"></i> Logout</a></li>
            </ul>
            <div class="menu-icon">
                <i class="fa-solid fa-bars" onclick="toggleMenu()"></i>
            </div>
        </nav>
    </header>

    <div class="content">
        <h1>Welcome to Your Dashboard</h1>
        <!-- Include the calendar.php file here -->
    </div>

    <!-- Log Table -->
    <div id="logTable" class="logTable" style="display: none;">
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Username</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                        <td>
                            <button onclick="showLogDetails(<?php echo htmlspecialchars($log['id']); ?>)">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Logs Button -->
    <div class="logs-button-container">
        <button class="logs-button" onclick="toggleLogTable()">Logs</button>
    </div>

    <script src="https://kit.fontawesome.com/f8e1a90484.js" crossorigin="anonymous"></script>
    <script src="calendar.js"></script>
    <script>
        function toggleMenu() {
        const menuList = document.getElementById('menuList');
        menuList.classList.toggle('show'); // Toggle the show class
    }

    function toggleLogTable() {
        const logTable = document.getElementById('logTable');
        logTable.style.display = logTable.style.display === 'none' ? 'block' : 'none';
    }

    function showLogDetails(logId) {
        const logDetailsContent = document.getElementById('logDetailsContent');
        logDetailsContent.innerHTML = "Details for log ID: " + logId; // Replace with actual details
        document.getElementById('logDetailsPopup').style.display = 'block';
    }

    function closePopup() {
        document.getElementById('logDetailsPopup').style.display = 'none';
    }
    </script>
</body>

</html>