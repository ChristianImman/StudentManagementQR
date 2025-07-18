<?php

require '../php/Database.php';
require '../php/db_connect.php';


if ($conn === null) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection not established.']));
}


$data = json_decode(file_get_contents("php://input"), true);


if (!$data || empty($data['qrCode'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or empty student ID received.']);
    exit;
}

$studentId = $data['qrCode'];


$stmt = $conn->prepare("SELECT studentid, name, course, yearStarted, status FROM students WHERE studentid = ?");
$stmt->bind_param("s", $studentId); 
$stmt->execute();
$result = $stmt->get_result();


if ($result && $result->num_rows > 0) {
    $student = $result->fetch_assoc(); 
    echo json_encode([
        'status' => 'success',
        'data' => $student 
    ]);
} else {
    echo json_encode([
        'status' => 'not_found',
        'message' => 'Student not found in the database.' 
    ]);
}


$stmt->close();
$conn->close();
?>
