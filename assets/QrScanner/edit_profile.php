<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../php/Database.php';

if (!isset($_SESSION['username'])) {
    header("Location: /assets/QrScanner/qr_scanner.php");
    exit();
}

if (isset($_GET['data'])) {
    $qrData = urldecode($_GET['data']);

    $lines = explode("\n", $qrData);
    $student = [];

    foreach ($lines as $line) {
        $cleanLine = trim($line);
        if (strpos($cleanLine, "Student ID") !== false) {
            $student['studentId'] = trim(str_replace("Student ID:", "", $cleanLine));
        }
        if (strpos($cleanLine, "Name") !== false) {
            $student['name'] = trim(str_replace("Name:", "", $cleanLine));
        }
        if (strpos($cleanLine, "Course") !== false) {
            $student['course'] = trim(str_replace("Course:", "", $cleanLine));
        }
        if (strpos($cleanLine, "Status") !== false) {
            $student['status'] = trim(str_replace("Status:", "", $cleanLine));
        }
        if (strpos($cleanLine, "Year Started") !== false) {
            $student['yearStarted'] = trim(str_replace("Year Started:", "", $cleanLine));
        }
    }
} else {
    die('No QR data found!');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="qr_scanner.css">
    <link rel="stylesheet" href="edit_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
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

    <div class="edit-profile-container">
        <h2>Edit Profile</h2>
        <form id="editProfileForm" action="save_student.php?v=<?= time() ?>" method="POST" class="edit-profile-container">
            <div class="form-group-custom">
                <label for="studentId">Student ID</label>
                <input type="number" id="studentId" name="studentid" value="<?php echo isset($student['studentId']) ? htmlspecialchars($student['studentId']) : ''; ?>" readonly disabled>
            </div>
            <div class="form-group-custom">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo isset($student['name']) ? htmlspecialchars($student['name']) : ''; ?>" disabled>
            </div>
            <div class="form-group-custom">
                <label for="course">Course</label>
                <select id="course" name="course" class="status-dropdown-custom">
                    <option value="N/A" <?php echo (isset($student['course']) && $student['course'] == 'N/A') ? 'selected' : ''; ?>>N/A</option>
                    <option value="BSA" <?php echo (isset($student['course']) && $student['course'] == 'BSA') ? 'selected' : ''; ?>>BSA</option>
                    <option value="BSEMT-IA" <?php echo (isset($student['course']) && $student['course'] == 'BSEMT - IA') ? 'selected' : ''; ?>>BSEMT - IA</option>
                    <option value="BSEMT-MR" <?php echo (isset($student['course']) && $student['course'] == 'BSEMT - MR') ? 'selected' : ''; ?>>BSEMT - MR</option>
                    <option value="BSESM-EMCC" <?php echo (isset($student['course']) && $student['course'] == 'BSESM - EMCC') ? 'selected' : ''; ?>>BSESM - EMCC</option>
                    <option value="BSESM-PSDE" <?php echo (isset($student['course']) && $student['course'] == 'BSESM - PSDE') ? 'selected' : ''; ?>>BSESM - PSDE</option>
                    <option value="BSET-ES" <?php echo (isset($student['course']) && $student['course'] == 'BSET - ES') ? 'selected' : ''; ?>>BSET - ES</option>
                    <option value="BSET-MST" <?php echo (isset($student['course']) && $student['course'] == 'BSET - MST') ? 'selected' : ''; ?>>BSET - MST</option>
                    <option value="BSET-TN" <?php echo (isset($student['course']) && $student['course'] == 'BSET - TN') ? 'selected' : ''; ?>>BSET - TN</option>
                    <option value="BSMET" <?php echo (isset($student['course']) && $student['course'] == 'BSMET') ? 'selected' : ''; ?>>BSMET</option>
                </select>
            </div>
            <div class="form-group-custom">
                <label for="status">Status</label>
                <select id="status" name="status" class="status-dropdown-custom">
                    <option value="N/A" <?php echo (isset($student['status']) && $student['status'] == 'N/A') ? 'selected' : ''; ?>>N/A</option>
                    <option value="Active" <?php echo (isset($student['status']) && $student['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo (isset($student['status']) && $student['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group-custom">
                <label for="yearStarted">Year Started</label>
                <select id="yearStarted" name="yearStarted" disabled>
                    <?php for ($year = date("Y"); $year >= 1900; $year--): ?>
                        <option value="<?= $year ?>" <?= (isset($student['yearStarted']) && $student['yearStarted'] == $year) ? 'selected' : ''; ?>><?= $year ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="button-group-custom">
                <button type="submit" id="submitBtn">Save Changes</button>
                <button type="button" id="cancelBtn">Cancel</button>
            </div>

        </form>
    </div>

    <script src="/qr/assets/js/edit_profile.js"></script>
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