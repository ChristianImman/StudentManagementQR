
<?php



$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);


if ($data) {
    
    

    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=your_database', 'your_username', 'your_password');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        
        foreach ($data as $student) {
            
            $stmt = $pdo->prepare("INSERT INTO students (studentid, name, course, yearStarted, status) 
            VALUES (:studentid, :name, :course, :yearStarted, :status)");

            
            $stmt->execute([
                ':studentid' => $student['Student ID'],
                ':name' => $student['Name'],
                ':course' => $student['Course'],
                ':yearStarted' => $student['Year Started'],
                ':status' => $student['Status'] ?? 'active', 
            ]);
        }

        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    
    echo json_encode(['success' => false, 'message' => 'No data received']);
}
?>
