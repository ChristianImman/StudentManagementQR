<?php
session_start();
require_once '../php/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo']) && isset($_SESSION['username'])) {
    $db = new Database();
    $conn = $db->getConnection();

    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . "_" . basename($_FILES["photo"]["name"]);
    $targetFile = $targetDir . $fileName; 
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["photo"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
            $username = $_SESSION['username'];
            $sql = "UPDATE admins SET profile_photo = :photo WHERE username = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':photo', $targetFile);
            $stmt->bindParam(':username', $username);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "path" => $targetFile]);
                exit;
            } else {
                echo json_encode(["success" => false, "error" => "Failed to update DB."]);
                exit;
            }
        } else {
            echo json_encode(["success" => false, "error" => "Upload failed."]);
            exit;
        }
    } else {
        echo json_encode(["success" => false, "error" => "File is not an image."]);
        exit;
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request."]);
    exit;
}
?>
