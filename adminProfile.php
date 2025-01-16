<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_POST['add_admin'])) {
    // Get and sanitize input data
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $location = isset($_POST['location']) && !empty($_POST['location']) ? $conn->real_escape_string($_POST['location']) : 'WEB'; // Default to 'WEB' if location is empty
    $address = $conn->real_escape_string($_POST['address']);

    // Prepare the SQL query using a prepared statement
    $sql = "INSERT INTO admin (name, email, password, address) 
            VALUES (?, ?, ?, ?)";

    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("ssss", $name, $email, $password, $address);

        // Execute the query
        if ($stmt->execute()) {
            echo "<script>alert('New admin added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding admin: " . $stmt->error . "');</script>";
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "<script>alert('Error preparing the query: " . $conn->error . "');</script>";
    }
}



// Fetch admin details
$sql = "SELECT * FROM admin ORDER BY Aid ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Admin Dashboard</title>
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
        }.container {
            width: 80%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        table th {
            background: #007BFF;
            color: #fff;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .add-admin-form {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .add-admin-form input, .add-admin-form textarea {
            width: 100%;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .add-admin-form button {
            background: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-admin-form button:hover {
            background: #0056b3;
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
        <a href="manage_user.php">Manage Users</a>
        <a href="mngFeeds.php">Manage Community Feed</a>
        <a href="adminProfile.php" style="background: #003f7f;">Admin Profile</a>
        <div class="profile">
            
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
    <div class="container">
        <h1>Admin Management</h1>
        
        <!-- Admin List -->
        <h2>Existing Admins</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Aid']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No admins found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Add Admin Form -->
        <h2>Add New Admin</h2>
        <form class="add-admin-form" method="POST">
    <input type="text" name="name" placeholder="Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <textarea name="address" placeholder="Address" required></textarea>
    <button type="submit" name="add_admin">Add Admin</button>
</form>



    </div>
        
    </div>

    
</body>
</html>






















<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <style>
        
    </style>
</head>
<body>
    
</body>
</html>
