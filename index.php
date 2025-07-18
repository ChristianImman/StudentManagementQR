<?php
session_start();

$message = $_SESSION['message'] ?? null;
$messageType = $_SESSION['message_type'] ?? 'info';

unset($_SESSION['message'], $_SESSION['message_type']);

if (isset($_SESSION['username'])) {
    header("Location: /qr/assets/dashboard/dashboard.php");
    exit();
}

$error_message = "";
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="/qr/assets/css/login.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="assets/bg/logo.png" alt="Logo" class="logo" />
        </div>
        <div class="wrapper">
            <?php if ($message): ?>
                <?php
                $messageClass = ($messageType === 'success') ? 'success-message' : 'error-message';
                ?>
                <div class="<?= $messageClass ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <script>
                    setTimeout(() => {
                        const msg = document.querySelector('.<?= $messageClass ?>');
                        if (msg) {
                            msg.style.opacity = '0';
                            msg.style.transition = 'opacity 0.5s ease';
                            setTimeout(() => msg.remove(), 500);
                        }
                    }, 3000);
                </script>
            <?php endif; ?>


            <h1>Login</h1>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form id="loginForm" action="login.php" method="post">
                <div class="input-box">
                    <input type="text" placeholder="Username" name="username"
                        value="<?= isset($_COOKIE['remember_username']) ? htmlspecialchars($_COOKIE['remember_username']) : '' ?>"
                        required />
                    <i class="bx bxs-user"></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="Password" name="password"
                        <?= isset($_COOKIE['remember_username']) ? 'autofocus' : '' ?> required />
                    <i class="bx bxs-lock-alt"></i>
                </div>
                <div class="remember">
                    <label>
                        <input type="checkbox" name="remember" />
                        Remember Me
                    </label>
                </div>
                <button type="submit" class="btn">Login</button>

                <p class="register" style="padding-top: 10px">
                    Don't have an account?
                    <a id="openRegisterModal"
                        style="color: lightblue; background: transparent; text-decoration: none; cursor: pointer;">Click here to
                        Register</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Modal for Registration -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeRegisterModal">&times;</span>
            <h2>Registration Form</h2>

            <form id="registerForm" method="POST" action="register.php" onsubmit="return validateEmail();">

                <div class="form-group">
                    <div>
                        <label for="registrar_id">Registrar ID:</label>
                        <input type="text" id="registrar_id" name="registrar_id" pattern="\d{10}" maxlength="10"
                            required title="Registrar ID must be exactly 10 digits" placeholder="Registrar ID must be exactly 10 digits">
                    </div>
                </div>

                <div class="form-group">
                    <div>
                        <label for="regUsername">Username:</label>
                        <input type="text" id="regUsername" name="regUsername" required>
                    </div>
                    <div>
                        <label for="regPassword">Password:</label>
                        <input type="password" id="regPassword" name="regPassword" minlength="8" required>
                    </div>
                </div>

                <div class="form-group">
                    <div>
                        <label for="fName">First Name:</label>
                        <input type="text" id="fName" name="fName" required>
                    </div>
                    <div>
                        <label for="lName">Last Name:</label>
                        <input type="text" id="lName" name="lName" required>
                    </div>
                </div>

                <div class="form-group">
                    <div>
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div>
                        <label for="yearStarted">Date of Birth:</label>
                        <input type="date" id="yearStarted" name="yearStarted" required>
                    </div>
                </div>

                <div class="button-group">
                    <input type="submit" name="register" value="Register">
                </div>
            </form>
        </div>
    </div>

    <script>
        const registerModal = document.getElementById("registerModal");
        const openRegisterModal = document.getElementById("openRegisterModal");
        const closeRegisterModal = document.getElementById("closeRegisterModal");

        
        openRegisterModal.onclick = () => registerModal.style.display = "block";

        
        closeRegisterModal.onclick = () => registerModal.style.display = "none";

        
        window.onclick = function(event) {
            if (event.target == registerModal) {
                registerModal.style.display = "none";
            }
        };

        
        function validateEmail() {
            const emailInput = document.getElementById("email").value;
            if (!emailInput.includes("@ustp.edu.ph")) {
                alert("Registration Unable:\nOnly USTP registrar staffs are allowed to access.");
                return false;
            }
            return true;
        }
    </script>
</body>

</html>