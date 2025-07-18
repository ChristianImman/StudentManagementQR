<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_records";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([]); 
    exit();
}

$sql = "SELECT studentid, name FROM students
        WHERE name LIKE CONCAT('%', ?, '%') 
        OR studentid LIKE CONCAT('%', ?, '%')
        LIMIT 6";  

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $query, $query);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}

echo json_encode($suggestions);

$stmt->close();
$conn->close();
?>
