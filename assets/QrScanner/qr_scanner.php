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
    <title>QR Scanner</title>
    <link rel="stylesheet" href="qr_scanner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>

<body>

    <header>
        <div class="logo">
            <a href="/qr/assets/dashboard/dashboard.php"> <img src="/qr/assets/bg/logo.png" alt="logo"> </a>
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
            <div class="menu-icon" onclick="toggleMenu()">
                <i class="fa-solid fa-bars"></i>
            </div>
        </nav>
    </header>

    <div class="qr-scanner">
        <div class="wrapper">
            <form class="qr-form">
                <div class="open-camera">Open Camera</div>
                <div class="file-input-container">
                    <label for="file-input">Upload File:</label>
                    <input type="file" id="file-input" accept="image/*" />
                </div>
                <button type="button" id="submitBtn" style="display: none;">Submit</button>

                <!-- QR Reader Container -->
                <div id="reader" style="display: none; width: 100%;"></div>

                <!-- Switch Camera Button -->
                <button type="button" class="switch-camera" id="switchCameraButton" style="display: none;">
                    🔄 Switch Camera
                </button>
            </form>

            <div class="details">
                <textarea spellcheck="false" disabled></textarea>
                <div class="buttons">
                    <button type="button" class="edit">Edit</button>
                    <button type="button" class="scan-again">Scan Again</button>
                </div>
            </div>
        </div>
    </div>


    <!-- ✅ Correct library version -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <!-- ✅ Your custom scanner script -->
    <script src="/qr/assets/js/qr_scanner.js"></script>

    <script>
        function toggleMenu() {
            const menuList = document.getElementById("menuList");
            const overlay = document.getElementById("overlay");
            menuList.classList.toggle("show");
            overlay.classList.toggle("active");
        }

        function editProfile() {
            const editButton = document.querySelector(".edit");
            if (!editButton.disabled) {
                editButton.click(); 
            }
        }
    </script>
</body>

</html>