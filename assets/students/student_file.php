<?php
session_start();
require_once '../php/Database.php';

if (!isset($_SESSION['username'])) {
    header("Location: /qr/index.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_records";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function formatDateLogged($dateString)
{
    $dateTime = new DateTime($dateString);
    return $dateTime->format('m/d/Y h:i A');
}

$device = isset($_GET['device']) ? $_GET['device'] : '';

switch ($device) {
    case 'tablet':
        $limit = 12;
        break;
    case 'mobile':
        $limit = 8;
        break;
    default:
        $limit = 8;
        break;
    case 'laptop':
        $limit = 8;
        break;
    case 'desktop':
        $limit = 8;
}


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : "";
$searching = !empty($searchTerm);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_logged';  
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';  

$stmt = null;

if ($searching) {
    
    $stmt = $conn->prepare("
        SELECT l.*, s.name, s.course, s.yearStarted
        FROM logs l
        JOIN students s ON l.studentid = s.studentid
        WHERE s.name LIKE CONCAT('%', ?, '%') OR l.studentid LIKE CONCAT('%', ?, '%')
        ORDER BY $sort $order
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM logs l
        JOIN students s ON l.studentid = s.studentid
        WHERE s.name LIKE CONCAT('%', ?, '%') OR l.studentid LIKE CONCAT('%', ?, '%')
    ");
    $countStmt->bind_param("ss", $searchTerm, $searchTerm);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalEntries = $countResult->fetch_assoc()['total'];
    $countStmt->close();
} else {
    
    $stmt = $conn->prepare("
        SELECT * FROM students
        ORDER BY $sort $order
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students");
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalEntries = $countResult->fetch_assoc()['total'];
    $countStmt->close();
}

$totalPages = ceil($totalEntries / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student File</title>
    <link rel="stylesheet" href="student_file.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>

<body>
    <header>
        <div class="logo">
            <a href="/qr/assets/dashboard/dashboard.php"><img src="/qr/assets/bg/logo.png" alt="logo"></a>
        </div>
        <nav>
            <ul id="menuList">
                <li><a href="/qr/assets/dashboard/dashboard.php"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="/qr/assets/students/student_file.php"><i class="fa-solid fa-file"></i> Student Logs</a></li>
                <li><a href="/qr/assets/QrScanner/qr_scanner.php"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li>
                <li><a href="/qr/assets/QrGenerator/qr_generator.php"><i class="fa-solid fa-qrcode"></i> QR Generator</a></li>
                <li><a href="/qr/assets/Profile/profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="/qr/logout.php"><i class="fa-solid fa-key"></i> Logout</a></li>
            </ul>
            <div class="menu-icon" onclick="toggleMenu()">
                <i class="fa-solid fa-bars"></i>
            </div>
        </nav>
    </header>

    <div class="search-wrapper sticky-top">
        <?php include 'search_form.php'; ?>
    </div>

    <section class="p-3">
        <div class="row">
            <table>
                <thead class="heading">
                    <tr>
                        <?php
                        $columns = [
                            'date_logged' => 'Date Logged',
                            'studentid' => 'Student ID',
                            'name' => 'Name',
                            'course' => 'Course',
                            'yearStarted' => 'Year Started',
                            'status' => 'Status'
                        ];

                        foreach ($columns as $col => $label):
                            $nextOrder = ($sort == $col && $order == 'asc') ? 'desc' : 'asc';
                            $query = "?sort=$col&order=$nextOrder&search=" . urlencode($searchTerm) . "&page=$page";
                        ?>
                            <th>
                                <a href="<?= $query ?>">
                                    <?= $label ?>
                                    <i class="fa-solid fa-sort <?= ($sort == $col) ? ($order == 'asc' ? 'asc' : 'desc') : '' ?>"></i>
                                </a>

                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="data">
                    <?php if ($searching): ?>
                        <?php while ($log = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= formatDateLogged($log['date_logged']) ?></td>
                                <td><?= htmlspecialchars($log['studentid']) ?></td>
                                <td><?= htmlspecialchars($log['name']) ?></td>
                                <td><?= htmlspecialchars($log['course']) ?></td>
                                <td><?= htmlspecialchars($log['yearStarted']) ?></td>
                                <td><?= htmlspecialchars($log['status']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <?php while ($student = $result->fetch_assoc()): ?>
                            <?php
                            $logStmt = $conn->prepare("SELECT * FROM students WHERE studentid = ? ORDER BY date_logged DESC LIMIT 1");
                            $logStmt->bind_param("s", $student['studentid']);
                            $logStmt->execute();
                            $logResult = $logStmt->get_result();
                            $log = $logResult->fetch_assoc();
                            $logStmt->close();
                            ?>
                            <tr>
                                <td><?= isset($log['date_logged']) ? formatDateLogged($log['date_logged']) : '-' ?></td>
                                <td><?= htmlspecialchars($student['studentid']) ?></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['course']) ?></td>
                                <td><?= htmlspecialchars($student['yearStarted']) ?></td>
                                <td><?= isset($log['status']) ? htmlspecialchars($log['status']) : '-' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>





    <?php if ($totalPages > 0): ?>
        <div class="table-controls sticky-bottom">
            <p class="entry-info">
                Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalEntries) ?> of <?= $totalEntries ?> entries
            </p>

            <div class="pagination-bar">
                <div class="pagination-scroll">
                    <nav>
                        <?php
                        require_once 'pagination.php';
                        $queryStr = http_build_query([
                            'search' => $searchTerm,
                            'sort' => $sort,
                            'order' => $order
                        ]);
                        echo renderPagination($page, $totalPages, $queryStr, 20);
                        ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>





    <script>
        function toggleMenu() {
            const menuList = document.getElementById("menuList");
            const overlay = document.getElementById("overlay");
            menuList.classList.toggle("show");
            overlay.classList.toggle("active");
        }

        function updatePaginationDisplay() {
            const paginationUl = document.querySelector('.pagination nav ul');
            if (!paginationUl) return;

            if (window.innerWidth <= 760) {
                paginationUl.classList.add('pagination-minimal');
            } else {
                paginationUl.classList.remove('pagination-minimal');
            }
        }

        function jumpToPage(e) {
            e.preventDefault();
            const input = prompt("Jump to page number:");
            const page = parseInt(input);
            if (!isNaN(page) && page > 0) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            }
        }

        (function() {
            const isTabletPortrait =
                window.innerWidth >= 768 &&
                window.innerWidth <= 834 &&
                window.innerHeight >= 1024 &&
                window.innerHeight <= 1280 &&
                window.matchMedia("(orientation: portrait)").matches;

            const url = new URL(window.location.href);

            
            if (isTabletPortrait && url.searchParams.get("device") !== "tablet") {
                url.searchParams.set("device", "tablet");

                if (isTabletPortrait && url.searchParams.get("device") !== "tablet") {
                    url.searchParams.set("device", "desktop");
                }

                if (isTabletPortrait && url.searchParams.get("device") !== "tablet") {
                    url.searchParams.set("device", "laptop");
                }
                
                window.location.replace(url.toString());
            }
        })();
    </script>
</body>

</html>