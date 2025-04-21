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

$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : "";
$searching = !empty($searchTerm);

$stmt = null;
if ($searching) {
    $stmt = $conn->prepare("
        SELECT s.* 
        FROM students s
        WHERE s.name LIKE CONCAT('%', ?, '%') OR s.studentid LIKE CONCAT('%', ?, '%')
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM students LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($searching) {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM students s 
        WHERE s.name LIKE CONCAT('%', ?, '%') OR s.studentid LIKE CONCAT('%', ?, '%')");
    $countStmt->bind_param("ss", $searchTerm, $searchTerm);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalEntries = $countResult->fetch_assoc()['total'];
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students");
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalEntries = $countResult->fetch_assoc()['total'];
}

$totalPages = ceil($totalEntries / $limit);
$countStmt->close();
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
            <div class="menu-icon">
                <i class="fa-solid fa-bars" onclick="toggleMenu()"></i>
            </div>
        </nav>
    </header>

    <section class="p-3">
        <div class="row">
            <div class="col-12">
                <?php include 'search_form.php'; ?>

                <table>
                    <thead class="heading">
                        <tr>
                            <th>Date Logged</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Started</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="data">
                        <?php while ($student = $result->fetch_assoc()): ?>
                            <?php
                            $logStmt = $conn->prepare("SELECT * FROM logs WHERE studentid = ? ORDER BY date_logged DESC");
                            $logStmt->bind_param("s", $student['studentid']);
                            $logStmt->execute();
                            $logResult = $logStmt->get_result();

                            if ($searching && $logResult->num_rows > 0) {
                                while ($log = $logResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= formatDateLogged($log['date_logged']) ?></td>
                                        <td><?= htmlspecialchars($student['studentid']) ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td><?= htmlspecialchars($student['course']) ?></td>
                                        <td><?= htmlspecialchars($student['yearStarted']) ?></td>
                                        <td><?= htmlspecialchars($log['status']) ?></td>
                                    </tr>
                                <?php endwhile;
                            } else {
                                $log = $logResult->fetch_assoc(); ?>
                                <tr>
                                    <td><?= isset($log['date_logged']) ? formatDateLogged($log['date_logged']) : '-' ?></td>
                                    <td><?= htmlspecialchars($student['studentid']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td><?= htmlspecialchars($student['course']) ?></td>
                                    <td><?= htmlspecialchars($student['yearStarted']) ?></td>
                                    <td><?= isset($log['status']) ? htmlspecialchars($log['status']) : '-' ?></td>
                                </tr>
                            <?php }
                            $logStmt->close(); ?>
                        <?php endwhile; ?>
                    </tbody>

                </table>

                <div class="pagination">
                    <?php if ($totalPages > 0): ?>
                        <p class="entry-info">
                            Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalEntries) ?> of <?= $totalEntries ?> entries
                        </p>
                        <nav>
                            <ul>
                                <li><a href="?page=1&search=<?= urlencode($searchTerm) ?>">&laquo;</a></li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li><a href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>" <?= ($i == $page) ? 'class="active"' : '' ?>><?= $i ?></a></li>
                                <?php endfor; ?>
                                <li><a href="?page=<?= $totalPages ?>&search=<?= urlencode($searchTerm) ?>">&raquo;</a></li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        function fetchSuggestions(query) {
            clearTimeout(window.debounceTimer);

            window.debounceTimer = setTimeout(() => {
                if (query.length < 3) {
                    document.getElementById('suggestions').style.display = 'none';
                    return;
                }

                fetch('fetch_suggestions.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        const suggestionsBox = document.getElementById('suggestions');
                        suggestionsBox.innerHTML = '';
                        if (data.length > 0) {
                            suggestionsBox.style.display = 'block';
                            data.forEach(item => {
                                const li = document.createElement('li');
                                li.textContent = `${item.studentid} - ${item.name}`;
                                li.onclick = function() {
                                    document.getElementById('search').value = item.studentid;
                                    suggestionsBox.style.display = 'none';
                                    document.querySelector('form').submit();
                                };
                                suggestionsBox.appendChild(li);
                            });
                        } else {
                            suggestionsBox.style.display = 'none';
                        }
                    })
                    .catch(error => console.error("Error fetching suggestions:", error));
            }, 100);
        }

        function clearSearchInput() {
            const searchInput = document.getElementById('search');
            const clearIcon = document.getElementById('clear-search');

            searchInput.value = "";
            clearIcon.style.display = 'none';
            fetchSuggestions('');

            const baseUrl = window.location.pathname;
            window.location.href = baseUrl;
        }

        function toggleMenu() {
            const menuList = document.getElementById('menuList');
            menuList.classList.toggle('show');
        }

        window.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search');
            const clearIcon = document.getElementById('clear-search');

            if (searchInput.value.trim() !== "") {
                clearIcon.style.display = 'inline';
            }

            searchInput.addEventListener('input', function() {
                clearIcon.style.display = searchInput.value.trim() !== "" ? 'inline' : 'none';
            });

            clearIcon.addEventListener('click', function() {
                clearSearchInput();
            });

            document.addEventListener('click', function(e) {
                const suggestionsBox = document.getElementById('suggestions');
                if (!document.getElementById('search').contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>

<?php
if ($stmt !== null) {
    $stmt->close();
}
$conn->close();
?>