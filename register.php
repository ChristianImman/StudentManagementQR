<?php
session_start();
require_once 'assets/php/Database.php';
require_once 'assets/php/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = htmlspecialchars($_POST['regUsername']);
    $password = $_POST['regPassword'];
    $first_name = htmlspecialchars($_POST['fName']);
    $last_name = htmlspecialchars($_POST['lName']);
    $email_address = htmlspecialchars(trim($_POST['email']));
    $date_of_birth = $_POST['yearStarted'];
    $registrar_id = (int) $_POST['registrar_id']; 

    
    var_dump($registrar_id); 

    
    if ($registrar_id <= 0) {
        $_SESSION['message'] = "Invalid Registrar ID.";
        $_SESSION['message_type'] = "error";
        header("Location: index.php");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    
    if (strpos($email_address, '@ustp.edu.ph') === false) {
        $_SESSION['message'] = "Registration Unable: Only USTP registrar staff are allowed to access.";
        $_SESSION['message_type'] = "error";
    } else {
        
        if ($user->usernameExists($username)) {
            $_SESSION['message'] = "Username already taken. Please choose another.";
            $_SESSION['message_type'] = "error";
        } else {
            
            if ($user->addUser($username, $password, $first_name, $last_name, $email_address, $date_of_birth, $registrar_id)) {
                $_SESSION['message'] = "Registration successful!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Registration failed. Please try again.";
                $_SESSION['message_type'] = "error";
            }
        }
    }

    
    header("Location: index.php");
    exit();
}
?>
