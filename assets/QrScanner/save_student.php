<?php  
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/qr/assets/php/Database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";  
$pass = "";      
$dbname = "student_records";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->query("SET time_zone = '+08:00'");

header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

if (!isset($_POST['studentid'], $_POST['name'], $_POST['course'], $_POST['status'], $_POST['yearStarted'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing fields: Student ID, Name, Course, Status, Year Started'
    ]);
    exit;
}

$studentid = $conn->real_escape_string(trim($_POST['studentid']));
$name = $conn->real_escape_string(trim($_POST['name']));
$course = $conn->real_escape_string(trim($_POST['course']));
$status = $conn->real_escape_string(trim($_POST['status']));
$yearStarted = (int)trim($_POST['yearStarted']);

if (!is_numeric($yearStarted) || $yearStarted < 1900 || $yearStarted > date("Y")) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Year Started. Please enter a valid year.'
    ]);
    exit;
}

$getStudentQuery = "SELECT * FROM students WHERE studentid = ?";
$stmt = $conn->prepare($getStudentQuery);
$stmt->bind_param("s", $studentid);
$stmt->execute();
$result = $stmt->get_result();
$existingData = $result->fetch_assoc();
$stmt->close();

$changes = [];

if ($existingData) {
    if ($course !== $existingData['course']) {
        $changes[] = true;
    }
    if ($status !== $existingData['status']) {
        $changes[] = true;
    }
    if ($yearStarted !== (int)$existingData['yearStarted']) {
        $changes[] = true;
    }
    if ($name !== $existingData['name']) {
        $changes[] = true;
    }

    $sql = "UPDATE students SET course = ?, status = ?, name = ?, yearStarted = ? WHERE studentid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $course, $status, $name, $yearStarted, $studentid);
    $stmt->execute();
    $stmt->close();
} else {
    $sql = "INSERT INTO students (studentid, name, course, status, yearStarted) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $studentid, $name, $course, $status, $yearStarted);
    $stmt->execute();
    $stmt->close();

    $changes[] = true;
}

if (!empty($changes)) {
    $logSql = "INSERT INTO logs (studentid, name, course, yearStarted, status) VALUES (?, ?, ?, ?, ?)";
    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param("sssis", $studentid, $name, $course, $yearStarted, $status);
    $logStmt->execute();
    $logStmt->close();
}

$studentInfo = null;
$fetchStudentInfo = $conn->prepare("SELECT date_logged, status FROM students WHERE studentid = ?");
$fetchStudentInfo->bind_param("s", $studentid);
$fetchStudentInfo->execute();
$studentResult = $fetchStudentInfo->get_result();
$studentInfo = $studentResult->fetch_assoc();
$fetchStudentInfo->close();

$latestLog = null;
$fetchLatestLog = $conn->prepare("SELECT date_logged, status FROM logs WHERE studentid = ? ORDER BY date_logged DESC LIMIT 1");
$fetchLatestLog->bind_param("s", $studentid);
$fetchLatestLog->execute();
$logResult = $fetchLatestLog->get_result();
$latestLog = $logResult->fetch_assoc();
$fetchLatestLog->close();


$response = [
    'status' => 'success',
    'message' => 'Student data saved successfully.',
    'student' => [
        'studentid' => $studentid,
        'name' => $name
    ]
];


if ($latestLog && ($latestLog['date_logged'] !== $studentInfo['date_logged'] || $latestLog['status'] !== $studentInfo['status'])) {
    $response['latest_log'] = $latestLog;
}


if (headers_sent() === false) {
    echo json_encode($response);
}

$conn->close();
exit;
?>
