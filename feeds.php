<?php
session_start();

// Check if the user is logged in and either a regular or organization user
if (!isset($_SESSION['id']) || !in_array($_SESSION['user_type'], ['regular', 'organization'])) {
    header("Location: index.php");
    exit();
}
// Determine the user ID from the session
$user_id = $_SESSION['id'];

$host = "localhost";
$dbname = "demo";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine the back button URL based on the user type
$user_id = $_SESSION['id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'organization') {
    $back_url = "organizationHome.php";
} else {
    $back_url = "home.php";
}

// Handle new feed post with optional image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = $conn->real_escape_string($_POST['content']);
    $feed_type = isset($_POST['feed_type']) ? $conn->real_escape_string($_POST['feed_type']) : 'community_feed';
    $user_id = $_SESSION['id'];
    $default_img = "SiteIcons/3.png"; // Path to the default image
    $content_img = $default_img; // Set default image initially

    if (isset($_FILES['content_img']) && $_FILES['content_img']['error'] == 0) {
        // Read the uploaded image and store it in the database as BLOB
        $image_data = file_get_contents($_FILES['content_img']['tmp_name']);
        $content_img = $image_data; // Set the uploaded image data
    } else {
        // If no image is uploaded, use the default image
        $content_img = file_get_contents($default_img);
    }

    // Insert the feed into the database, saving the image as a BLOB
    $stmt = $conn->prepare("INSERT INTO feeds (user_id, content, content_img, likes_count, feed_type) VALUES (?, ?, ?, 0, ?)");
    $stmt->bind_param("isss", $user_id, $content, $content_img, $feed_type);
    $stmt->execute();
    $stmt->close();
}


// Handle like button using AJAX
if (isset($_POST['like_feed_id'])) {
    $feed_id = (int)$_POST['like_feed_id'];
    $user_id = $_SESSION['id'];

    // Check if the user already liked the feed
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND feed_id = ?");
    $stmt->bind_param("ii", $user_id, $feed_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already liked this post.']);
    } else {
        // Insert like record and update feed's like count
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO likes (user_id, feed_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $feed_id);
        $stmt->execute();

        $conn->query("UPDATE feeds SET likes_count = likes_count + 1 WHERE feed_id = $feed_id");

        $result = $conn->query("SELECT likes_count FROM feeds WHERE feed_id = $feed_id");
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'likes_count' => $row['likes_count']]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    $stmt->close();
    exit();
}

// Fetch feeds
$result = $conn->query("
    SELECT feeds.*, login.name AS username, login.profile_pic 
    FROM feeds 
    JOIN login ON feeds.user_id = login.id 
    ORDER BY feeds.created_at DESC
");
$sql = "
    SELECT feeds.*, login.name AS username, login.profile_pic, login.status
    FROM feeds 
    JOIN login ON feeds.user_id = login.id

";


// Check if filter_type is set in the GET request
if (isset($_GET['filter_type']) && in_array($_GET['filter_type'], ['community_feed', 'land_listening', 'request'])) {
    $filter_type = $conn->real_escape_string($_GET['filter_type']);
    $sql .= " WHERE feeds.feed_type = '$filter_type'";
}

// Add ordering to the query
$sql .= " ORDER BY feeds.created_at DESC";

// Execute the query
$result = $conn->query($sql);
// Fetch user information from the 'login' table, including status
$user_query = "SELECT id, email, name, status FROM login WHERE id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param('i', $id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_info = $user_result->fetch_assoc();

// Check if user_info is valid
$name = isset($user_info['name']) ? htmlspecialchars($user_info['name']) : 'Unknown User';
$email = isset($user_info['email']) ? htmlspecialchars($user_info['email']) : 'Unknown Email';
$status = isset($user_info['status']) ? $user_info['status'] : 'pending'; // Default to 'pending' if status is not found
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Community Announcement | People: Community Sharing Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="feeds.css">
    <style>
         /* Mobile-Specific CSS */
         /* @media (max-width: 460px) {
            .container {
                width: 100%;
            }
            
            .filter-box form {
                flex-direction: column;
                gap: 10px;
            }

            .filter-box img{
                margin-top: 5px;
                width: 80px;
            }

            #filter_type {
                width: 100%;
                margin: 0;
            }

            .filter-btn {
                width: 100%; 
            }

            .header h2 {
                font-size: 1.2rem;
            }

            .add_box {
                flex-direction: column;
                gap: 10px;
            }
        } */
        .filter-btn{
    cursor: pointer;

        }
    </style>
    <script>
        async function likeFeed(feedId) {
            const response = await fetch("", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `like_feed_id=${feedId}`
            });

            const result = await response.json();
            if (result.success) {
                const likeCountElem = document.getElementById(`like-count-${feedId}`);
                likeCountElem.textContent = result.likes_count + ' Likes';
            } else {
                alert(result.message || "Failed to like the post. Please try again.");
            }
        }
    </script>
</head>
<body>

    <div class="container">

    <div class="headandadd"> <br>
    <div class="header">
    <a href="<?php echo $back_url; ?>"><img src="SiteIcons/arrow.png" alt="back"></a>
    <h2><span style="color: #ff009a;">Community </span>Announcement</h2>
    </div> <br><br>
        <div class="add_box">
            <!-- <div class="add-filter">
                <div class="filter-box-add">
                    <label for="filter_type" style="display: block;  ">Make Announcement</label>
                    <a href=""> <img src="SiteIcons/announcement.png" alt="SiteIcons/plus.png"> </a>
                </div>
            </div> -->
            <div class="filter-box">
                <form method="GET" action="" style="display: flex; gap: 15px;">
                    <label class="anno" for="filter_type" style="display: block; margin-left: 3rem; margin-top: 0.5px;">Make Announcement</label>
                    <a href="javascript:void(0)" id="togglePostBox"> 
                        <img src="SiteIcons/plus.png" alt="Make Announcement">
                    </a>
                </form>
            </div>
            <div class="filter-box">
                <form method="GET" action="" style=" display: flex ; gap: 15px; ">
                    <label for="filter_type" style="display: flex;  ">Find feeds by</label>
                    <select name="filter_type" id="filter_type" style=" margin-top: -5px; width: 38%; height: 10%; padding: 10px; border-radius: 15px; background-color: #f9f9f9;">
                        <option value="">All</option>
                        <option value="community_feed" <?= isset($_GET['filter_type']) && $_GET['filter_type'] === 'community_feed' ? 'selected' : '' ?>>Community Feed</option>
                        <option value="land_listening" <?= isset($_GET['filter_type']) && $_GET['filter_type'] === 'land_listening' ? 'selected' : '' ?>>Land Listing</option>
                        <option value="request" <?= isset($_GET['filter_type']) && $_GET['filter_type'] === 'request' ? 'selected' : '' ?>> Donation Request</option>
                    </select>
                    <button type="submit" style=" margin-top: -5px; padding: 3px 20px; background-color: #4caf50; color: #fff; border: none; border-radius: 12px;" class="filter-btn">Filter</button>
                </form>
            </div>
        </div>
        <a href="userEditFeed.php" class="v-btn">View My Announcement </a>
    </div>



    <br><br>

    <div class="post-box" id="postBox" style="display: none; border: 1px solid #ddd; padding: 20px; border-radius: 10px; background-color: #f9f9f9;">
    <h2 style="font-family: Arial, sans-serif; font-size: 20px; color: #333;">Make an Announcement</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Custom File Upload -->
            <label for="content_img" id="customUpload" style="display: block; margin-top: 15px; cursor: pointer; color: #4caf50; text-decoration: underline; font-family: Arial, sans-serif; font-size: 14px;">
            <img src="SiteIcons/Select Image (2).png" alt="upload" style="width: 100px; margin-left: 0.2rem; ">
            </label>
            <input type="file" name="content_img" id="content_img" accept="image/*" style="display: none;">

            <!-- Image Preview -->
            <div id="imagePreviewContainer" style="margin-top: 15px; display: none; position: relative; width: 20%; max-height: 20%;">
                <img id="imagePreview" src="" alt="Image Preview" style="width: 100%; height: auto; border: 1px solid #ccc; border-radius: 10px;">
                <button id="removeImageButton" type="button" style="width: 20%; position: absolute; top: 5px; right: 5px; background-color: red; color: white; border: none; border-radius: 50%; padding: 5px; cursor: pointer; font-size: 12px; font-weight: bold;">X</button>
    </div>


        <textarea name="content" rows="3" style="width: 45%; padding: 10px; border: 1px solid #ccc; border-radius: 10px; font-family: Arial, sans-serif; font-size: 14px;" placeholder="What's on your mind?"></textarea>
    
        <!-- Dropdown for Feed Type -->
        <label for="feed_type" style="display: block; margin-top: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #333;">Select Feed Type:</label>
        <select name="feed_type" id="feed_type" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-family: Arial, sans-serif; font-size: 14px;">
            <option value="community_feed">Community Feed</option>
            <option value="land_listening">Land Listing</option>
            <option value="request">Donation Request</option>
        </select>



        <button type="submit" style="margin-top: 20px; padding: 10px 15px; background-color: #4caf50; color: #fff; border: none; border-radius: 5px; font-family: Arial, sans-serif; font-size: 14px; cursor: pointer;">
            Post
        </button>
    </form>
</div>

<script>
    // Get the elements
    const fileInput = document.getElementById('content_img');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const removeImageButton = document.getElementById('removeImageButton');

    // Show image preview when a file is selected
    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();

            // When the file is loaded, set the preview image source
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.style.display = 'block'; // Show the preview container
            };

            reader.readAsDataURL(file); // Read the file
        } else {
            imagePreviewContainer.style.display = 'none'; // Hide the preview if no file is selected
        }
    });

    // Remove the selected image
    removeImageButton.addEventListener('click', function () {
        fileInput.value = ''; // Clear the file input
        imagePreviewContainer.style.display = 'none'; // Hide the preview container
    });
</script>


    <script>
    // Get the elements
    const togglePostBox = document.getElementById("togglePostBox");
    const postBox = document.getElementById("postBox");

    // Add click event to toggle visibility
    togglePostBox.addEventListener("click", function () {
        if (postBox.style.display === "none" || postBox.style.display === "") {
            postBox.style.display = "block"; // Show the post box
        } else {
            postBox.style.display = "none"; // Hide the post box
        }
    });
</script>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="feed">
                <div class="feed-content">
                    
                <?php if ($row['profile_pic']): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($row['profile_pic']) ?>" 
                        alt="Profile Picture" 
                        class="profile-img"
                        style="border-radius: 50%; object-fit: cover; border: 4px solid <?= ($row['status'] === 'allowed') ? '#1b9df4' : 'darkred'; ?>;">
                <?php else: ?>
                    <img src="default-profile.png" 
                        alt="Default Profile Picture" 
                        class="profile-img" 
                        style="border-radius: 50%; object-fit: cover; border: 4px solid <?= ($row['status'] === 'allowed') ? '#1b9df4' : 'darkred'; ?>;">
                <?php endif; ?>
                <div class="verify">
                    <?php if ($row['status'] === 'allowed'): ?>
                        <img src="SiteIcons/verified.png" alt="Verified" title="Verified User">
                    <?php endif; ?>
                </div>
                    <h3 ><?= htmlspecialchars($row['username']) ?></h3>
                        <p class="cat"><strong>category : </strong> <?= htmlspecialchars($row['feed_type']) ?></p> <br>
                        <p><?= htmlspecialchars($row['content']) ?></p>
                        
                <button class="like-btn" onclick="likeFeed(<?= $row['feed_id'] ?>)">
                    <img src="like.jpg" alt="" class="like">
                </button>
                <span id="like-count-<?= $row['feed_id'] ?>" class="like-count"><?= $row['likes_count'] ?> Likes</span>
                </div>


                <div class="feedimg">
    <?php if ($row['content_img']): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($row['content_img']) ?>" alt="Post Image">
    <?php endif; ?>
</div>

                
            </div>

        <?php endwhile; ?>
    </div>
    
    <script>
        let like_btn = document.getElementsByClassName("like");
        let like_pic = "Like.jpg";
        let liked_pic = "Liked.jpg";

        for (let i = 0; i < like_btn.length; i++) {
           like_btn[i].addEventListener("click", function(){
                this.setAttribute("src", liked_pic);
           });
            
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
