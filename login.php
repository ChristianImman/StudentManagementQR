<?php
session_start();
require_once 'assets/php/Database.php';
require_once 'assets/php/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password_input = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false; 

    
    if ($remember) {
        setcookie("remember_username", $username, time() + (86400 * 7), "/");
    } else {
        setcookie("remember_username", "", time() - 3600, "/");
    }

    
    if (!$user->usernameExists($username)) {
        $_SESSION['error_message'] = "Registration Unable: Only USTP registrar staff are allowed to access.";
        header("Location: index.php");
        exit();
    } else {
        
        if ($user->login($username, $password_input)) {
            $_SESSION['username'] = $username;  
            header("Location: assets/dashboard/dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Incorrect Username or Password.";
            header("Location: index.php");
            exit();
        }
    }
}
?>
