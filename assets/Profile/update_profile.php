<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once $_SERVER['DOCUMENT_ROOT'] . '/qr/assets/php/Database.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/qr/assets/php/User.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user = new User($conn);

$data = json_decode(file_get_contents("php://input"), true);
$username = $_SESSION['username'];
$first_name = $data['first_name'] ?? null;
$last_name = $data['last_name'] ?? null;
$email_address = $data['email_address'] ?? null;
$password = $data['password'] ?? null;

if (!$username || !$email_address || !$first_name || !$last_name) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}


$result = $user->updateUserByUsername($username, $email_address, $first_name, $last_name, $password);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error updating profile']);
}
?>
