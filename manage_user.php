<?php
// Include database connection
include 'connection.php';

// PHPMailer Import
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Handle search input
$search_term = '';
if (isset($_POST['search'])) {
    $search_term = mysqli_real_escape_string($connection, $_POST['search_term']);
}

// Build the search query
$search_query = "";
if ($search_term) {
    $search_query = "AND (name LIKE '%$search_term%' OR email LIKE '%$search_term%' OR status LIKE '%$search_term%')";
}

// Handle admin actions (allow/block/delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

// Determine the new status or action
if ($action === 'allow') {
    $status = 'allowed';
} elseif ($action === 'block') {
    $status = 'blocked';
} elseif ($action === 'delete') {
    echo "<script>
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = 'delete_user.php?user_id=$user_id';
        } else {
            window.location.href = 'manage_user.php';
        }
    </script>";
    exit;
} else {
    $status = 'pending';
}


    // Update user status
    $query = "UPDATE login SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'si', $status, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('User status updated successfully!');</script>";

        // Send email notification for `allow` action
        if ($action === 'allow') {
            $email_query = "SELECT email, name, user_type FROM login WHERE id = ?";
            $email_stmt = mysqli_prepare($connection, $email_query);
            mysqli_stmt_bind_param($email_stmt, 'i', $user_id);
            mysqli_stmt_execute($email_stmt);
            $result = mysqli_stmt_get_result($email_stmt);
            $user_data = mysqli_fetch_assoc($result);

            if ($user_data) {
                $to = $user_data['email'];
                $name = $user_data['name'];
                $user_type = ucfirst($user_data['user_type']);

                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'info.people.platfrom@gmail.com';
                    $mail->Password = 'xtvqpbrsbtmnbnhv';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('no-reply@people.com', 'People Platform');
                    $mail->addAddress($to);

                    $message = "<html>
                                    <body>
                                        <p>Dear $name,</p>
                                        <p>We are excited to inform you that your $user_type account has been granted full access to the People Platform. You can now log in and explore all features.</p>
                                        <p>Best Regards,<br>People Team</p>
                                    </body>
                                </html>";
                    $mail->isHTML(true);
                    $mail->Subject = "Access Granted to People Platform";
                    $mail->Body = $message;
                    $mail->send();
                } catch (Exception $e) {
                    echo "<script>alert('Email error: " . $mail->ErrorInfo . "');</script>";
                }
            }
        }
    } else {
        echo "<script>alert('Error updating status: " . mysqli_error($connection) . "');</script>";
    }

    mysqli_stmt_close($stmt);
}

// Fetch users grouped by status
$query_pending = "SELECT * FROM login WHERE status = 'pending' $search_query";
$query_allowed = "SELECT * FROM login WHERE status = 'allowed' $search_query";
$query_blocked = "SELECT * FROM login WHERE status = 'blocked' $search_query";

$result_pending = mysqli_query($connection, $query_pending);
$result_allowed = mysqli_query($connection, $query_allowed);
$result_blocked = mysqli_query($connection, $query_blocked);
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
        .active {
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
            margin-left: 300px;
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
        /* table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons button {
            padding: 5px 10px;
            margin: 2px;
            cursor: pointer;
        }
        .allow {
            background-color: #4CAF50;
            color: white;
        }
        .block {
            background-color: #f44336;
            color: white;
        }
        .delete {
            background-color: #f0ad4e;
            color: white;
        }*/
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        } 

        /* General Table Styling */
table {
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); 
    border-radius: 8px; 
    overflow: hidden;
}

/* Table Headers */
table th {
    background-color: #007bff;
    color: #fff;
    padding: 12px 15px; 
    text-align: left; 
    font-size: 14px; 
    text-transform: uppercase; 
}

/* Table Rows */
table tr {
    border-bottom: 1px solid #ddd; 
}

table tr:last-child {
    border-bottom: none; 
}

/* Table Cells */
table td {
    padding: 12px 15px;
    font-size: 14px; 
    color: #333; 
}

/* Alternating Row Colors */
table tr:nth-child(even) {
    background-color: #f9f9f9; 
}

/* Hover Effect for Rows */
table tr:hover {
    background-color: #f1f1f1; 
}

/* Table Actions Column Styling */
table .action-buttons {
    display: flex; 
    gap: 10px; 
}

table .action-buttons button {
    padding: 5px 10px; 
    font-size: 12px; 
    border-radius: 4px; 
    cursor: pointer; 
    transition: background-color 0.3s ease; 
}

/* Specific Action Button Colors */
table .btn-edit {
    background-color: #28a745; 
    color: #fff;
}

table .btn-edit:hover {
    background-color: #218838; 
}

table .btn-delete {
    background-color: #dc3545; 
    color: #fff;
}

table .btn-delete:hover {
    background-color: #c82333; 
}

        .view-details {
            text-decoration: none;
            padding: 5px 10px;
            background-color: #2196F3;
            color: white;
            border-radius: 5px;
            margin-left: 5px;
        }
        
        .filter-btn {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .filter-btn:hover {
            background-color: #0056b3;
        }
        .search-bar {
    background-color: #fff; 
    padding: 15px 20px; 
    border-radius: 8px; 
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px; 
}

.search-bar form {
    display: flex;
    align-items: center;
    gap: 10px; 
    flex-wrap: wrap; 
}

.search-bar input[type="text"] {
    flex: 1; 
    padding: 10px 15px; 
    border: 1px solid #ccc; 
    border-radius: 4px; 
    font-size: 14px; 
    transition: border-color 0.3s ease;
}

.search-bar input[type="text"]:focus {
    border-color: #007bff;
    outline: none; 
}

.filter-btn {
    background-color: #28a745; 
    color: #fff; 
    border: none;
    padding: 8px 12px; 
    border-radius: 4px; 
    cursor: pointer; 
    font-size: 13px; 
    transition: background-color 0.3s ease, transform 0.2s ease;
}

/* Hover State */
.filter-btn:hover {
    background-color: #218838;
}

/* Active State */
.filter-btn:active {
    background-color: #1e7e34; 
    transform: scale(0.95); 
}

/* Specific Buttons with Different Colors */
.filter-btn.search-btn {
    background-color: #007bff; 
}

.filter-btn.search-btn:hover {
    background-color: #0056b3; 
}

.filter-btn.regular-btn {
    background-color: #ffc107; 
}

.filter-btn.regular-btn:hover {
    background-color: #e0a800; 
}

.filter-btn.organization-btn {
    background-color: #17a2b8; 
}

.filter-btn.organization-btn:hover {
    background-color: #138496; 
}

.filter-btn.allowed-btn {
    background-color: #6c757d; 
}

.filter-btn.allowed-btn:hover {
    background-color: #5a6268; 
}

.filter-btn.blocked-btn {
    background-color: #dc3545; 
}

.filter-btn.blocked-btn:hover {
    background-color: #c82333; 
}


@media (max-width: 768px) {
    .search-bar form {
        flex-direction: column; 
        align-items: stretch; 
    }

    .filter-btn {
        width: 100%;
    }
}

    </style>
</head>
<body>
    <!-- Vertical Navigation Bar -->
    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <a href="admin.php">Dashboard</a>
        <a href="analytics.php">Analytics</a>
        <a href="mngGiveaways.php">Manage Giveaways</a>
        <a href="manage_user.php" style="background: #003f7f;">Manage Users</a>
        <a href="mngFeeds.php" >Manage Community Feed</a>
        <a href="adminProfile.php">Admin Profile</a>
        <div class="profile">
            
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
    <div class="header">
        <h1>Manage Users</h1>
    </div>

    <!-- Search Bar with Filter Buttons -->
    <div class="search-bar">
    <form action="" method="POST" style="display: flex; align-items: center; gap: 10px;">
        <input type="text" name="search_term" placeholder="Search by Name, Email, or Status" value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" name="search" class="filter-btn search-btn">Search</button>
        <button type="submit" name="filter_regular" class="filter-btn regular-btn">Show Regular Users</button>
        <button type="submit" name="filter_organization" class="filter-btn organization-btn">Show Organization Users</button>
        <button type="submit" name="filter_allowed" class="filter-btn allowed-btn">Show Allowed Users</button>
        <button type="submit" name="filter_blocked" class="filter-btn blocked-btn">Show Blocked Users</button>
    </form>
</div>


    <?php
    // Apply user type or status filters
    if (isset($_POST['filter_regular'])) {
        $search_query .= " AND user_type = 'regular'";
    } elseif (isset($_POST['filter_organization'])) {
        $search_query .= " AND user_type = 'organization'";
    } elseif (isset($_POST['filter_allowed'])) {
        $search_query .= " AND status = 'allowed'";
    } elseif (isset($_POST['filter_blocked'])) {
        $search_query .= " AND status = 'blocked'";
    }

    // Function to render user tables
    function renderTable($result, $title) {
        echo "<h2>$title</h2>";
        if (mysqli_num_rows($result) > 0) {
            echo "<table>
                <tr>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                $profile_pic = $row['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($row['profile_pic']) : 'default.png';
                echo "<tr>
                    <td><img src='$profile_pic' alt='Profile Picture' class='profile-pic'></td>
                    <td>{$row['name']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['user_type']}</td>
                    <td>{$row['status']}</td>
                    <td class='action-buttons'>
                        <form action='' method='post' style='display:inline-block;'>
                            <input type='hidden' name='user_id' value='{$row['id']}'>";
                            
                            // Show "Allow" button only if the user is not allowed
                            if ($row['status'] !== 'allowed') {
                                echo "<button type='submit' name='action' value='allow' class='allow'>Allow</button>";
                            }
                            
                            // Show "Block" button only if the user is not blocked
                            if ($row['status'] !== 'blocked') {
                                echo "<button type='submit' name='action' value='block' class='block'>Block</button>";
                            }
                            
                            // Always show the "Delete" button
                            echo "<button type='submit' name='action' value='delete' class='delete'>Delete</button>
                        </form>
                        <a href='view_user_details.php?user_id={$row['id']}' class='view-details'>View Details</a>
                    </td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No users found.</p>";
        }
    }
    

    // Execute queries and render tables
    $query_pending = "SELECT * FROM login WHERE status = 'pending' $search_query";
    $query_allowed = "SELECT * FROM login WHERE status = 'allowed' $search_query";
    $query_blocked = "SELECT * FROM login WHERE status = 'blocked' $search_query";

    $result_pending = mysqli_query($connection, $query_pending);
    $result_allowed = mysqli_query($connection, $query_allowed);
    $result_blocked = mysqli_query($connection, $query_blocked);

    renderTable($result_pending, "Pending Users");
    renderTable($result_allowed, "Allowed Users");
    renderTable($result_blocked, "Blocked Users");

    // Close connection
    mysqli_close($connection);
    ?>

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
                labels: ['Total Giveaways', 'Expired Giveaways'],
                datasets: [{
                    label: 'Giveaways',
                    data: [<?= $totalGiveaways ?>, <?= $expiredGiveaways ?>],
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





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <style>
        
    </style>
</head>
<body>
    
</body>
</html>

