<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "demo";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle feed editing
if (isset($_POST['update'])) {
    $feed_id = intval($_POST['feed_id']);
    $content = $conn->real_escape_string($_POST['content']);

    // Handle image upload
    $image_data = null;
    if (!empty($_FILES['content_img']['tmp_name'])) {
        $image_data = addslashes(file_get_contents($_FILES['content_img']['tmp_name']));
    }

    // Update query
    $sql = "UPDATE feeds SET content = '$content'";
    if ($image_data !== null) {
        $sql .= ", content_img = '$image_data'";
    }
    $sql .= " WHERE feed_id = $feed_id AND user_id = $user_id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Feed updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating feed: " . $conn->error . "');</script>";
    }
}

// Fetch user's feeds
$sql = "SELECT * FROM feeds WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);


// Capture the search term
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build SQL query to fetch user's feeds with optional search condition
$sql = "SELECT * FROM feeds WHERE user_id = $user_id";

// Apply search condition if search query is provided
if (!empty($search)) {
    $sql .= " AND content LIKE '%$search%'";
}

$sql .= " ORDER BY created_at DESC";

// Execute the query
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feeds</title>
    <style>
        /* General Styling */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        header {
            width: 100%;
            background-color: #343a40;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            z-index: 10;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        header a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            background: #007bff;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        header a:hover {
            background: #0056b3;
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 100px auto 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .feed {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }

        .feed:last-child {
            border-bottom: none;
        }

        .feed-content {
            margin: 10px 0;
            color: #555;
            font-size: 16px;
        }

        .feed-image {
            max-width: auto;
            height: 150px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .edit-btn {
            background: #007bff;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 10px 0;
            transition: background 0.3s ease;
        }

        .edit-btn:hover {
            background: #0056b3;
        }

        .update-form {
            display: none;
            margin-top: 10px;
        }

        .update-form textarea {
            width: 100%;
            height: 80px;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .update-form input[type="file"] {
            margin: 10px 0;
        }

        .update-form img {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
            border-radius: 4px;
            display: none; /* Hidden until preview is available */
        }

        .update-form button {
            background: #28a745;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .update-form button:hover {
            background: #218838;
        }

        .no-feeds {
            text-align: center;
            color: #777;
            font-size: 18px;
            margin-top: 20px;
        }
        .b-btn{
            position: relative;
            left: 80%;
        }
        /* Search Bar Styling */
.search-form {
    margin: 20px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px; /* Add space between input and button */
}

.search-form input[type="text"] {
    width: 100%;
    max-width: 400px;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.3s ease;
}

.search-form input[type="text"]:focus {
    border-color: #007bff;
}

.search-form button {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.search-form button:hover {
    background-color: #0056b3;
}

.search-form button:focus {
    outline: none;
}
    </style>
</head>
<body>
<header>
    <h1>My Feeds</h1>
    <a href="feeds.php" class="b-btn">Back</a>
</header>
<div class="container">
    <h2>My Feeds</h2>
    <!-- Search Bar -->
<!-- Search Bar -->
<form method="GET" action="userEditFeed.php" class="search-form">
        <input type="text" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Search</button>
    </form>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="feed">
                <p><strong>Created At:</strong> <?php echo $row['created_at']; ?></p>
                <p class="feed-content"><?php echo htmlspecialchars($row['content']); ?></p>
                <?php if (!empty($row['content_img'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['content_img']); ?>" alt="Feed Image" class="feed-image">
                <?php endif; ?>
                <button class="edit-btn" onclick="toggleEditForm(<?php echo $row['feed_id']; ?>)">Edit</button>
                <form class="update-form" id="form-<?php echo $row['feed_id']; ?>" method="POST" enctype="multipart/form-data">
                    <textarea name="content"><?php echo htmlspecialchars($row['content']); ?></textarea>
                    <input type="file" name="content_img" accept="image/*" onchange="previewImage(event, <?php echo $row['feed_id']; ?>)">
                    <img id="preview-<?php echo $row['feed_id']; ?>" src="#" alt="Preview Image">
                    <input type="hidden" name="feed_id" value="<?php echo $row['feed_id']; ?>">
                    <button type="submit" name="update">Update</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-feeds">You have no feeds to display.</p>
    <?php endif; ?>
</div>

<script>
    function toggleEditForm(feedId) {
        const form = document.getElementById('form-' + feedId);
        form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
    }

    function previewImage(event, feedId) {
        const file = event.target.files[0];
        const preview = document.getElementById('preview-' + feedId);

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block'; // Show the preview
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = '';
            preview.style.display = 'none'; // Hide the preview if no file is selected
        }
    }
</script>
</body>
</html>
