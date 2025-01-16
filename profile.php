<?php
session_start();
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];

    // Display the message
    echo "<div class='message-box {$message_type}'>{$message}</div>";

    // Clear the message after displaying it
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

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



if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['id'];
$user_type = $_SESSION['user_type'];

// Determine the back button URL based on the user type
if ($user_type === 'organization') {
    $back_url = "org_user_dashboard.php";
} else {
    $back_url = "user_dashboard.php";
}
// Fetch user details from the database using the session 'id'
$id = $_SESSION['id'];
$query = "SELECT * FROM login WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();

// Convert the profile picture from the database (BLOB) to a base64-encoded image for preview
if (!empty($user['profile_pic'])) {
    $profile_pic = 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']);
} else {
    // Default image if no profile picture is available
    $profile_pic = 'default.png'; // Ensure this file exists in your project directory
}

// Handle profile update form submission
// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password'];

    // Handle file upload (profile picture)
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
        $profile_pic_data = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $update_query = "UPDATE login SET name = ?, email = ?, password = ?, profile_pic = ?, latitude = ?, longitude = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param('sssbddi', $name, $email, $password, $null, $latitude, $longitude, $id);
        $stmt->send_long_data(3, $profile_pic_data);
    } else {
        // Update without changing profile picture
        $update_query = "UPDATE login SET name = ?, email = ?, password = ?, latitude = ?, longitude = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param('sssddi', $name, $email, $password, $latitude, $longitude, $id);
    }
    
    if ($stmt->execute()) {
        // Update session with the new email and name
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
    
        // Set a success message in the session
        $_SESSION['message'] = "Profile updated successfully.";
        $_SESSION['message_type'] = "success";
    
        header("Location: profile.php"); // Redirect back to the profile page
        exit();
    } else {
        // Set an error message in the session
        $_SESSION['message'] = "Failed to update profile. Please try again.";
        $_SESSION['message_type'] = "error";
    
        header("Location: profile.php"); // Redirect back to the profile page
        exit();
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | People: Community Sharing Platform</title>
    <link rel="icon" type="image/png" href="people.png">
    <style>
               /* Message Box Styles */
               .message-box {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            margin: 0;
            border-radius: 5px;
            font-size: 1rem;
            text-align: center;
            z-index: 9999;
            max-width: 90%;
            width: auto;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        /* Success Message */
        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Error Message */
        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

            /* Updated CSS */
            body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('bg3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .form-container {
            background: white;
            width: 100%;
            max-width: 500px;
            margin: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .form-container h2 {
            font-size: 1.8rem;
            color: #06C167;

            text-align: center;
            margin-bottom: 15px;
        }

        .form-container label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="password"],
        .form-container input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-container input[type="file"] {
            display: none;
        }

        .profile-preview {
            text-align: center;
            margin-bottom: 15px;
        }

        .profile-preview img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #4f358e;
            cursor: pointer;
        }

        #map {
            width: 100%;
            height: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .dd {
            background: #4f358e;
            color: white;
            border: none;
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .dd:hover {
            background: #372769;
        }

        .floating-contact {
            position: fixed;
            bottom: 20px;
            left: 20px;
        }

        .floating-contact img {
            width: 50px;
            height: 50px;
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


    </style>
</head>
<body>
<div class="form-container">
        
        <div class="header">
    <a href="<?php echo $back_url; ?>"><img src="SiteIcons/arrow-w.png" alt="back"></a>
    <h2 style="margin-left: 15px; font-size: 1.6rem;">Edit <span style="color: #ff009a;">Profile</span></h2>
    </div>
    <br>

        <!-- Profile Picture Preview -->
        <div class="profile-preview">
            <img id="profileImage" src="<?php echo $profile_pic; ?>" alt="Profile Picture" title="Click to change profile picture">
            <p>Click to change your profile picture</p>
        </div>

        <!-- Profile Update Form -->
        <form action="profile.php" method="POST" enctype="multipart/form-data">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter new password (leave blank if unchanged)">

            <label for="location">Your Location:</label>
        <div id="geocoder" class="geocoder"></div>
        <br>
        <div class="map-buttons">
            <button type="button" onclick="getCurrentLocation()">Use My Current Location</button>
            <button type="button" onclick="clearMarker()">Clear Marker</button>
        </div>
        <br>
        <div id="map"></div>
        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($user['latitude'] ?? ''); ?>">
        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($user['longitude'] ?? ''); ?>">
            <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">
            <button type="submit" class="dd">Update Profile</button>
        </form>
    </div>


<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" />

<script>
mapboxgl.accessToken = 'pk.eyJ1IjoicGVvcGxlcGxhdGZvcm0iLCJhIjoiY20ybGphZHk0MGNmdzJpcHdrcHVyMzh5ZSJ9.t_fL-Pv4n1zsteW466ksTg';

const userLat = <?php echo $user['latitude'] ?? 7.8731; ?>;
const userLng = <?php echo $user['longitude'] ?? 80.7718; ?>;

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: [userLng, userLat],
    zoom: 13
});

// Initialize marker and add to map if coordinates are valid
let marker = new mapboxgl.Marker({ draggable: true });

if (userLat && userLng) {
    marker.setLngLat([userLng, userLat]).addTo(map);
}

// Update hidden fields when marker is dragged
marker.on('dragend', () => {
    const lngLat = marker.getLngLat();
    document.getElementById('latitude').value = lngLat.lat;
    document.getElementById('longitude').value = lngLat.lng;
});

// Geocoder for searching locations
const geocoder = new MapboxGeocoder({
    accessToken: mapboxgl.accessToken,
    mapboxgl: mapboxgl,
    marker: false
});

// Add geocoder to map and handle location selection
geocoder.on('result', (event) => {
    const coordinates = event.result.geometry.coordinates;
    map.setCenter(coordinates);
    marker.setLngLat(coordinates).addTo(map);
    document.getElementById('latitude').value = coordinates[1];
    document.getElementById('longitude').value = coordinates[0];
});

document.getElementById('geocoder').appendChild(geocoder.onAdd(map));

// Get current location and update map
function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            map.setCenter([longitude, latitude]);
            marker.setLngLat([longitude, latitude]).addTo(map);
            document.getElementById('latitude').value = latitude;
            document.getElementById('longitude').value = longitude;
        }, error => {
            alert('Unable to get current location: ' + error.message);
        });
    } else {
        alert('Geolocation is not supported by your browser.');
    }
}

// Clear marker and reset fields
function clearMarker() {
    marker.remove();
    document.getElementById('latitude').value = '';
    document.getElementById('longitude').value = '';
}

</script>
    <script>
        // Get the image element and the hidden file input
        const profileImage = document.getElementById('profileImage');
        const profilePicInput = document.getElementById('profilePicInput');

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
    </script>

    <!-- JavaScript to hide the message box after 1 second -->
<script>
    // Wait for the page to load
    window.addEventListener('DOMContentLoaded', () => {
        const messageBox = document.querySelector('.message-box');

        if (messageBox) {
            // Hide the message box after 1 second
            setTimeout(() => {
                messageBox.style.opacity = '0';
            }, 1000);

            // Remove the message box from the DOM after the fade-out animation
            setTimeout(() => {
                messageBox.remove();
            }, 15000); // Wait an additional 0.5s for the fade-out effect
        }
    });
</script>
</body>
       
<!--  back Button -->
<div class="floating-contact" style="display: none;">
            <a href="<?php echo $back_url; ?>" class="back-btn">
                <img src="SiteIcons/left.png" alt="back"></a>
    </div>

    <!-- upload Button -->
<div class="floating-contact" style="display: none;">
            <a href="" id="profileImage" class="upload-btn">
                <img src="SiteIcons/upload.png" alt="logout"></a>
    </div>
</html>
