<?php
session_start();
echo "Logout script reached."; // Debugging line
session_destroy();
header("Location: index.php"); // Adjust this path based on your actual structure
exit();
?>