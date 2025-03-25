<?php 
// Database connection
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$dbname = "student_records";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection 
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// Pagination setup
$limit = 6; // Limit entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $limit; // Offset for SQL query

// Count total number of entries
$countResult = $conn->query("SELECT COUNT(*) AS total FROM students");
$totalEntries = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit); // Total pages

// Fetch paginated results
$sql = "SELECT * FROM students LIMIT $limit OFFSET $offset"; 
$result = $conn->query($sql); 
?>

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="student_file.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"> 
    <title>Student File</title> 
</head> 
<body> 
<header> 
    <div class="logo"> 
        <a href="/assets/dashboard/dashboard.php"><img src="/qr/assets/bg/logo.png" alt="logo"></a> 
    </div> 
    <nav> 
        <ul id="menuList"> 
            <li><a href="/qr/assets/dashboard/dashboard.php"><i class="fa-solid fa-house"></i> Home</a></li> 
            <li><a href="/qr/assets/students/student_file.php"><i class="fa-solid fa-file"></i> Student File</a></li> 
            <li><a href="/qr/assets/QrScanner/qr_scanner.php"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li> 
            <li><a href="/qr/assets/Profile/profile.php"><i class="fa-solid fa-user"></i> Profile</a></li> 
            <li><a href="/qr/logout.php"><i class="fa-solid fa-key"></i> Logout</a></li> 
        </ul> 
        <div class="menu-icon"> <i class="fa-solid fa-bars" onclick="toggleMenu()"></i> </div> 
    </nav> 
</header> 

<section class="p-3"> 
    <div class="row"> 
        <div class="col-12"> 
            <table> 
                <thead class="heading"> 
                    <tr> 
                        <th>Student ID</th> 
                        <th>Name</th> 
                        <th>Course</th> 
                        <th>Status</th> 
                        <th>Year Started</th> 
                    </tr> 
                </thead> 
                <tbody class="data"> 
                    <?php while ($row = $result->fetch_assoc()) : ?> 
                        <tr> 
                            <td><?= htmlspecialchars($row['studentid']) ?></td> 
                            <td><?= htmlspecialchars($row['name']) ?></td> 
                            <td><?= htmlspecialchars($row['course']) ?></td> 
                            <td> 
                                <div>
                                    <?= htmlspecialchars($row['status']) ?>
                                </div> 
                            </td> 
                            <td> 
                                <?php 
                                $date = DateTime::createFromFormat('Y-m-d', $row['yearStarted']); 
                                echo $date ? $date->format('F j, Y') : htmlspecialchars($row['yearStarted']); 
                                ?> 
                            </td> 
                        </tr> 
                    <?php endwhile; ?> 
                </tbody> 
            </table> 

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($totalPages > 0): ?>
                    <p class="entry-info">Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalEntries) ?> of <?= $totalEntries ?> entries</p>
                    <nav>
                        <ul>
                            <li><a href="?page=1">&laquo;</a></li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li><a href="?page=<?= $i ?>" <?= ($i == $page) ? 'class="active"' : '' ?>><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li><a href="?page=<?= $totalPages ?>">&raquo;</a></li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div> 
    </div> 
</section> 

</body> 
</html> 
<?php $conn->close(); ?>