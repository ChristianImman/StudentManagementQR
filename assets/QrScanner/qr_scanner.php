<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner</title>
    <link rel="stylesheet" href="assets/dashboard/dashboard.css">
    <link rel="stylesheet" href="qr_scanner.css"> <!-- Corrected path -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="/assets/dashboard/dashboard.php"><img src="/qr/assets/bg/logo.png" alt="logo"></a>
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

<div class="qr-scanner">
    <div class="wrapper">
        <form class="qr-form">
            <div class="open-camera">Open Camera</div>
            <div class="file-input-container">
                <label for="file-input">Upload File:</label>
                <input type="file" id="file-input" accept="image/*" />
            </div>
            <div id="reader" style="display:none;"></div>
        </form>
        <div class="details">
            <textarea spellcheck="false" disabled></textarea>
            <div class="buttons">
                <button type="button" class="edit">Edit</button> 
                <button type="button" class="save">Save</button>
                <button type="button" class="scan-again" style="display:none;">Scan Again</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing -->
<div id="editModal" class="modal custom-modal">
    <div class="modal-content custom-modal-content">
        <span id="modalClose" class="close custom-close">&times;</span>
        <h2>Edit Scanned Data</h2>
        <textarea id="editText" rows="10" style="width: 100%;"></textarea>
        <button id="modalSave">Save Changes</button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script src="/qr/assets/js/qr_scanner.js"></script>
<script>
    function toggleMenu() {
        const menuList = document.getElementById('menuList');
        menuList.classList.toggle('show');
    }
</script>
</body>
</html>