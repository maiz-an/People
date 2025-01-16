<?php

session_start();

// Check if the user is logged in and either a regular or organization user
if (!isset($_SESSION['id']) || !in_array($_SESSION['user_type'], ['regular', 'organization'])) {
    header("Location: index.php");
    exit();
}
// Determine the user ID from the session
$user_id = $_SESSION['id'];

// Connect to MySQL database using mysqli
$conn = mysqli_connect("localhost", "root", "", "demo");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check the current user's status
$status_query = "SELECT status FROM login WHERE id = $user_id LIMIT 1";
$status_result = mysqli_query($conn, $status_query);

if ($status_result && mysqli_num_rows($status_result) > 0) {
    $status_row = mysqli_fetch_assoc($status_result);
    if ($status_row['status'] === 'pending') {
        // Redirect to verify.html if the user's status is pending
        header("Location: please_verify.php");
        exit();
    }
}

// Determine the back button URL based on the user type
$user_id = $_SESSION['id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'organization') {
    $back_url = "organizationHome.php";
} else {
    $back_url = "home.php";
}

if ($user_type === 'organization') {
    $back_url_dash = "org_user_dashboard.php";
} else {
    $back_url_dash = "user_dashboard.php";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Connect to MySQL database using mysqli
$conn = mysqli_connect("localhost", "root", "", "demo");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data and sanitize inputs
    $user_id = $_SESSION['id'];
    $food_title = isset($_POST['food_title']) ? mysqli_real_escape_string($conn, $_POST['food_title']) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $pickup_time = isset($_POST['pickup-time']) ? mysqli_real_escape_string($conn, $_POST['pickup-time']) : '';
    $pickup_instruction = isset($_POST['pickup_instruction']) ? mysqli_real_escape_string($conn, $_POST['pickup_instruction']) : '';
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0.0;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0.0;
    $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, $_POST['category']) : 'food';

    // Get show_up_duration from the form input
    $show_up_duration = isset($_POST['show_up_duration']) ? floatval($_POST['show_up_duration']) : 0; // Duration in hours

    // If the duration is 0, default to 1 hour to prevent same expiration_time as created_at
    if ($show_up_duration <= 0) {
        $show_up_duration = 1; // Default duration in hours
    }

    // MySQL query to calculate expiration_time using NOW() + show_up_duration
    $query = "
        INSERT INTO free_food 
        (user_id, food_title, description, quantity, pickup_time, pickup_instruction, latitude, longitude, expiration_time, category, created_at) 
        VALUES 
        ('$user_id', '$food_title', '$description', $quantity, '$pickup_time', '$pickup_instruction', $latitude, $longitude, 
        DATE_ADD(NOW(), INTERVAL $show_up_duration HOUR), '$category', NOW())
    ";

// After inserting the giveaway into `free_food` table
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming $latitude and $longitude of the giveaway location are already set
    $radius = 10; // Radius in kilometers

    // Query to find nearby users
    $nearby_users_query = "
    SELECT id, name, email,
           (6371 * ACOS(COS(RADIANS($latitude)) 
           * COS(RADIANS(latitude)) 
           * COS(RADIANS(longitude) - RADIANS($longitude)) 
           + SIN(RADIANS($latitude)) 
           * SIN(RADIANS(latitude)))) AS distance 
    FROM login 
    WHERE user_type IN ('regular', 'organization') AND id != $user_id
    HAVING distance <= $radius";


    $nearby_users_result = mysqli_query($conn, $nearby_users_query);

    if ($nearby_users_result && mysqli_num_rows($nearby_users_result) > 0) {
        while ($user = mysqli_fetch_assoc($nearby_users_result)) {
            $user_email = $user['email'];
            $user_name = $user['name'];

            // Use PHPMailer to send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'info.people.platfrom@gmail.com'; //  email
                $mail->Password = 'xtvqpbrsbtmnbnhv'; //  email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@people.com', 'Help A Homeless!');
                $mail->addAddress($user_email);

                $mail->isHTML(true);
                $mail->Subject = "Urgent: Help A Homeless Near You - $food_title";
                $mail->Body = "
                            <p>A new opportunity to make a difference has just been added to the <strong>People: Community Sharing Platform</strong>.</p>
                                <p><strong>Title:</strong> $food_title<br>
                                <strong>Description:</strong> $description<br>
                                <strong>Pickup Location:</strong> Nearby your location.</p>
                                <p>This initiative is to assist a homeless individual in need. Your support in claiming and delivering this item would make a significant impact.</p>
                                <p><strong>Action Needed:</strong> Please log in to your account to learn more and take action. Together, we can create a kinder community.</p>
                                <p>Best regards,<br>
                                <strong>The People Community Team</strong><br>
                                <i>People: Community Sharing Platform</i></p>";

                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send email to $user_email: " . $mail->ErrorInfo);
            }
        }
    }
}



    if (mysqli_query($conn, $query)) {
        // Get the last inserted ID for free_food table
        $food_id = mysqli_insert_id($conn);

        // Handle image uploads
        // Handle image uploads or use default
if (isset($_FILES['food_images']) && !empty($_FILES['food_images']['name'][0])) {
    foreach ($_FILES['food_images']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['food_images']['name'][$key];
        $file_type = $_FILES['food_images']['type'][$key];
        $image_data = file_get_contents($tmp_name);
        $image_data = mysqli_real_escape_string($conn, $image_data);

        // Insert the uploaded image into the database
        $query_img = "INSERT INTO free_food_images (food_id, food_image, image_type) VALUES ($food_id, '$image_data', '$file_type')";
        mysqli_query($conn, $query_img);
    }
} else {
    // Use the default image if no file is uploaded
    $default_image_path = 'SiteIcons/2.png'; 
    $default_image_data = file_get_contents($default_image_path);
    $default_image_data = mysqli_real_escape_string($conn, $default_image_data);
    $default_image_type = 'image/png'; 

    $query_img = "INSERT INTO free_food_images (food_id, food_image, image_type) VALUES ($food_id, '$default_image_data', '$default_image_type')";
    mysqli_query($conn, $query_img);
}


       // Set a flag to indicate success
       echo "<script>window.onload = function() { showSuccessModal(); }</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    // Close the database connection
    mysqli_close($conn);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Food GiveAway | People: Community Sharing Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
    
    <style>

        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            background-image: url('bg3.jpg');
            background-size: cover; 
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .container {
            background-color: white;
            max-width: 35%;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }
        .upload-icon{
            width: 6rem;
            height: auto;
            cursor: pointer;
        }
        .upload-icon-cover{
            margin-top: -1rem;
            margin-left: -5.4rem;
            width: 4rem;
            height: auto;
            cursor: pointer;
        }
        h2 {
            text-align: center;
            color: #4f358e;
            margin-bottom: 20px;
        }
        .gap{
            margin-top: 3rem;

        }
        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 10px;
            text-align: center;
        }

        .modal-content h2 {
            margin-bottom: 20px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .btn-secondary {
            background-color: #f44336;
            color: white;
        }

        .btn-primary:hover, .btn-secondary:hover {
            opacity: 0.8;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        input[type="datetime-local"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .file-upload input[type="file"] {
            padding: 0;
        }
        .btn {
            display: block;
            width: 100%;
            background-color: #4f358e;
            color: white;
            border: none;
            padding: 15px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #3d276c;
        }
        .form-group-radio{
            display: none;
            gap: 5px;
        }
        .form-group-radio-ca{
            margin-top: -7%;
            padding-bottom: 8%;
            margin-left: 30%;
            display: flex;
            gap: 5px;
        }
        .form-group-inline {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .form-group-inline div {
            flex: 1;
        }
        .img-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            height: fit-content;
            width: auto;
        }
        .img-preview {
            width: 150px;
            height: 180px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ccc;
            position: relative;
        }
        .img-preview-wrapper {
            position: relative;
            display: inline-block;
        }
        .map-container {
            margin-top: 20px;
            height: 300px;
        }
        .location-icon {
            margin-right: 5px;
        }
        .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            font-size: 12px;
            cursor: pointer;
        }
      
        .header{
            display: flex;
            padding: 2%;
            align-items: center;
            min-height: 2rem;
            max-height: 2rem;
            gap: 10px;
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
        @media (max-width: 450px) {
    /* General Body Styling */
    body {
        font-family: 'Poppins', sans-serif;
        padding: 10px;
    }


    .header img {
        width: 20px;
    }

    .header h2 {
        font-size: 1.4rem;
        color: #06C167;
    }

    /* Form Container */
    .container {
        background-color: #fff;
        margin: 0 auto;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        max-width: 100%; /* Use full width for small screens */
    }

    /* Form Elements */
    input[type="text"],
    input[type="number"],
    input[type="file"],
    input[type="datetime-local"],
    input[type="url"],
    select,
    textarea {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        font-size: 1rem;
        margin-bottom: 5px;
        display: block;
    }

    .btn {
        width: 100%;
        background-color: #4f358e;
        color: white;
        border: none;
        padding: 10px;
        font-size: 1rem;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        background-color: #3d276c;
    }

    /* Toggle Buttons */
    .toggle-buttons {
        display: flex;
        margin-bottom: 15px;
    }

    .toggle-btn {
        font-size: 1rem;
        font-weight: bold;
        padding: 10px;
        cursor: pointer;
        border: none;
        background-color: transparent;
        color: #ff009a;
    }

    .toggle-btn.active {
        text-decoration: underline 3px solid #06C167;
    }

    /* Image Previews */
    .img-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .img-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: #ff4d4d;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        text-align: center;
        font-size: 12px;
        cursor: pointer;
    }

    /* Map Container */
    .map-container {
        width: 100%;
        height: 250px;
        margin-top: 15px;
        border-radius: 5px;
        overflow: hidden;
    }

    /* Floating Buttons (Optional) */
    .floating-contact {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    .contact-btn img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .profile-btn img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    /* Footer Adjustments */
    .footer {
        text-align: center;
        padding: 15px 10px;
        background-color: #4f358e;
        color: white;
        font-size: 0.9rem;
        border-radius: 10px;
    }

    .footer a {
        color: #fff;
        text-decoration: underline;
    }

    /* Miscellaneous */
    .search-form {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .search-form input[type="text"] {
        padding: 10px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .search-form button {
        background-color: #4f358e;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    .search-form button:hover {
        background-color: #3d276c;
    }
    .form-group-radio{
            display: none;
            gap: 10px;
        }
        .form-group-radio-ca{
            margin-top: -8%;
            padding-bottom: 8%;
            margin-left: 47%;
            display: flex;
            gap: 5px;
        }
}


.hide{
    display: none;
}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
    <a href="<?php echo $back_url; ?>"><img src="SiteIcons/arrow-w.png" alt="back"></a>
    <h2><span style="color: #ff009a;">Support </span>Homeless</h2>
    </div>
    <div class="gap"></div>
    <form action="" method="post" enctype="multipart/form-data">
      <!-- Radio Buttons for Category -->
<div class="form-group-radio">
    <label for="category">Select Category:</label>
    <div class="form-group-radio-ca">
        <input type="radio" id="category-food" name="category" value="homeless" checked onclick="updateLabels('Food')">
        <label for="category-food">Food</label>
    </div>
    <div class="form-group-radio-ca">
        <input type="radio" id="category-nonfood" name="category" value="homeless" onclick="updateLabels('Item')">
        <label for="category-nonfood">Non-Food</label>
    </div>
</div>

<!-- Labels with IDs matching updateLabels function -->
<div class="form-group file-upload">
    <label id="label-food-image" for="food-images">Image</label> <br>
    <img id="upload-icon" src="SiteIcons/image (3).png" alt="Upload" class="upload-icon" onclick="document.getElementById('food-images').click();" />
    <img id="upload-icon-cover" src="SiteIcons/cloud-computing.png" alt="Upload" class="upload-icon-cover" onclick="document.getElementById('food-images').click();" />
    <input type="file" id="food-images" name="food_images[]" accept="image/*" multiple onchange="previewMultipleImages(event)" style="display:none;">
    <div id="image-previews" class="img-preview-container"></div>
</div>


<div class="form-group">
    <label id="label-food-title" for="food-title">Name Support</label>
    <input type="text" id="food-title" name="food_title" placeholder="Make a Name for Support " required>
</div>

<div class="form-group">
    <label id="label-description" for="description">Support Description</label>
    <textarea id="description" name="description" rows="3" placeholder="Describe about the Support " required></textarea>
</div>



<div class="hide">
<div class="form-group">
    <label id="label-quantity" for="quantity">Quantity</label>
    <input type="text" id="quantity" name="quantity" placeholder="Enter the quantity of GiveAway" value="homeless" required>
</div>

<div class="form-group">
    <label id="label-pickup-time" for="pickup-time">Pickup Time</label>
    <input type="text" id="pickup-time" name="pickup-time" placeholder="After 4pm - 10pm" value="homeless" required>
</div>

<div class="form-group">
    <label id="label-pickup-instruction" for="pickup-instruction">Your Pickup Instruction</label>
    <input type="text" id="pickup-instruction" name="pickup_instruction" placeholder="Instruction for pickup GiveAway" value="homeless" required></textarea>
</div>

</div>


        <!-- Location Search Input -->
        <div class="form-group">
            <label for="location">
                <i class="fa fa-map-marker location-icon"></i> Location
            </label>
            <div id="geocoder" class="geocoder"></div>
            <br>
            <small><a href="javascript:void(0)" onclick="getCurrentLocation()">Use my current location</a></small>
        </div>

        <!-- Map Preview -->
        <div id="map" class="map-container"></div>

        <!-- Hidden fields to store lat/lng -->
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">

<!-- Show Up For (Visibility Period) -->
<!-- Show Up For (Visibility Period) -->
<div class="form-group">
    <label for="show-up">Ask For</label>
    <select id="show-up" name="show_up_duration" required>
        <option value="1">1 Hour</option>
        <option value="2">2 Hours</option>
        <option value="4">4 Hours</option>
        <option value="6">6 Hours</option>
        <option value="8">8 Hours</option>
        <option value="10">10 Hours</option>
        <option value="12">12 Hours</option>
        <option value="24">1 Day</option>
        <option value="48">2 Days</option>
        <option value="72">3 Days</option>
        <option value="96">4 Days</option>
        <option value="120">5 Days</option>
        <option value="144">6 Days</option>
        <option value="168">7 Days</option>
    </select>
</div>

        <div>
                <!-- Submit Button -->
                <button type="submit" class="btn">Submit</button>
            </form>
        </div>

<div id="success-modal" class="modal">
    <div class="modal-content">
        <h2>Request Submitted successfully!</h2>
        <div class="modal-buttons">
            <button class="btn-primary" onclick="addAnotherItem()">Ask New</button>
            <button class="btn-secondary" onclick="goToDashboard()">Go to Dashboard</button>
        </div>
    </div>
</div>



<script>
    function showSuccessModal() {
        document.getElementById('success-modal').style.display = 'flex';
    }

    function addAnotherItem() {
        document.getElementById('success-modal').style.display = 'none';
        document.querySelector('form').reset();
    }

    function goToDashboard() {
        window.location.href = '<?php echo $back_url_dash; ?>';
    }

    function updateLabels(category) {
    document.getElementById('label-food-image').textContent = category + ' Image';
    document.getElementById('label-food-title').textContent = category + ' Title';
    document.getElementById('label-description').textContent = 'Description of the ' + category.toLowerCase();
    document.getElementById('label-quantity').textContent = 'Quantity of ' + category.toLowerCase();
    document.getElementById('label-pickup-time').textContent = 'Pickup Time';
    document.getElementById('label-pickup-instruction').textContent = 'Your Pickup Instruction for ' + category.toLowerCase();


         // Change image based on category
         const uploadIcon = document.getElementById('upload-icon');
    if (category === 'Food') {
        uploadIcon.src = 'SiteIcons/burger.png'; // Path for food
    } else if (category === 'Item') {
        uploadIcon.src = 'SiteIcons/gift1.png'; // Path for non-food
    }

}


</script>

<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" type="text/css">

<script>
    let selectedFiles = [];
    // Function to display multiple image previews with remove button
    function previewMultipleImages(event) {
        const files = event.target.files;
        const previewContainer = document.getElementById('image-previews');
        previewContainer.innerHTML = ""; // Clear previous previews
        selectedFiles = Array.from(files); // Store selected files in an array

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgWrapper = document.createElement('div');
                imgWrapper.classList.add('img-preview-wrapper');

                const imgElement = document.createElement('img');
                imgElement.src = e.target.result;
                imgElement.classList.add('img-preview');

                // Add remove button for each image
                const removeBtn = document.createElement('button');
                removeBtn.textContent = 'x';
                removeBtn.classList.add('remove-btn');
                removeBtn.onclick = function() {
                    removeImage(index);
                };

                imgWrapper.appendChild(imgElement);
                imgWrapper.appendChild(removeBtn);
                previewContainer.appendChild(imgWrapper);
            };
            reader.readAsDataURL(file);
        });
    }

    // Function to remove a specific image from the preview and array
    function removeImage(index) {
        selectedFiles.splice(index, 1); // Remove the selected file from array
        document.getElementById('food-images').files = new FileListItems(selectedFiles); // Update file input with remaining files
        previewMultipleImages({ target: { files: selectedFiles } }); // Re-preview remaining images
    }

    // Helper function to create a FileList object
    function FileListItems(files) {
        const b = new ClipboardEvent("").clipboardData || new DataTransfer();
        for (let i = 0; i < files.length; i++) {
            b.items.add(files[i]);
        }
        return b.files;
    }



    // Initialize Mapbox map without a default location
    mapboxgl.accessToken = 'pk.eyJ1IjoicGVvcGxlcGxhdGZvcm0iLCJhIjoiY20ybGphZHk0MGNmdzJpcHdrcHVyMzh5ZSJ9.t_fL-Pv4n1zsteW466ksTg';

    let map = new mapboxgl.Map({
        container: 'map', // Map container element ID
        style: 'mapbox://styles/mapbox/streets-v11', // Map style
        center: [0, 0], // Initially set to [0, 0]
        zoom: 15 // World view by default
    });

    // Create a draggable marker, initially not placed on the map
    let marker = new mapboxgl.Marker({
        draggable: true
    });

    // Geocoder for searching locations
    let geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        mapboxgl: mapboxgl,
        marker: false // Prevent default marker
    });

    // Add geocoder search box to the map
    document.getElementById('geocoder').appendChild(geocoder.onAdd(map));

    // When a location is selected from the search box
    geocoder.on('result', function(event) {
        const coordinates = event.result.geometry.coordinates;
        map.setCenter(coordinates);
        marker.setLngLat(coordinates).addTo(map); // Add or move marker to the selected place
        document.getElementById('latitude').value = coordinates[1]; // Set latitude
        document.getElementById('longitude').value = coordinates[0]; // Set longitude
    });

    // Update form fields when the marker is dragged manually
    marker.on('dragend', function() {
        const lngLat = marker.getLngLat();
        document.getElementById('latitude').value = lngLat.lat;
        document.getElementById('longitude').value = lngLat.lng;
    });

    // Function to get the user's current location
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const { latitude, longitude } = position.coords;
                map.setCenter([longitude, latitude]);
                map.setZoom(13); // Zoom in to a closer view
                marker.setLngLat([longitude, latitude]).addTo(map);
                document.getElementById('latitude').value = latitude;
                document.getElementById('longitude').value = longitude;
            }, function(error) {
                alert('Unable to retrieve your location: ' + error.message);
            });
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }
</script>

</body>
</html>
