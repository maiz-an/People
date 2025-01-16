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

// Get the user_id from the URL parameters or session
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Determine user role based on user_id
$user_role = 'regular'; // Default role
if ($user_id > 0) {
    $role_sql = "SELECT user_type FROM login WHERE id = ?"; // Assuming the `login` table has a `user_type` column
    $role_stmt = $conn->prepare($role_sql);
    $role_stmt->bind_param("i", $user_id);
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();

    if ($role_result->num_rows > 0) {
        $user_role = $role_result->fetch_assoc()['user_type']; // Adjust based on the actual column name
    }
}

// Set the back URL based on the user role
$back_url = ($user_role === 'organization') ? 'home.php' : 'organizationHome.php';

// Get the food_id and user_id from the URL parameters
$food_id = isset($_GET['food_id']) ? intval($_GET['food_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Handle reporting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report'])) {
    $food_id = intval($_POST['food_id']);
    $report_category = isset($_POST['report']) ? mysqli_real_escape_string($conn, $_POST['report']) : null;

    if ($report_category) { // Check if report_category is not null
        // Update the `report_category` column
        $update_sql = "UPDATE free_food SET report_category = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $report_category, $food_id);
        if ($stmt->execute()) {
            $msg = "Report submitted successfully.";
        } else {
            $msg = "Error submitting report.";
        }
    } else {
        $msg = "No report category selected.";
    }
}


// Fetch food item details
$food_sql = "SELECT * FROM free_food WHERE id = ?";
$food_stmt = $conn->prepare($food_sql);
$food_stmt->bind_param("i", $food_id);
$food_stmt->execute();
$food_result = $food_stmt->get_result();
// the SQL query to include profile_pic from the login table
// Fetch food item details with latitude and longitude
$food_sql = "
    SELECT free_food.food_title, free_food.description, free_food.quantity, free_food.pickup_time, free_food.pickup_instruction,
           free_food.expiration_time, free_food.created_at, free_food.latitude, free_food.longitude,
           login.name, login.profile_pic
    FROM free_food
    JOIN login ON free_food.user_id = login.id
    WHERE free_food.id = ?";
$food_stmt = $conn->prepare($food_sql);
$food_stmt->bind_param("i", $food_id);
$food_stmt->execute();
$food_result = $food_stmt->get_result();

if ($food_result->num_rows > 0) {
    $item = $food_result->fetch_assoc();
    $food_title = htmlspecialchars($item['food_title']);
    $description = htmlspecialchars($item['description']);
    $quantity = htmlspecialchars($item['quantity']);
    $pickup_time = htmlspecialchars($item['pickup_time']);
    $pickup_instruction = htmlspecialchars($item['pickup_instruction']);
    $expiration_time = htmlspecialchars($item['expiration_time']);
    $created_at = date('F j, Y, g:i a', strtotime($item['created_at']));
    $latitude = $item['latitude'];
    $longitude = $item['longitude'];

    // Convert the profile_pic to base64 if it exists, otherwise use a default image
    if (!empty($item['profile_pic'])) {
        $profile_pic = 'data:image/jpeg;base64,' . base64_encode($item['profile_pic']);
    } else {
        $profile_pic = 'default.png'; // URL to a default image
    }

    // Fetch all images for the food item
    $img_sql = "SELECT food_image, image_type FROM free_food_images WHERE food_id = ?";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $food_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();

    $images = [];
    while ($img_row = $img_result->fetch_assoc()) {
        $images[] = 'data:' . $img_row['image_type'] . ';base64,' . base64_encode($img_row['food_image']);
    }
} else {
    echo "Food item not found.";
    exit;
}

// Fetch user details
$user_sql = "SELECT name FROM login WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

$user_name = ($user_result->num_rows > 0) ? htmlspecialchars($user_result->fetch_assoc()['name']) : "Unknown";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Item Details</title>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <style>
        .header{
            display: flex;
            padding: 2%;
            align-items: center;
            min-height: 3.8rem;
            max-height: 3.8rem;
            gap: 45px;
            background-color: #433434;
            border-radius: 45px;
        }
        .header img{
            margin-top: 6px;
            width: 26px;

        }


        .header h2 {
            margin-left: 1.8rem;
            text-align: center;
            color: white;
            font-size: 1.8rem;
            color: #06C167;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f7f8fa;
            color: #333;
            line-height: 1.6;
            background-image: url('bg3.jpg');
            background-size: cover; 
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        

        h2 {
            font-size: 1.8rem;
            color: #4f358e;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Container styling */
.container {
    width: 35%;
    max-width: 800px;
    margin: 20px auto;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    background-color: whitesmoke;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Profile container styling */
.profile-container {
    display: flex;
    align-items: center;
    gap: 15px;
    justify-content: center;
    position: relative;
    margin-bottom: 15px;
}

/* Updated Back Button Styling */
.back-btn {
    display: flex;
    position: absolute;
    top: 30px;
    left: 31.5rem; 
    background: none;
    z-index: 1000; 
    padding: 10px; 
    cursor: pointer;
    transition: transform 0.2s ease;
}

.back-btn img {
    width: 20px; 
    height: 20px;
}

.back-btn:hover {
    transform: scale(1.1); 
}


/* Profile Picture styling within the title */
.profile-container img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-left: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Title and username styling */
.profile-container p {
    margin-top: 5px;
    font-size: 1.6rem;
    color: white;
    display: flex;
    align-items: center;
    margin: 0;
}
.dropdown {
            position: relative;
            display: inline-block;
            width: 35px;
            height: 35px;
        }
        .dropdown img{
            width: 25px;
            margin-top: 2rem;
            margin-left: 28rem;
            margin-bottom: -2rem;
            cursor: pointer;
        }
        .dropdown-content {
            margin-top: 1.5rem;
            margin-left: 17.2rem;
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            padding: 10px;
            z-index: 1;
            border-radius: 5px;
            min-width: 200px;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-content button {
            display: block;
            width: 100%;
            padding: 8px;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
        }
        .dropdown-content button:hover {
            background-color: #ddd;
        }
        .container {
            padding: 20px;
        }
        .message {
            color: green;
            font-weight: bold;
        }


        /* Confirmation Overlay */
#confirmationOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.confirmation-box {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.confirmation-box p {
    margin-bottom: 20px;
    font-size: 1.2rem;
}

.confirmation-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn-confirm,
.btn-cancel {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-confirm {
    background-color: #4CAF50;
    color: white;
}

.btn-confirm:hover {
    background-color: #388e3c;
}

.btn-cancel {
    background-color: #f44336;
    color: white;
}

.btn-cancel:hover {
    background-color: #d32f2f;
}


/* Success Message Box */
.success-message {
    position: fixed;
    top: 15%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #4CAF50;
    color: white;
    font-weight: bold;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    font-size: 1rem;
    text-align: center;
    animation: fadeOut 2s forwards;
}

/* Animation for fade-out effect */
@keyframes fadeOut {
    0% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}




        /* Slider styling */
        .slider-container {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 300px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .slider-images {
            display: flex;
            transition: transform 0.3s ease-in-out;
        }

        .slider-images img {
            width: 100%;
            height: 300px;
            object-fit: contain;
            flex-shrink: 0;
        }

        /* Navigation buttons */
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: blueviolet;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 50%;
        }

        .slider-btn.left {
            left: 10px;
        }

        .slider-btn.right {
            right: 10px;
        }

        /* Dots Indicator */
        .dots-container {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }

        .dot {
            width: 10px;
            height: 10px;
            margin: 0 5px;
            background-color: #ccc;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .dot.active {
            background-color: #4f358e;
        }
        .container p {
            margin: 10px 0;
            color: #555;
        }

        strong {
            color: #333;
        }

        .map-preview {
            width: 100%;
            height: 200px;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #ddd;
        }

        .map-link-btn {
            margin-top: 10px;
            padding: 0;
            background-color: none;
            color: none;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }

        .map-link-btn:hover {
            background-color: #3c2b7a;
        }

        .request-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .request-btn:hover {
            background-color: #388e3c;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .profile-container img {
                width: 40px;
                height: 40px;
            }

            .slider-images img {
                height: 150px;
            }

            .map-preview {
                height: 180px;
            }
        }
        /* @media (max-width: 450px) {
            .container {
                width: 85%;
            }

            h2 {
                font-size: 1.5rem;
            }
            .profile-container p{
                font-size: 1.4rem;
                text-align: center;
                margin: 0 auto;
            }
            .slider-container {
                position: relative;
                overflow: hidden;
                height: 300px;
                width: 100%;
                border-radius: 8px;
                align-content: center;
                align-items: center;
            }
            .slider-images img {
                width: 100%;
                height: 300px;
                object-fit: contain;
                flex-shrink: 0;
            }
            .slider-btn {
                display: none;
            }

            .slider-btn.left {
                left: -10px;
            }

            .slider-btn.right {
                right: -10px;
            }

            .map-preview {
                height: 200px;
            }

            .map-link-btn,
            .request-btn {
                font-size: 0.8rem;
                width: 100%;
            }

            p {
                font-size: 1.1rem;
            }
            back-btn {
        width: 32px;
        height: 32px;
    }



    .profile-container p {
        font-size: 1.6rem;
        text-align: center;
        margin: 0 auto;
    }

 } */

 @media (max-width: 450px) {
    body {
        font-size: 0.9rem;
    }

    .container {
        width: 90%; /* Reduce container width */
        padding: 10px auto;
        margin: 0 auto;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    h2 {
        font-size: 1.4rem;
        margin: 5px 0;
        text-align: center;
    }

    .profile-container {
        margin-left: -1rem;
    }

    .profile-container img {
        margin-top: 5px !important ;
        width: 45px !important ; /* Resize profile picture */
        height: 45px !important;
    }

    .profile-container p {
        font-size: 1.1rem;
        margin: 0 auto;
        color: #333;
    }

    .dropdown {
        position: relative;
        display: inline-block;
        margin-left: -10rem; /* Proper alignment with profile picture */
    }

    .dropdown img {
        width: 22px;
        height: 22px;
        cursor: pointer;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #fff;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 1;
        padding: 8px;
        border-radius: 5px;
        min-width: 150px;
        font-size: 0.8rem;
        margin-right: -1rem;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .dropdown-content button {
        padding: 5px 8px;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
        width: 100%;
    }

    .dropdown-content button:hover {
        background-color: #f0f0f0;
    }

    .header {
        display: flex;
        padding: 10px 15px;
        background-color: #433434;
        border-radius: 30px;
    }

    .header img {
        width: 22px;
    }

    .header h2 {
        font-size: 1.2rem;
        color: white;
        margin: 0;
    }

    .slider-container {
        height: 200px;
    }

    .slider-images img {
        height: 200px;
    }

    .slider-btn {
        padding: 8px;
    }

    .dots-container {
        margin-top: 8px;
    }

    .dot {
        width: 8px;
        height: 8px;
    }

    .map-preview {
        height: 150px; /* Adjust map height */
        margin-top: 10px;
    }

    .confirmation-box {
        padding: 15px;
        font-size: 0.9rem;
    }

    .confirmation-buttons button {
        font-size: 0.9rem;
        padding: 8px 15px;
    }

    .success-message {
        font-size: 0.9rem;
        padding: 10px 15px;
    }

    .request-btn {
        font-size: 0.8rem;
        padding: 8px;
    }
}


    </style>
</head>
<body>
<div class="container">
    <div class="header">
    <a href="mngGiveaways.php" class="back"><img src="SiteIcons/arrow-w.png" alt="arrow-w" ></a>
        <div style="text-align: center;" class="profile-container">
        <p style="color: white; margin-top: 20px;"><strong style=" color: #d14197;"><?php echo $food_title; ?> </strong> <span style="margin-left: 5px; color: #06C167;">by <?php echo $user_name; ?></span>
            <img src="<?php echo $profile_pic; ?>" alt="Profile Picture"></p>
        </div>

<!-- Success Message Box -->
<div id="successMessageBox" class="success-message" style="display: none;">
    <p id="successMessage"></p>
</div>


<!-- Confirmation Overlay -->
<div id="confirmationOverlay" style="display: none;">
    <div class="confirmation-box">
        <p>Are you sure you want to report this item?</p>
        <div class="confirmation-buttons">
            <button id="confirmReport" class="btn-confirm">Yes, Report</button>
            <button id="cancelReport" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>
</div> 

<!-- Update dropdown form
<div class="dropdown">
    <img src="SiteIcons/information-button.png" alt="Report">
    <div class="dropdown-content">
        <form id="reportForm" action="" method="POST">
            <input type="hidden" name="food_id" value="<?php echo $food_id; ?>">
            <input type="hidden" name="report" id="reportCategory">
            <button type="button" onclick="showConfirmation('Spam')">Spam</button>
            <button type="button" onclick="showConfirmation('Inappropriate Content')">Inappropriate Content</button>
            <button type="button" onclick="showConfirmation('Fraud or Scam')">Fraud or Scam</button>
            <button type="button" onclick="showConfirmation('Expired or Invalid')">Expired or Invalid</button>
            <button type="button" onclick="showConfirmation('Safety Concerns')">Safety Concerns</button>
            <button type="button" onclick="showConfirmation('Wrong Category')">Wrong Category</button>
            <button type="button" onclick="showConfirmation('Offensive to Homeless')">Offensive to Homeless</button>
        </form>
    </div>
</div> -->


<br> <br>

    <!-- Image Slider -->
    <div class="slider-container">
        <div class="slider-images" id="sliderImages">
            <?php foreach ($images as $src): ?>
                <img src="<?php echo $src; ?>" alt="Food Image">
            <?php endforeach; ?>
        </div>
        <button class="slider-btn left" onclick="moveSlide(-1)">&#10094;</button>
        <button class="slider-btn right" onclick="moveSlide(1)">&#10095;</button>
    </div>

    <!-- Dots Indicator -->
    <div class="dots-container" id="dotsContainer"></div>

    <p><strong>Description:</strong> <?php echo $description; ?></p>
    <p><strong>Quantity:</strong> <?php echo $quantity; ?></p>
    <p><strong>Pickup Time:</strong> <?php echo $pickup_time; ?></p>
    <p><strong>pickup instruction:</strong> <?php echo $pickup_instruction; ?></p>
    <p><strong>Posted on:</strong> <?php echo $created_at; ?></p>
    <p><strong>Visible For:</strong> <?php echo $expiration_time; ?></p>

    <!-- Mapbox Map Preview -->
    
    <p><strong>Location:</strong></p>
    <a class="map-link-btn" href="javascript:void(0)" onclick="openInPreferredMap()"><div id="map" class="map-preview"></div></a>

</div>

<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script>
    // Mapbox access token
    mapboxgl.accessToken = 'pk.eyJ1IjoicGVvcGxlcGxhdGZvcm0iLCJhIjoiY20ybGphZHk0MGNmdzJpcHdrcHVyMzh5ZSJ9.t_fL-Pv4n1zsteW466ksTg';

    // Initialize the Mapbox map
    const latitude = <?php echo $latitude; ?>;
    const longitude = <?php echo $longitude; ?>;
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [longitude, latitude],
        zoom: 15
    });

    // Add a marker at the specified location
    new mapboxgl.Marker()
        .setLngLat([longitude, latitude])
        .addTo(map);

    // Function to open location in user's preferred map app
    function openInPreferredMap() {
        const googleMapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}`;
        window.location.href = googleMapsUrl;
    }

    // JavaScript for Image Slider
    let currentIndex = 0;
    const slides = document.querySelectorAll('.slider-images img');
    const totalSlides = slides.length;
    const dotsContainer = document.getElementById('dotsContainer');

    // Create dots dynamically based on the number of slides
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        dot.setAttribute('data-index', i);
        dot.addEventListener('click', () => goToSlide(i));
        dotsContainer.appendChild(dot);
    }

    // Function to update active dot
    function updateDots() {
        document.querySelectorAll('.dot').forEach(dot => dot.classList.remove('active'));
        document.querySelectorAll('.dot')[currentIndex].classList.add('active');
    }

    // Function to move slides
    function moveSlide(direction) {
        currentIndex = (currentIndex + direction + totalSlides) % totalSlides;
        document.getElementById('sliderImages').style.transform = `translateX(-${currentIndex * 100}%)`;
        updateDots();
    }

    // Function to jump to a specific slide
    function goToSlide(index) {
        currentIndex = index;
        document.getElementById('sliderImages').style.transform = `translateX(-${currentIndex * 100}%)`;
        updateDots();
    }

    // Initial dot setup
    updateDots();

    // Swipe functionality for mobile
    let startX = 0;
    const slider = document.querySelector('.slider-container');

    slider.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    });

    slider.addEventListener('touchmove', (e) => {
        if (!startX) return;
        const moveX = e.touches[0].clientX;
        const diff = startX - moveX;

        if (diff > 50) {
            moveSlide(1); // Swipe left
            startX = 0;
        } else if (diff < -50) {
            moveSlide(-1); // Swipe right
            startX = 0;
        }
    });


    document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('confirmationOverlay');
    const confirmButton = document.getElementById('confirmReport');
    const cancelButton = document.getElementById('cancelReport');
    const reportForm = document.getElementById('reportForm');
    const reportCategoryInput = document.getElementById('reportCategory');
    let selectedReportCategory = '';

    // Show the confirmation overlay
    window.showConfirmation = (category) => {
        selectedReportCategory = category;
        overlay.style.display = 'flex';
    };

    // Confirm the report
    confirmButton.addEventListener('click', () => {
        reportCategoryInput.value = selectedReportCategory;
        reportForm.submit(); // Submit the form
    });

    // Cancel the report
    cancelButton.addEventListener('click', () => {
        overlay.style.display = 'none';
    });
});


document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('confirmationOverlay');
    const confirmButton = document.getElementById('confirmReport');
    const cancelButton = document.getElementById('cancelReport');
    const reportForm = document.getElementById('reportForm');
    const reportCategoryInput = document.getElementById('reportCategory');
    const successBox = document.getElementById('successMessageBox');
    const successMessage = document.getElementById('successMessage');
    let selectedReportCategory = '';

    // Show the confirmation overlay
    window.showConfirmation = (category) => {
        selectedReportCategory = category;
        overlay.style.display = 'flex';
    };

    // Confirm the report
    confirmButton.addEventListener('click', () => {
        reportCategoryInput.value = selectedReportCategory;
        reportForm.submit(); // Submit the form
    });

    // Cancel the report
    cancelButton.addEventListener('click', () => {
        overlay.style.display = 'none';
    });

    // Display success message
    const displaySuccessMessage = (message) => {
        successMessage.innerText = message;
        successBox.style.display = 'block';
        setTimeout(() => {
            successBox.style.display = 'none';
        }, 2000); // Hide after 2 seconds
    };

    // Check if a success message should be shown
    <?php if (!empty($msg)) { ?>
    displaySuccessMessage(<?php echo json_encode($msg); ?>);
    <?php } ?>
});


</script>
</body>

</html>
