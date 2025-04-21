<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
session_start();
require_once '../php/Database.php';

if (!isset($_SESSION['username'])) {
    header("Location: /qr/index.php");
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
                <li><a href="/qr/assets/students/student_file.php"><i class="fa-solid fa-file"></i> Student Logs</a></li>
                <li><a href="/qr/assets/QrScanner/qr_scanner.php"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li>
                <li><a href="/qr/assets/QrGenerator/qr_generator.php"><i class="fa-solid fa-qrcode"></i> QR Generator</a></li>
                <li><a href="/qr/assets/Profile/profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="/qr/logout.php"><i class="fa-solid fa-key"></i> Logout</a></li>
            </ul>
            <div class="menu-icon">
            <i class="fa-solid fa-bars" onclick="toggleMenu()"></i>
        </div>
        </nav>
    </header>

    <div class="content">
        <?php
        include 'calendar/index.php';
        ?>
    </div>

    <script src="https://kit.fontawesome.com/f8e1a90484.js" crossorigin="anonymous"></script>
    <script src="calendar.js"></script>

    <script>
        function toggleMenu() {
            const menuList = document.getElementById('menuList');
            menuList.classList.toggle('show');
        }
    </script>

</body>

</html>