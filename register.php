<?php
// Include the necessary files
include_once __DIR__ . '/assets/php/Database.php'; // Use __DIR__ to get the current directory
include_once __DIR__ . '/assets/php/User.php'; // Use __DIR__ to get the current directory

// Create a new database connection
$db = new Database();
$user = new User($db->getConnection());

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Call the addUser  method to add the new user
    if ($user->addUser ($username, password: $password)) {
        echo "User  added successfully!";
    } else {
        echo "Failed to add user.";
    }
}
?>

<!-- HTML Form for User Registration -->
<form method="POST" action="">
    <label for="username">Username:</label>
    <input type="text" name="username" required>
    
    <label for="password">Password:</label>
    <input type="password" name="password" required>
    
    <label for="fName">First Name</label>
    <input type="text" name="fName" required>

    <label for="lName">Last Name</label>
    <input type="text" name="FName" required>

    <label for="email">Email Address</label>
    <input type="text" name="email" required>

    <label for="yearStarted">Date of Birth</label>
    <input type="date" id="yearStarted" placeholder="MM, DD, YYYY">

    <input type="submit" value="Register">
</form>