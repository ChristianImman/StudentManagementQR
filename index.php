<?php
session_start();
require_once 'assets/php/Database.php';
require_once 'assets/php/User.php';

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    header("Location: assets/dashboard/dashboard.php"); // Corrected path
    exit();
}

// Initialize error message
$error_message = "";

// Create a new database connection
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Check if the login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $password_input = $_POST['password'];

    // Attempt to log in
    if ($user->login($username, $password_input)) {
        $_SESSION['username'] = $username;
        // Clear any existing error message
        unset($_SESSION['error_message']);
        header("Location: assets/dashboard/dashboard.php"); // Corrected path
        exit();
    } else {
        // Store error message in session
        $_SESSION['error_message'] = "Invalid username or password.";
    }
}

// Check if the registration form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    // Get the form data
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $first_name = htmlspecialchars($_POST['fName']);
    $last_name = htmlspecialchars($_POST['lName']);
    $email_address = htmlspecialchars($_POST['email']);
    $date_of_birth = $_POST['yearStarted'];

    // Check if the username already exists
    if ($user->usernameExists($username)) {
        $_SESSION['message'] = "Username already taken. Please choose another.";
        $_SESSION['message_type'] = "error"; // Set message type to error
    } else {
        // Call the addUser  method
        if ($user->addUser ($username, $password, $first_name, $last_name, $email_address, $date_of_birth)) {
            $_SESSION['message'] = "Registration successful!";
            $_SESSION['message_type'] = "success"; // Set message type to success
        } else {
            $_SESSION['message'] = "Registration failed.";
            $_SESSION['message_type'] = "error"; // Set message type to error
        }
    }
}

$database->closeConnection();

// Check if there is an error message in the session
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    // Clear the error message fr   om the session
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/login.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
    <title>Login</title>
</head>
<body>
<div class="container">
        <div class="logo">
            <img src="assets/bg/logo.png" alt="Logo" class="logo">
        </div>
        <div class="wrapper">
            <h1>Login</h1>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form action="index.php" method="post"> <!-- Adjusted action -->
                <div class="input-box">
                    <input type="text" placeholder="Username" name="username" required>
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="Password" name="password" required>
                    <i class='bx bxs-lock-alt'></i>
                </div>
                <div class="remember">
                    <label>
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                </div>
                <button type="submit" class="btn" name="login">Login</button>
                <p class="getqr" style="padding-top: 10px;">
                <a id="qrButton" style="color: #F6B533; background: transparent; text-decoration: none; cursor: pointer;">
                Create Your Qr Code 
                </a>
                <p class="registar" style="padding-top: 10px;">
                Don't have Account? 
                <a id="openRegisterModal" style="color: lightblue; background: transparent; text-decoration: none; cursor: pointer;">
                    Click here to Register
                </a>  
                </p>    
            </form>
        </div>
    </div>

    <!-- Modal Structure Qr Generator -->
    <div id="qrModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>QR Code Generator</h2>
            
            <div class="form-group">
                <div>
                    <label for="studentId">Student ID</label>
                    <input type="text" id="studentId" placeholder="Enter Student ID">
                </div>
                <div>
                    <input type="text" id="firstName" placeholder="First Name" required>
                    <input type="text" id="lastName" placeholder="Last Name" required>
                </div>
            </div>
            
            <div class="form-group">
                <div>
                    <label for="course">Course</label>
                    <input type="text" id="course" placeholder="Enter Course">
                </div>
                <div>
                    <label for="yearStarted">Date of Birth</label>
                    <input type="date" id="yearStarted" placeholder="MM, DD, YYYY">
                </div>
            </div>
            
            <div class="form-group">
                <div class="status-dropdown" style="width: 30%;"> <!-- Set width to 30% -->
                    <label for="status">Status</label>
                    <select id="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <button id="generateQRButton">Generate QR Code</button>
            <div id="qrcode"></div>
            <button id="printQRButton">Print</button>
        </div>
    </div>

    <!-- Button to open the registration modal -->
    <div id="registerModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" id="closeRegisterModal">&times;</span>
            <h2>Registration Form</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <div>
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
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
                        <input type="text" id="email" name="email" required>
                    </div>
                    <div>
                        <label for="yearStarted">Date of Birth:</label>
                        <input type="date" id="yearStarted" name="yearStarted" required>
                    </div>
                </div>

                <input type="submit" name="register" value="Register">
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="assets/js/qr_generator.js"></script>
    <script src="assets/js/qrcode.js"></script>
    <script>
    // Get the modal
    var registerModal = document.getElementById("registerModal");

    // Get the button that opens the modal
    var openRegisterModal = document.getElementById("openRegisterModal");

    // Get the <span> element that closes the modal
    var closeRegisterModal = document.getElementById("closeRegisterModal");

    // When the user clicks the button, open the modal 
    openRegisterModal.onclick = function() {
        registerModal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    closeRegisterModal.onclick = function() {
        registerModal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == registerModal) {
            registerModal.style.display = "none";
        }
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
    </script>
</body>
</html>