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
    <link rel="stylesheet" href="qr_generator.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title>QR Generator</title>
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

    <div id="qrCode" class="QR">
        <div class="qr-container">
            <p class="p">QR Code Generator</p>
            <div>
                <label for="studentId">Student ID</label>
                <input type="text" id="studentId" placeholder="Enter Student ID" required maxlength="10" title="Please enter up to 10 digits." oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>
            <div class="form-group">
                <div>
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" placeholder="First Name" required title="Please enter letters only">
                </div>
                <div>
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" placeholder="Last Name" required title="Please enter letters only">
                </div>
            </div>
            <div class="form-group">
                <div class="middle-initial">
                    <label for="middleInitial">Middle Initial</label>
                    <input type="text" id="middleInitial" placeholder="M.I" required maxlength="1" title="Please enter a single letter">
                </div>
                <div class="suffix">
                    <label for="suffix">Suffix</label>
                    <input type="text" id="suffix" placeholder="Jr, Sr, II, III, IV, etc" required oninput="validateInput(this)">
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label for="yearStarted">Year Started</label>
                    <select id="yearStarted" required>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>
            </div>
            <div class="button-group">
                <button id="generateQRButton">Generate QR Code</button>
                <button id="printQRButton">Print QR CODE</button>
            </div>
            <div id="qrcode" class="qr-code"></div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/f8e1a90484.js" crossorigin="anonymous"></script>
    <script src="/qr/assets/js/qr_generator.js"></script>
    <script src="/qr/assets/js/qrcode.js"></script>
    <script>
       window.onload = function() {
            window.history.replaceState(null, null, window.location.href);
            window.history.pushState('forward', null, './#forward');
            window.onpopstate = function() {
                window.history.pushState('forward', null, './#forward');
                window.location.replace("/qr/assets/QrGenerator/qr_generator.php");
            };
        };


        function toggleMenu() {
            const menuList = document.getElementById('menuList');
            menuList.classList.toggle('show'); // Toggle the show class
        }

        window.onload = function() {
            // Check if there is a message in the session
            <?php if (isset($_SESSION['message'])): ?>
                var message = "<?php echo $_SESSION['message']; ?>";
                var messageType = "<?php echo $_SESSION['message_type']; ?>";
                var messageDiv = document.createElement("div");
                messageDiv.className = "message " + messageType; // Add success or error class
                messageDiv.innerText = message;

                document.body.appendChild(messageDiv);
                messageDiv.style.display = "block"; // Show the message

                // Automatically hide the message after 5 seconds
                setTimeout(function() {
                    messageDiv.style.display = "none";
                    document.body.removeChild(messageDiv); // Remove the message from the DOM
                }, 5000);
            <?php
                // Clear the message after displaying it
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            endif; ?>
        }

        // Function to restrict input to letters only
        function restrictInputToLetters(event) {
            const input = event.target;
            const value = input.value;
            const regex = /^[A-Za-z]*$/; // Regular expression for letters only

            // If the input value does not match the regex, remove the last character
            if (!regex.test(value)) {
                input.value = value.slice(0, -1); // Remove the last character
            }
        }

        // Add event listeners to the input fields
        document.getElementById("firstName").addEventListener("input", restrictInputToLetters);
        document.getElementById("lastName").addEventListener("input", restrictInputToLetters);
        document.getElementById("middleInitial").addEventListener("input", restrictInputToLetters);
    </script>
</body>

</html>