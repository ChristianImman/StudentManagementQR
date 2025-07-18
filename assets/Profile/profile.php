<?php
session_start();
require_once '../php/Database.php';

$db = new Database();
$conn = $db->getConnection();

$username = $_SESSION['username'];

$sql = "SELECT username, first_name, last_name, email_address, registrar_id, created_at, profile_photo FROM admins WHERE username = :username";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$photoPath = (!empty($user['profile_photo']) && file_exists($user['profile_photo']))
    ? $user['profile_photo']
    : null;

$formattedDate = $user && $user['created_at']
    ? date("M d, Y h:i A", strtotime($user['created_at']))
    : 'N/A';

if (!isset($_SESSION['username'])) {
    header("Location: /index.php");
    exit();
}

$updatedUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;


if (isset($_GET['delete']) && $_GET['delete'] == 'true') {
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];

        
        $deleteSql = "DELETE FROM admins WHERE username = :username";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bindParam(':username', $username);

        
        if ($deleteStmt->execute()) {
            
            session_destroy();  
            header("Location: /qr/index.php");
            exit();
        } else {
            
            $_SESSION['message'] = "Account deletion failed. Please try again.";
            $_SESSION['message_type'] = "error";
        }
    }
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
            <div class="menu-icon" onclick="toggleMenu()">
                <i class="fa-solid fa-bars"></i>
            </div>
        </nav>
    </header>

    <main>
        <div class="card">
            <div class="photo-section">
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;" onchange="previewPhoto(event)">
                    <div class="photo-placeholder" onclick="document.getElementById('photoInput').click()">
                        <?php if ($photoPath): ?>
                            <img id="preview-img" src="<?= $photoPath ?>" alt="Profile Photo" />
                        <?php else: ?>
                            <span id="placeholder-text">Add Photo</span>
                        <?php endif; ?>
                    </div>


                    <div class="photo-actions" id="photoActions">
                        <button type="button" id="uploadPhotoBtn" class="save-btn">Save</button>
                        <button type="button" class="cancel-btn" onclick="cancelPhoto()">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="details">
                <div class="name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                <div class="email"><?= htmlspecialchars($user['email_address']) ?></div>
                <div class="id"><?= htmlspecialchars($user['registrar_id']) ?></div>
                <div class="info-box">FACULTY/ADMIN</div>
                <div class="info-box">Last Edit <?= $formattedDate ?></div>
                <button class="settings-button" onclick="openSettings()">Settings</button>
            </div>
        </div>

        <!-- Settings Container -->
        <div class="settings-container" id="settingsPopup">
            <div class="settings-content">
                <h2>SETTINGS</h2>

                <!-- CHANGE FULL NAME -->
                <div class="setting-item">
                    <label>CHANGE FULL NAME</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" value="<?= htmlspecialchars($user['first_name']) ?>" id="changeFirstName" placeholder="First Name" />
                        <input type="text" value="<?= htmlspecialchars($user['last_name']) ?>" id="changeLastName" placeholder="Last Name" />
                    </div>
                </div>

                <!-- CHANGE EMAIL -->
                <div class="setting-item">
                    <label>CHANGE EMAIL</label>
                    <input type="email" value="<?= htmlspecialchars($user['email_address']) ?>" id="changeEmail" />
                </div>

                <!-- CHANGE PASSWORD -->
                <div class="setting-item">
                    <label>CHANGE PASSWORD</label>
                    <input type="password" placeholder="Enter new password" id="changePassword" />
                </div>

                <!-- ACCOUNT DELETION -->
                <div class="delete-account" onclick="confirmDelete()">Account deletion</div>

                <!-- SAVE/Cancel buttons -->
                <div class="button-container">
                    <button class="save" onclick="saveChanges()">Save Changes</button>
                    <button class="disagree" onclick="closeSettings()">Disagree</button>
                </div>
            </div>
        </div>

        <!-- Delete Account Confirmation -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-content">
                <h3>Are you sure you want to delete your account?</h3>
                <button class="confirm-delete" onclick="deleteAccount()">Yes, delete my account</button>
                <button class="cancel-delete" onclick="cancelDelete()">Cancel</button>
            </div>
        </div>
    </main>

    <script src="profile.js"></script> <!-- External JS file -->

    <script>
        function toggleMenu() {
            const menuList = document.getElementById("menuList");
            const overlay = document.getElementById("overlay");
            menuList.classList.toggle("show");
            overlay.classList.toggle("active");
        }

        function saveChanges() {
            var firstName = document.getElementById('changeFirstName').value;
            var lastName = document.getElementById('changeLastName').value;
            var email = document.getElementById('changeEmail').value;
            var password = document.getElementById('changePassword').value;

            var data = {
                first_name: firstName,
                last_name: lastName,
                email_address: email,
                password: password
            };

            fetch('/qr/assets/Profile/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message || 'Profile updated successfully');
                        closeSettings(); 
                    } else {
                        alert(result.error || 'Failed to update profile');
                    }
                })
                .catch(error => {
                    alert('Error updating profile');
                    console.error(error);
                });
        }

        function closeSettings() {
            document.getElementById('settingsPopup').style.display = 'none';
        }
    </script>



</body>

</html>