<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";  // Change if needed
$pass = "";      // Change if needed
$dbname = "student_records";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get JSON data from request
$data = json_decode(file_get_contents("php://input"), true);

// Debugging: Print received data
file_put_contents("debug_log.txt", print_r($data, true)); // Logs data to debug_log.txt

if (isset($data["studentid"], $data["name"], $data["course"], $data["status"], $data["yearStarted"])) {
    $studentid = $conn->real_escape_string($data["studentid"]);
    $name = $conn->real_escape_string($data["name"]);
    $course = $conn->real_escape_string($data["course"]);
    $status = $conn->real_escape_string($data["status"]);
    $yearStarted = $conn->real_escape_string($data["yearStarted"]);

    // Debug: Print SQL query before execution
    $sql = "INSERT INTO students (studentid, name, course, status, yearStarted) 
            VALUES ('$studentid', '$name', '$course', '$status', '$yearStarted')";
    file_put_contents("debug_log.txt", "\nSQL: $sql", FILE_APPEND);

    if ($conn->query($sql) === TRUE) {
        echo "success";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Invalid data received";
}

$conn->close();
?>
