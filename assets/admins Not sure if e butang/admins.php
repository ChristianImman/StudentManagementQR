

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admins.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title>Admin - Student File</title>
</head>
<body>

<header>
    <div class="logo">
        <a href="/assets/dashboard/dashboard.php"><img src="assets/bg/logo1.png" alt="logo"></a>
    </div>
    <nav>
            <ul id="menuList">
                <li><a href="/qr/assets/dashboard/dashboard.php"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="/qr/assets/students/student_file.php"><i class="fa-solid fa-file"></i> Student File</a></li>
                <li><a href="/qr/assets/QrScanner/qr_scanner.php"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li>
                <li><a href="/qr/assets/admins/admins.php"><i class="fa-solid fa-users"></i> Admin User</a></li>
                <li><a href="/qr/assets/Profile/profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fa-solid fa-key"></i> Logout</a></li>
            </ul>
            <div class="menu-icon">
                <i class="fa-solid fa-bars" onclick="toggleMenu()"></i>
            </div>
</nav>
</header>

<!-- Add Admin Button -->
<button class="btn-add-admin" onclick="openModal()">+ Add Admin</button>

<!-- Modal Form for Adding Admin -->
<div id="addAdminModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add New Admin</h2>
        <form action="" method="POST"> <!-- Submit to the same page -->
            <label>Username:</label>
            <input type="text" name="username" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn-submit">Add Admin</button>
        </form>
    </div>
</div>

<!-- JavaScript for Modal -->
<script>
    function openModal() {
        document.getElementById("addAdminModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("addAdminModal").style.display = "none";
    }
</script>

<section class="p-3">
    <div class="row">
        <div class="col-12">
            <table>
                
            </table>
        </div>
    </div>
</section>

</body>
</html>

