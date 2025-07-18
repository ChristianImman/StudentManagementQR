<?php


header("Content-Type: application/json");

$host = "localhost";
$dbname = "student_records";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$action = $input["action"] ?? "";
$students = $input["payload"] ?? [];

$results = [];

foreach ($students as $student) {
    $studentId = $student["studentId"] ?? "";
    $name = $student["name"] ?? "";
    $course = $student["course"] ?? "";
    $yearStarted = $student["yearStarted"] ?? "";
    $status = $student["status"] ?? "";
    $qrCode = $student["qrCode"] ?? "";

    if (empty($studentId) || empty($name) || empty($yearStarted)) {
        $results[] = [
            "studentId" => $studentId,
            "status" => "error",
            "message" => "Missing required fields"
        ];
        continue;
    }

    try {
        
        $stmtCheckQR = $pdo->prepare("SELECT COUNT(*) FROM student_qr_codes WHERE studentid = :studentId");
        $stmtCheckQR->execute([":studentId" => $studentId]);
        $existsInQR = $stmtCheckQR->fetchColumn() > 0;

        if ($existsInQR) {
            $results[] = [
                "studentId" => $studentId,
                "status" => "error",
                "message" => "QR code already exists for this student"
            ];
            continue;
        }

        
        if ($action === "upload") {
            $stmtCheckStudents = $pdo->prepare("SELECT COUNT(*) FROM students WHERE studentid = :studentId");
            $stmtCheckStudents->execute([":studentId" => $studentId]);
            $existsInStudents = $stmtCheckStudents->fetchColumn() > 0;

            if (!$existsInStudents) {
                $stmtInsertStudent = $pdo->prepare("
                    INSERT INTO students (studentid, name, course, yearStarted, status)
                    VALUES (:studentId, :name, :course, :yearStarted, :status)
                ");
                $stmtInsertStudent->execute([
                    ":studentId" => $studentId,
                    ":name" => $name,
                    ":course" => $course,
                    ":yearStarted" => $yearStarted,
                    ":status" => $status
                ]);
            }
        }

        
        $stmtInsertQR = $pdo->prepare("
            INSERT INTO student_qr_codes (studentid, qr_code, name, course, yearStarted)
            VALUES (:studentId, :qrCode, :name, :course, :yearStarted)
        ");
        $stmtInsertQR->execute([
            ":studentId" => $studentId,
            ":qrCode" => $qrCode,
            ":name" => $name,
            ":course" => $course,
            ":yearStarted" => $yearStarted
        ]);

        $results[] = [
            "studentId" => $studentId,
            "status" => "success",
            "message" => "Studenet ID Inserted successfully"
        ];

    } catch (Exception $e) {
        $results[] = [
            "studentId" => $studentId,
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}

echo json_encode($results);