<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php"); 
    exit();
}
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Set your password
$dbname = "demo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data for gender distribution
$maleUsersQuery = $conn->query("SELECT COUNT(*) AS count FROM login WHERE gender = 'male'");
$maleUsers = $maleUsersQuery->fetch_assoc()['count'];

$femaleUsersQuery = $conn->query("SELECT COUNT(*) AS count FROM login WHERE gender = 'female'");
$femaleUsers = $femaleUsersQuery->fetch_assoc()['count'];

$organizationsQuery = $conn->query("SELECT COUNT(*) AS count FROM login WHERE gender = 'organization'");
$organizations = $organizationsQuery->fetch_assoc()['count'];

// Fetch data for giveaways
$totalGiveawaysQuery = $conn->query("SELECT COUNT(*) AS count FROM free_food");
$totalGiveaways = $totalGiveawaysQuery->fetch_assoc()['count'];

$expiredGiveawaysQuery = $conn->query("SELECT COUNT(*) AS count FROM free_food WHERE status = 'expired'");
$expiredGiveaways = $expiredGiveawaysQuery->fetch_assoc()['count'];

$holdedGiveawaysQuery = $conn->query("SELECT COUNT(*) AS count FROM free_food WHERE status = 'holded'");
$holdedGiveaways = $holdedGiveawaysQuery->fetch_assoc()['count'];

$comGiveawaysQuery = $conn->query("SELECT COUNT(*) AS count FROM free_food WHERE status = 'completed'");
$comGiveaways = $comGiveawaysQuery->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/png" href="people.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f4f9;
            display: flex;
            height: 100vh;
        }
        /* Navigation Bar */
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

        /* Main Content */
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
    </style>
</head>
<body>
    <!-- Vertical Navigation Bar -->
    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <a href="admin.php" style="background: #003f7f;">Dashboard</a>
        <a href="analytics.php">Analytics</a>
        <a href="mngGiveaways.php">Manage GiveAway & Requests</a>
        <a href="manage_user.php">Manage Users</a>
        <a href="mngFeeds.php">Manage Community Feed</a>
        <a href="adminProfile.php">Admin Profile</a>
        <div class="profile">
            
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h1 id="dashboard">Dashboard</h1>
        <div class="card">
            <h3>Total Male Users</h3>
            <p><?= $maleUsers ?></p>
        </div>
        <div class="card">
            <h3>Total Female Users</h3>
            <p><?= $femaleUsers ?></p>
        </div>
        <div class="card">
            <h3>Total Organizations</h3>
            <p><?= $organizations ?></p>
        </div>
        <div class="card">
            <h3>Total GiveAway & Requests</h3>
            <p><?= $totalGiveaways ?></p>
        </div>
        <div class="card">
            <h3>Expired GiveAway & Requests</h3>
            <p><?= $expiredGiveaways ?></p>
        </div>
        <div class="card">
            <h3>holded GiveAway & Requests</h3>
            <p><?= $holdedGiveaways ?></p>
        </div>

        <!-- Charts Section -->
        <div id="analytics" class="chart-container">
            <h2>Users Distribution</h2>
            <canvas id="userChart" width="400" height="300"></canvas> <!-- Adjusted size -->
        </div>
        <div class="chart-container">
            <h2>GiveAway & Requests Status</h2>
            <canvas id="giveawayChart" width="400" height="300"></canvas> <!-- Adjusted size -->
        </div>

    </div>

    <script>
        // User Chart
        const userChart = new Chart(document.getElementById('userChart'), {
            type: 'pie',
            data: {
                labels: ['Male', 'Female', 'Organizations'],
                datasets: [{
                    data: [<?= $maleUsers ?>, <?= $femaleUsers ?>, <?= $organizations ?>],
                    backgroundColor: ['#007bff', '#e83e8c', '#28a745'],
                }]
            },
            options: {
                responsive: false, // Disable auto-resizing for fixed size
                maintainAspectRatio: false, // Ensures the custom size is used
            }
        });

        // Giveaways Chart
        const giveawayChart = new Chart(document.getElementById('giveawayChart'), {
            type: 'bar',
            data: {
                labels: ['Total Giveaways', 'Expired Giveaways', 'Holded Giveaways', 'Completed Giveaways'],
                datasets: [{
                    label: 'Giveaways',
                    data: [<?= $totalGiveaways ?>, <?= $expiredGiveaways ?>, <?= $holdedGiveaways ?>, <?= $comGiveaways ?>],
                    backgroundColor: ['#17a2b8', '#dc3545']
                }]
            },
            options: {
                responsive: false, // Disable auto-resizing for fixed size
                maintainAspectRatio: false, // Ensures the custom size is used
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

    </script>
</body>
</html>
