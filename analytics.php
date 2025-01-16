<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Replace with your password
$dbname = "demo";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data for user types
$user_type_query = "SELECT gender, COUNT(*) AS count FROM login GROUP BY gender";
$user_type_result = $conn->query($user_type_query);
$user_type_data = [];
while ($row = $user_type_result->fetch_assoc()) {
    $user_type_data[$row['gender']] = $row['count'];
}

// Fetch data for free_food statuses
$free_food_status_query = "SELECT status, COUNT(*) AS count FROM free_food GROUP BY status";
$free_food_status_result = $conn->query($free_food_status_query);
$free_food_status_data = [];
while ($row = $free_food_status_result->fetch_assoc()) {
    $free_food_status_data[$row['status']] = $row['count'];
}

// Fetch data for free food give-away trends
$giveaway_query = "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM free_food GROUP BY DATE(created_at)";
$giveaway_result = $conn->query($giveaway_query);
$giveaway_dates = [];
$giveaway_counts = [];
while ($row = $giveaway_result->fetch_assoc()) {
    $giveaway_dates[] = $row['date'];
    $giveaway_counts[] = $row['count'];
}
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
        h1 {
            text-align: center;
            color: #333;
        }
        .chart-container {
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
        }
        canvas {
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- Vertical Navigation Bar -->
    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <a href="admin.php">Dashboard</a>
        <a href="analytics.php" style="background: #003f7f;">Analytics</a>
        <a href="mngGiveaways.php">Manage GiveAway & Requests</a>
        <a href="manage_user.php">Manage Users</a>
        <a href="mngFeeds.php">Manage Community Feed</a>
        <a href="adminProfile.php">Admin Profile</a>
        <div class="profile">
            
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h1>Admin Analytics</h1>

        <!-- User Types Chart -->
        <div class="chart-container">
            <h2>Users by Category</h2>
            <canvas id="userTypeChart"></canvas>
        </div>

        <!-- Free Food Status Pie Chart -->
        <div class="chart-container">
            <h2>GiveAway & Requests Status</h2>
            <canvas id="foodStatusChart"></canvas>
        </div>

        <!-- Free Food Giveaway Trends -->
        <div class="chart-container">
            <h2>GiveAway & Requests Over Time</h2>
            <canvas id="giveawayTrendChart"></canvas>
        </div>

    </div>

    <script>
        // Data from PHP
        const userTypeData = <?php echo json_encode($user_type_data); ?>;
        const foodStatusData = <?php echo json_encode($free_food_status_data); ?>;
        const giveawayDates = <?php echo json_encode($giveaway_dates); ?>;
        const giveawayCounts = <?php echo json_encode($giveaway_counts); ?>;

        // User Types Chart
        const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
        new Chart(userTypeCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(userTypeData),
                datasets: [{
                    label: 'Number of Users',
                    data: Object.values(userTypeData),
                    backgroundColor: ['#007BFF', '#FF6384', '#36A2EB'],
                    borderColor: ['#0056b3', '#ff4f7a', '#2d87d9'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Free Food Status Pie Chart
        const foodStatusCtx = document.getElementById('foodStatusChart').getContext('2d');
        new Chart(foodStatusCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(foodStatusData),
                datasets: [{
                    data: Object.values(foodStatusData),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                }
            }
        });

        // Free Food Giveaway Trends Chart
        const giveawayTrendCtx = document.getElementById('giveawayTrendChart').getContext('2d');
        new Chart(giveawayTrendCtx, {
            type: 'line',
            data: {
                labels: giveawayDates,
                datasets: [{
                    label: 'Giveaways',
                    data: giveawayCounts,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

   
</body>
</html>


