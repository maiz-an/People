<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Set your password
$dbname = "demo";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle deletion
if (isset($_POST['delete'])) {
    $feed_id = intval($_POST['feed_id']);
    $sql = "DELETE FROM feeds WHERE feed_id = $feed_id";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Feed deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting feed: " . $conn->error . "');</script>";
    }
}

// // Fetch feeds
// $sql = "SELECT * FROM feeds ORDER BY created_at DESC";
// $result = $conn->query($sql);
// ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/png" href="people.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Add your CSS styles here */
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
        .container {
            width: 100%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
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
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
        }
        .delete-btn:hover {
            background: #c82333;
        }
        .view-btn {
            background: #28a745;
            color: white;
        }
        .view-btn:hover {
            background: #218838;
        }
        .card-view {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            width: 90%;
            max-width: 600px;
        }
        .card-view h2 {
            margin-bottom: 10px;
        }
        .card-view p {
            margin: 5px 0;
        }
        .card-view img {
            width: 100%;
            border-radius: 8px;
            margin: 10px 0;
        }
        .card-view button {
            background: #dc3545;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <a href="admin.php">Dashboard</a>
        <a href="analytics.php">Analytics</a>
        <a href="mngGiveaways.php">Manage Giveaways</a>
        <a href="manage_user.php">Manage Users</a>
        <a href="mngFeeds.php" style="background: #003f7f;">Manage Community Feed</a>
        <a href="adminProfile.php">Admin Profile</a>
        <div class="profile">
            
    </div>
    </div>
    

    <div class="main">
        <div class="container">
            <h1>Community Feeds Management</h1>
            <table>
                <thead>
                    <tr>
                        <th>Feed ID</th>
                        <th>User ID</th>
                        <th>Content</th>
                        <th>Likes</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <?php
                $sql = "SELECT * FROM feeds ORDER BY created_at DESC";
                $result = $conn->query($sql);
                ?>

                <tbody id="feedsTable">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $base64Image = $row['content_img'] ? base64_encode($row['content_img']) : ''; // Convert image to Base64
                        ?>
                            <tr>
                                <td><?php echo $row['feed_id']; ?></td>
                                <td><?php echo $row['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['content']); ?></td>
                                <td><?php echo $row['likes_count']; ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="feed_id" value="<?php echo $row['feed_id']; ?>">
                                        <button type="submit" name="delete" class="delete-btn">Delete</button>
                                    </form>
                                    <button class="action-btn view-btn" 
                                            onclick="viewFeed('<?php echo htmlspecialchars(json_encode([
                                                'content' => $row['content'],
                                                'user_id' => $row['user_id'],
                                                'likes_count' => $row['likes_count'],
                                                'created_at' => $row['created_at'],
                                                'content_img' => $base64Image
                                            ])); ?>')">View</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No feeds available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Card View Modal -->
    <div id="cardView" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); z-index:1000;">
        <img id="cardImage" src="" alt="Feed Image" style="max-width:auto; height:255px; display:none; margin-bottom:20px; border-radius:8px;">
        <p id="cardUserId" style="margin:10px 0; font-weight:bold;"></p>
        <p id="cardLikes" style="margin:10px 0;"></p>
        <p id="cardCreatedAt" style="margin:10px 0;"></p>
        <p id="cardContent" style="margin:10px 0; color:#333;"></p>
        <button onclick="closeCardView()" style="padding:10px 20px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer;">Close</button>
    </div>


    <script>
        function viewFeed(feedData) {
        const feed = JSON.parse(feedData);

        // Populate card view fields
        document.getElementById('cardContent').innerText = feed.content;
        document.getElementById('cardUserId').innerText = `User ID: ${feed.user_id}`;
        document.getElementById('cardLikes').innerText = `Likes: ${feed.likes_count}`;
        document.getElementById('cardCreatedAt').innerText = `Created At: ${feed.created_at}`;

        // Display image if available
        if (feed.content_img) {
            document.getElementById('cardImage').src = `data:image/jpeg;base64,${feed.content_img}`;
            document.getElementById('cardImage').style.display = 'block';
        } else {
            document.getElementById('cardImage').style.display = 'none';
        }

        // Show modal
        document.getElementById('cardView').style.display = 'block';
    }

    function closeCardView() {
        document.getElementById('cardView').style.display = 'none';
    }

    </script>

</body>
</html>
