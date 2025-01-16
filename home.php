<?php
session_start();
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


// Check if the user is logged in by checking the session
if ($_SESSION['user_type'] !== 'regular') {
    header("Location: index.php"); 
    exit();
}

// Fetch user details from the session
$name = $_SESSION['name'];
$id = $_SESSION['id'];


// Fetch user profile picture from the database
$query = "SELECT profile_pic FROM login WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!empty($user['profile_pic'])) {
    $profile_pic = 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']);
} else {
    $profile_pic = 'default.png'; // Default image if no profile picture
}




// Fetch all food items where status is 'normal' only
$food_query = "SELECT * FROM free_food WHERE user_id != ? AND status = 'normal' ORDER BY id DESC";
$food_stmt = $conn->prepare($food_query);
$food_stmt->bind_param("i", $id);
$food_stmt->execute();
$food_result = $food_stmt->get_result();

$$query = "SELECT profile_pic FROM login WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$profile_pic = !empty($user['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : 'default.png';

$food_query = "
    SELECT free_food.*, login.name AS poster_name, login.profile_pic AS poster_pic
    FROM free_food 
    JOIN login ON free_food.user_id = login.id 
    WHERE free_food.user_id != ? AND free_food.status = 'normal' 
    ORDER BY free_food.id DESC";
$food_stmt = $conn->prepare($food_query);
$food_stmt->bind_param("i", $id);
$food_stmt->execute();
$food_result = $food_stmt->get_result();
// Fetch user details
$user_sql = "SELECT name FROM login WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

$user_name = ($user_result->num_rows > 0) ? htmlspecialchars($user_result->fetch_assoc()['name']) : "Unknown";
// Get the search query from the URL if it exists
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// SQL query to fetch items based on search input
if ($search) {
    $food_query = "
        SELECT free_food.*, login.name AS poster_name, login.profile_pic AS poster_pic
        FROM free_food 
        JOIN login ON free_food.user_id = login.id 
        WHERE free_food.user_id != ? 
        AND free_food.status = 'normal'
        AND (
            free_food.food_title LIKE ? 
            OR free_food.description LIKE ? 
            OR login.name LIKE ?
        )
        ORDER BY free_food.id DESC";
    $search_param = '%' . $search . '%';
    $food_stmt = $conn->prepare($food_query);
    $food_stmt->bind_param("isss", $id, $search_param, $search_param, $search_param);
} else {
    $food_query = "
        SELECT free_food.*, login.name AS poster_name, login.profile_pic AS poster_pic
        FROM free_food 
        JOIN login ON free_food.user_id = login.id 
        WHERE free_food.user_id != ? 
        AND free_food.status = 'normal'
        ORDER BY free_food.id DESC";
    $food_stmt = $conn->prepare($food_query);
    $food_stmt->bind_param("i", $id);
}

$food_stmt->execute();
$food_result = $food_stmt->get_result();


// Check for unread notifications
$notification_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE poster_id = ? AND is_read = 0";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $id); // Use the correct variable for user ID from session
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();
$notification_data = $notification_result->fetch_assoc();
$unread_count = $notification_data['unread_count'];

// Determine which image to show based on unread notifications
$image_to_show = ($unread_count > 0) ? 'notification.gif' : 'notification.png';

// Check for unread messages
$message_sql = "SELECT COUNT(*) AS unread_messages FROM messages WHERE receiver_id = ? AND is_read = 0";
$message_stmt = $conn->prepare($message_sql);
$message_stmt->bind_param("i", $id);
$message_stmt->execute();
$message_result = $message_stmt->get_result();
$unread_messages = $message_result->fetch_assoc()['unread_messages'];
$message_icon = $unread_messages > 0 ? 'talk.gif' : 'talk.png';

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
    <title>Home Page | People: Community Sharing Platform</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="home.css">
    <link rel="icon" type="image/png" href="people.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

</head>
<style>
    body{
        background-color: #333;
    }
</style>
<body>
    <header class="header">
        <h1 class="logo">People</h1>
        <nav class="nav" id="nav">
        <div class="dropdown">
            <a href="#" class="dropdown-toggle">Share Resource</a>
            <div class="dropdown-menu">
                <a href="giveAway.php">GiveAway</a>
                <a href="homeless.php">Homeless </a>
            </div>
        </div>
            <a href="feeds.php">Community</a>
        </nav>
    </header>


    <!-- Display food items -->
    <div class="container">
    <div class="fx">
    <h2>Available <span> GiveAway & Requests </h2></span>
    <!-- Search Form -->
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Search an item.." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Search</button>
    </form>
    </div>
    <div class="grid">
        <?php while ($row = $food_result->fetch_assoc()): ?>
            <?php
                $food_id = $row['id'];
                $food_title = htmlspecialchars($row['food_title']);
                $description = htmlspecialchars($row['description']);
                $poster_name = htmlspecialchars($row['poster_name']);
                $poster_pic = !empty($row['poster_pic']) ? 'data:image/jpeg;base64,' . base64_encode($row['poster_pic']) : 'https://via.placeholder.com/30';

                $img_query = "SELECT food_image, image_type FROM free_food_images WHERE food_id = ? LIMIT 1";
                $img_stmt = $conn->prepare($img_query);
                $img_stmt->bind_param("i", $food_id);
                $img_stmt->execute();
                $img_result = $img_stmt->get_result();
                $img_data = $img_result->fetch_assoc();
                $image_src = $img_data ? 'data:' . $img_data['image_type'] . ';base64,' . base64_encode($img_data['food_image']) : 'https://via.placeholder.com/300x200?text=No+Image';
            ?>
            <div class="food-item">
                <img src="<?php echo $image_src; ?>" alt="<?php echo $food_title; ?>">
                <div class="food-item-content">
                <h3 class="hclss"><?php echo $food_title; ?> </h3>
                <p><?php echo $description; ?></p>
                </div>
                <div class="button-container">
                <a href="view_item.php?food_id=<?php echo $food_id; ?>&user_id=<?php echo $row['user_id']; ?>" class="btn"><span style="color: #4CAF50; font-weight: 700; ">View </span> Details</a>
                <?php if (isset($_SESSION['id'])): ?>
                    <form action="request_handler.php" method="post">
                        <input type="hidden" name="food_id" value="<?php echo $food_id; ?>">
                        <input type="hidden" name="session_id" value="<?php echo $_SESSION['id']; ?>">
                        <button type="submit" name="request_item" class="request-btn">Request <span style="color: #4CAF50; font-weight: 700; " > this </span></button>
                    </form>
                </div>
                    <div class="poster-info">
                    <span class="mini">by <?php echo $poster_name; ?></span>
                    <img src="<?php echo $poster_pic; ?>" alt="<?php echo $poster_name; ?>'s profile picture">
                    </div>
                <?php else: ?>
                    <p>Please <a href="login.php">log in</a> to request this item.</p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>


<!-- Food Item Details Modal -->
<div class="modal" id="foodModal">
    <div class="modal-content" id="modalContent">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<script>
    function openModal(foodId) {
        fetch(`home.php?food_id=${foodId}&action=viewItemDetails`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('modalBody').innerHTML = data;
                document.getElementById('foodModal').classList.add('active');
            });
    }

    function closeModal() {
        document.getElementById('foodModal').classList.remove('active');
    }

    // Close modal if the user clicks outside of the modal content
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('foodModal');
        if (event.target === modal) {
            closeModal();
        }
    });
</script>



<!--  Footer Section -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <h2>People</h2>
                <p>Connecting people, reducing waste, and supporting communities.</p>
            </div>
            <div class="footer-menu">
                <ul class="menu-list">
                    <li><a href="#">About</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
                <ul class="menu-list">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
                <ul class="menu-list">
                    <li><a href="#">Food Sharing</a></li>
                    <li><a href="#">Homeless Support</a></li>
                    <li><a href="#">Donate</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
        <ul class="list">
            <li><a href="#">About </a></li>
            <li><a href="#">Contact </a></li>
            <li><a href="#">Careers</a></li>
        </ul>
        <ul class="list">
            <li><a href="#">Help Center </a></li>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Privacy Policy</a></li>
        </ul>
        
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fa fa-whatsapp" style="font-size:28px;" ></i></a>
            </div>
            <p>&copy; 2024 People. All Rights Reserved.</p>
        </div>
    </div>
</footer>
</body>



<script>
    // Toggle dropdown menu visibility on click
document.querySelector('.dropdown-toggle').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent the default link behavior
    const dropdownMenu = this.nextElementSibling; // Get the dropdown menu
    dropdownMenu.classList.toggle('active'); // Toggle the 'active' class
});

// Close the dropdown menu if clicked outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    if (!dropdown.contains(event.target)) {
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        dropdownMenu.classList.remove('active'); // Hide the menu
    }
});

    // Get the elements
    const profileOverlay = document.getElementById('profileOverlay');
    const profileBtn = document.querySelector('.profile-btn');
    const closeProfileOverlay = document.getElementById('closeProfileOverlay');
    const profileImage = document.getElementById('profileImage');
    const profilePicInput = document.getElementById('profilePicInput');

    // Open the profile overlay when clicking the profile button
    profileBtn.addEventListener('click', function(event) {
        event.preventDefault();
        profileOverlay.classList.add('active');
    });

    // Close the profile overlay when clicking the close button
    closeProfileOverlay.addEventListener('click', function() {
        profileOverlay.classList.remove('active');
    });

    // Close the profile overlay when clicking outside the form
    window.addEventListener('click', function(event) {
        if (event.target === profileOverlay) {
            profileOverlay.classList.remove('active');
        }
    });

    // When the image is clicked, trigger the file input click
    profileImage.addEventListener('click', () => {
        profilePicInput.click();
    });

    // When a new file is selected, update the image preview
    profilePicInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result; // Set the new image
            }
            reader.readAsDataURL(file); // Read the file and update preview
        }
    });



    async function fetchUpdates() {
    try {
        const response = await fetch('fetch_updates.php');
        const data = await response.json();

        console.log("Data received:", data);

        if (data.error) {
            console.error('Error:', data.error);
            return;
        }

        // Log each component of data to confirm values
        console.log("Unread notifications:", data.unread_notifications);
        console.log("Unread messages:", data.unread_messages);
        console.log("Food items:", data.food_items);

        // Update notification icon
        const notificationIcon = document.querySelector('.noti img');
        notificationIcon.src = data.unread_notifications > 0 ? 'notification.gif' : 'notification.png';

        // Update message icon
        const messageIcon = document.querySelector('.circle-button img');
        messageIcon.src = data.unread_messages > 0 ? 'talk.gif' : 'talk.png';

        // Update food items in the grid
        const foodGrid = document.querySelector('.grid');
        if (foodGrid && Array.isArray(data.food_items)) {
            foodGrid.innerHTML = ''; // Clear current items
            data.food_items.forEach(item => {
                const foodItem = document.createElement('div');
                foodItem.classList.add('food-item');
                foodItem.innerHTML = `
                    <img src="${item.poster_pic || 'https://via.placeholder.com/300x200?text=No+Image'}" alt="${item.food_title}">
                    <div class="food-item-content">
                        <h3>${item.food_title}</h3>
                        <p>${item.description}</p>
                    </div>
                    <div class="button-container">
                        <a href="view_item.php?food_id=${item.id}&user_id=${item.user_id}" class="btn">View Details</a>
                        <form action="request_handler.php" method="post">
                            <input type="hidden" name="food_id" value="${item.id}">
                            <input type="hidden" name="session_id" value="${id}">
                            <button type="submit" class="request-btn">Request this</button>
                        </form>
                    </div>
                    <div class="poster-info">
                        <span>by ${item.poster_name}</span>
                        <img src="${item.poster_pic}" alt="${item.poster_name}'s profile picture">
                    </div>
                `;
                foodGrid.appendChild(foodItem);
            });
        }
    } catch (error) {
        console.error('Error fetching updates:', error);
    }
}

// Run fetchUpdates every 1 seconds
setInterval(fetchUpdates, 1000);
fetchUpdates(); // Initial call to fetch updates when the page loads


</script>




     <!-- Floating Contact Button -->
    <div class="floating-contact">
        <a href="home.php" class="contact-btn">
            <img src="SiteIcons/logo.png" alt="logo Us">
        </a>
        <div class="verify">
            <?php if ($status === 'allowed'): ?>
                <img src="SiteIcons/verified.png" alt="Verified" title="Verified User" >
                <?php endif; ?>
        </div>
    <!-- profile -->
    <div class="profile">
    <a href="user_dashboard.php" class="profile-btn">
    <img 
        src="<?php echo htmlspecialchars($profile_pic); ?>" 
        alt="Profile Picture" 
        width="100" 
        style="
            border-radius: 50%; 
            object-fit: cover; 
            border: 4px solid <?php echo ($status === 'allowed') ? '#1b9df4' : 'darkred'; ?>;">
        </a>
        
    </div>
    

    <!-- Floating Contact Button -->
    <div class="icon-p">
        <a href="home.php" class="p">
            <img src="SiteIcons/logo.png" alt="logo Us">
        </a>
    </div>

    <!-- Floating bottom div -->
<div class="floating-div"></div>

<div class="noti">
    <a href="notify_user.php">
        <div class="noti-button">
        <img src="<?php echo $image_to_show; ?>" alt="Notification Status" class="<?php echo ($image_to_show === 'notification.gif') ? 'notification-gif' : 'notification-png'; ?>">
        </div>
    </a>
</div>


<!-- Message Icon -->
<div class="crt">
    <a href="messages.php">
        <div class="circle-button">
            <img src="SiteIcons/<?php echo $message_icon; ?>" 
                 alt="Message Status" 
                 class="message-image <?php echo ($message_icon === 'talk.gif') ? 'talk-gif' : 'talk-png'; ?>">
        </div>
    </a>
</div>


<!-- container button -->

<!-- Wrapper for all content that needs to blur -->
<div class="content-wrapper" id="contentWrapper">
  <!-- Main image to toggle visibility of secondary images -->
  <a href="#" onclick="toggleImages(); return false;">
    <img src="SiteIcons/add.gif" alt="Click to show more images" class="main-image" id="mainImage">
  </a>

<!-- Loading GIF overlay -->
<div class="loading" id="loadingOverlay"></div>

<div class="blur-overlay" id="blurOverlay">
    <div class="blur-content"> <!-- Content that needs to be blurred -->
        <!-- Add main blurred content here -->
    </div>

<!-- Hidden images arranged in a circle around the main image with labels below each one -->
<div class="hidden-images" id="hiddenImages">
  <div class="secondary-wrapper" style="transform: translate(-35px, -40px);">
    <a href="giveAway.php" onclick="toggleImages();">
      <img src="SiteIcons/sharing.png" alt="Image 1" class="secondary-image">
    </a>
    <span class="label">Share Resources</span>
  </div>
  <div class="secondary-wrapper" style="transform: translate(-100px, -120px);">
    <a href="homeless.php" onclick="toggleImages();">
      <img src="SiteIcons/beggar.png" alt="Image 3" class="secondary-image">
    </a>
    <span class="label">Support Homeless</span>
  </div>
  <div class="secondary-wrapper" style="transform: translate(30px, -120px);">
    <a href="feeds.php" onclick="toggleImages();">
      <img src="SiteIcons/loudspeaker.png" alt="Image 4" class="secondary-image">
    </a>
    <span class="label">Support Community</span>
  </div>
</div>

<!-- eeaweteartretertppuer9terithierthierhtierhtiuheritierhtkrhthkrhtkhrtiihrthrothoirhtoir -->
<script>
  let imagesVisible = false;

  function toggleImages() {
    const hiddenImages = document.getElementById('hiddenImages');
    const images = hiddenImages.querySelectorAll('.secondary-wrapper');
    const loadingOverlay = document.getElementById('loadingOverlay');
    imagesVisible = !imagesVisible;

    if (imagesVisible) {
      // Show loading overlay
      loadingOverlay.style.display = 'block';
      blurOverlay.style.display = 'block';


      // Show the first image with a delay of 300ms
      setTimeout(() => {
        images[0].classList.add('show');
      }, 300); 

      // Show the second and third images simultaneously with a delay of 600ms
      setTimeout(() => {
        images[1].classList.add('show');
        images[2].classList.add('show');
        loadingOverlay.style.display = 'none'; // Hide overlay after all images are shown
      }, 600);
      
    } else {
      // Hide all images and reset visibility state
      loadingOverlay.style.display = 'none';
      blurOverlay.style.display = 'none';
      images.forEach((img) => img.classList.remove('show'));
    }
  }
</script>
<!-- eeaweteartretertppuer9terithierthierhtierhtiuheritierhtkrhthkrhtkhrtiihrthrothoirhtoir -->

</html>
