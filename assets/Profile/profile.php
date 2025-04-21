<?php
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
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title>Admin Profile</title>
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

    <main>
        <div class="card">
            <div class="photo-placeholder">add photo</div>
            <div class="name">Raymond Ray Saldua</div>
            <div class="email">raymondray.sald@ustp.edu.ph</div>
            <div class="id">123457890</div>
            <div class="role">FACULTY/ADMIN</div>
            <div class="last-edit">Last Edit: mm/dd/yyyy hh:mm</div>
            <div class="settings">Settings</div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menuList = document.getElementById('menuList');
            menuList.classList.toggle('show');
        }
    </script>
</body>

</html>