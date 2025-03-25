<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <!-- Modal Structure -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
         <span class="close">&times;</span>
        <h2>QR Code Generator</h2>
            <input type="text" id="studentId" placeholder="Student ID">
            <input type="text" id="name" placeholder="Name">
            <input type="text" id="course" placeholder="Course">
            <input type="text" id="yearStarted" placeholder="MM, DD, YYYY Year Started">
            <input type="text" id="status" placeholder="Status (Active/Inactive)">
            <button onclick="generateQR()">Generate QR Code</button>
            <div id="qrcode"></div>
            <button onclick="printQR()">Print</button>
        </div>
    </div>

    <script src="/assets/js/qr_generator.js"></script>
</body>
</html>