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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
            <div class="menu-icon" onclick="toggleMenu()">
                <i class="fa-solid fa-bars"></i>
            </div>
        </nav>
    </header>


    <div id="qrCode" class="QR">
        <div class="qr-container">
            <p class="p">QR Code Generator</p>
            <div>
                <label for="studentId">Student ID</label>
                <input type="text" id="studentId" placeholder="Enter Student ID" required maxlength="10"
                    title="Please enter up to 10 digits." oninput="this.value = this.value.replace(/[^0-9]/g, '');" />
            </div>
            <div class="form-group">
                <div>
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" placeholder="First Name" required />
                </div>
                <div>
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" placeholder="Last Name" required />
                </div>
            </div>
            <div class="form-group">
                <div class="middle-initial">
                    <label for="middleInitial">Middle Initial</label>
                    <input type="text" id="middleInitial" placeholder="M.I" maxlength="1" />
                </div>
                <div class="suffix">
                    <label for="suffix">Suffix</label>
                    <input type="text" id="suffix" placeholder="Jr, Sr, II, III, IV, etc" />
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label for="yearStarted">Year Started</label>
                    <select id="yearStarted" required></select>
                </div>
            </div>
            <div class="button-group">
                <button id="generatePrintQRButton">Generate & Print QR Code</button>
                <button id="fileUploadButton">Upload File</button>
            </div>

            <div id="qrcode" class="qr-code" style="margin-top: 10px;"></div>

            <input type="file" id="fileInput" style="display: none;" accept=".csv, .xlsx" />

            <div id="qrProgressContainer" style="display: none; text-align: center; margin-top: 10px;">
                <p>Generating QR Codes:
                    <span id="qrProgressCount">0</span>/<span id="qrProgressTotal">0</span>
                </p>
            </div>

            <div id="uploadResultModal" class="modal">
                <div class="modal-content" style="max-width: 90vw; overflow-x: auto;">
                    <h2>Upload Results</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Year Started</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="uploadResultBody" class="data"></tbody>
                    </table>
                    <button id="closeUploadResults">Close</button>
                </div>
            </div>

            <div id="qrModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div id="qrDisplayContainer"></div>
                    <h2 id="modalStudentInfo"></h2>
                    <div id="modalButtons" class="modal-buttons">
                        <button id="printBtn" class="printBtn">Print</button>
                        <button id="downloadBtn" class="downloadBtn">Download QR Code</button>
                        <button id="closeBtn" class="closeBtn">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ QR Generator Logic -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="/qr/assets/js/qr_generator.js"></script>


    <!-- ✅ Menu toggle -->
    <script>
        function toggleMenu() {
            const menuList = document.getElementById("menuList");
            const overlay = document.getElementById("overlay");
            menuList.classList.toggle("show");
            overlay.classList.toggle("active");
        }
    </script>
</body>

</html>