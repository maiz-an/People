<?php
// Database connection
$host = 'localhost';
$db = 'demo';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $action = $_POST['action']; // "reject" or "restore"

    if ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE free_food SET status = 'rejected' WHERE id = :id");
    } elseif ($action === 'restore') {
        $stmt = $pdo->prepare("UPDATE free_food SET status = 'normal' WHERE id = :id");
    }
    $stmt->execute(['id' => $id]);
}



// Fetch data for charts
$maleUsers = $pdo->query("SELECT COUNT(*) AS count FROM login WHERE gender = 'male'")->fetch()['count'];
$femaleUsers = $pdo->query("SELECT COUNT(*) AS count FROM login WHERE gender = 'female'")->fetch()['count'];
$organizations = $pdo->query("SELECT COUNT(*) AS count FROM login WHERE gender = 'organization'")->fetch()['count'];

$totalGiveaways = $pdo->query("SELECT COUNT(*) AS count FROM free_food")->fetch()['count'];
$expiredGiveaways = $pdo->query("SELECT COUNT(*) AS count FROM free_food WHERE status = 'expired'")->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Admin Dashboard - Manage GiveAway & Requests</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f4f9;
            display: flex;
            height: 100vh;
        }
        .navbar {
            width: 250px;
            background: #007bff;
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            height: 100%;
        }
        .navbar a {
            text-decoration: none;
            color: #fff;
            padding: 10px;
            margin: 5px 0;
            background: #0056b3;
            border-radius: 5px;
            text-align: center;
        }
        .navbar a:hover {
            background: #003f7f;
        }
        .navbar .profile {
            margin-top: auto;
            text-align: center;
        }
        .navbar .profile img {
            border-radius: 50%;
            width: 80px;
            height: 80px;
        }

        .main {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
        }
        .card {
            background: #fff;
            padding: 20px;
            margin: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: inline-block;
            width: 250px;
            text-align: center;
        }
        .card h3 {
            margin: 0;
            color: #555;
        }
        .chart-container {
            margin: 20px 0;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .container {
            width: 90%;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .search-input {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Add a slight shadow */
    border-radius: 8px; /* Smooth rounded corners */
        }
        table th, table td {
            text-align: left;
            padding: 12px;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-edit {
            background-color: #28a745;
            color: #fff;
        }
        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }
        .btn-status {
            background-color: #ffc107;
            color: #fff;
        }

        /* Tooltip Styling */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 5px 0;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        .btn-v {
            text-decoration: none;
            display: inline-block;
            width: 50px;
            height: 50px;
            background: url('SiteIcons/file.png') no-repeat center center;
            background-size: cover;
            color: transparent;
            border: none;
            overflow: hidden;
            position: relative;
        }

        .btn-v span {
            position: absolute;
            clip: rect(0, 0, 0, 0);
            width: 1px;
            height: 1px;
            margin: -1px;
            overflow: hidden;
        }

    </style>
</head>
<body>
    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <a href="admin.php">Dashboard</a>
        <a href="analytics.php">Analytics</a>
        <a href="mngGiveaways.php" style="background: #003f7f;">Manage GiveAway & Requests</a>
        <a href="manage_user.php">Manage Users</a>
        <a href="mngFeeds.php">Manage Community Feed</a>
        <a href="adminProfile.php">Admin Profile</a>
        <div class="profile">
            
        </div>
    </div>

    <div class="main">
        <h1>Manage GiveAway & Requests</h1>
        <div class="container">
            <div class="search-container">
                <input
                    type="text"
                    id="searchInput"
                    class="search-input"
                    placeholder="Search by User ID, Post ID, Item Details, or Date"
                    onkeyup="searchTable()"
                />
            </div>
            <table id="giveawayTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Reported Category</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM free_food";
                    $result = $pdo->query($query);

                    while ($row = $result->fetch()) {
                        echo "
                            <tr>
                                <td>{$row['id']}</td>
                                <td>{$row['user_id']}</td>
                                <td>{$row['food_title']}</td>
                                <td>{$row['description']}</td>
                                <td>{$row['quantity']}</td>
                                <td>{$row['report_category']}</td> <!-- Updated column data -->c
                                <td>{$row['status']}</td>
                                <td class='action-buttons'>
                                <form method='POST' action='' style='display: inline;'>
                    <input type='hidden' name='id' value='{$row['id']}' />
                    <input type='hidden' name='action' value='" . ($row['status'] === 'rejected' ? 'restore' : 'reject') . "' />
                    <button class='btn " . ($row['status'] === 'rejected' ? 'btn-status' : 'btn-delete') . "' type='submit' onclick=\"return confirm('Are you sure you want to " . ($row['status'] === 'rejected' ? 'Allow' : 'Reject') . " this item?');\">" . ($row['status'] === 'rejected' ? 'Allow' : 'Reject') . "</button>
                </form>
                                  
                                </td>
                                <td>
                                    <a href='view_item_admin.php?food_id={$row['id']}&user_id={$row['user_id']}' class='btn-v'><span>View Details/span></a>
                                    " . (isset($_SESSION['id']) ? "
                                    <form action='request_handler.php' method='post'>
                                        <input type='hidden' name='food_id' value='{$row['id']}'>
                                        <input type='hidden' name='session_id' value='{$_SESSION['id']}'>
                                        <button type='submit' name='request_item' class='btn'>Request this</button>
                                    </form>
                                    " : "") . "
                                </td>
                            </tr>
                        ";
                    }
                    ?>
                </tbody>
                
            </table>
        </div>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("giveawayTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName("td");
                let matchFound = false;
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const textValue = td[j].textContent || td[j].innerText;
                        if (textValue.toUpperCase().indexOf(filter) > -1) {
                            matchFound = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = matchFound ? "" : "none";
            }
        }
    </script>
</body>
</html>
