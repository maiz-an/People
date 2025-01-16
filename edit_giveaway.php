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

// Check if the user is logged in and either a regular or organization user
if (!isset($_SESSION['id']) || !in_array($_SESSION['user_type'], ['regular', 'organization'])) {
    header("Location: index.php");
    exit();
}
// Determine the back button URL based on the user type
$user_id = $_SESSION['id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'organization') {
    $back_url = "org_user_dashboard";
} else {
    $back_url = "user_dashboard";
}
$id = $_SESSION['id'];
$food_id = $_GET['food_id'] ?? null;

// Fetch the giveaway details
if ($food_id) {
    $query = "
        SELECT free_food.*, free_food_images.food_image
        FROM free_food
        LEFT JOIN free_food_images ON free_food.id = free_food_images.food_id
        WHERE free_food.id = ? AND free_food.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $food_id, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $giveaway_images = [];

    $giveaway = null;
while ($row = $result->fetch_assoc()) {
    // Assign the first row to $giveaway if not already set
    if (!$giveaway) {
        $giveaway = $row;
    }
    $giveaway_images[] = $row['food_image'];
}

// Check if $giveaway is still null
if (!$giveaway) {
    echo "Giveaway not found or you are not authorized to edit this item.";
    exit();
}

}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $pickup_time = $_POST['pickup_time'];
    $pickup_instruction = $_POST['pickup_instruction'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Update the giveaway details
    $update_query = "
        UPDATE free_food
        SET food_title = ?, description = ?, quantity = ?, pickup_time = ?, pickup_instruction = ?, latitude = ?, longitude = ?
        WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ssissddii', $title, $description, $quantity, $pickup_time, $pickup_instruction,  $latitude, $longitude, $food_id, $id);
    $stmt->execute();

    // Handle delete action
if (isset($_POST['delete_giveaway'])) {
    $food_id = $_POST['food_id'] ?? null;

    if ($food_id) {
        // Delete the giveaway and associated images
        $delete_image_query = "DELETE FROM free_food_images WHERE food_id = ?";
        $stmt = $conn->prepare($delete_image_query);
        $stmt->bind_param('i', $food_id);
        $stmt->execute();

        $delete_giveaway_query = "DELETE FROM free_food WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_giveaway_query);
        $stmt->bind_param('ii', $food_id, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Giveaway deleted successfully!";
        } else {
            echo "Failed to delete the giveaway. Please try again.";
        }

        // Redirect back to the user dashboard after deletion
        header("Location: $back_url");
        exit();
    } else {
        echo "Error: Food ID is missing.";
        exit();
    }
}

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $pickup_time = $_POST['pickup_time'];
    $pickup_instruction = $_POST['pickup_instruction'];

    // Update the giveaway details
    $update_query = "
        UPDATE free_food
        SET food_title = ?, description = ?, quantity = ?, pickup_time = ?, pickup_instruction = ?
        WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ssissii', $title, $description, $quantity, $pickup_time, $pickup_instruction, $food_id, $id);
    $stmt->execute();

    // Check if a new image is uploaded
    if (!empty($_FILES['food_image']['tmp_name'])) {
        $image_data = file_get_contents($_FILES['food_image']['tmp_name']);
        $image_type = $_FILES['food_image']['type'];

        // Update the image in the database
        $image_query = "
            REPLACE INTO free_food_images (food_id, food_image, image_type)
            VALUES (?, ?, ?)";
        $stmt = $conn->prepare($image_query);
        $stmt->bind_param('isb', $food_id, $image_data, $image_type);
        $stmt->execute();
    }

    echo "Giveaway updated successfully!";
    header("Location: $back_url");
    exit();
}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Giveaway</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #4f358e;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .form-group input[type="file"] {
            padding: 5px;
        }
        .submit-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #4f358e;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #3d276c;
        }
        .back-btn {
            display: block;
            margin-top: 10px;
            text-align: center;
            color: #4f358e;
            text-decoration: none;
        }
        .preview-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Giveaway</h2>
    <form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="food_id" value="<?php echo $food_id; ?>">

    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($giveaway['food_title']); ?>" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($giveaway['description']); ?></textarea>
    </div>
    <div class="form-group">
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($giveaway['quantity']); ?>" required>
    </div>
    <div class="form-group">
        <label for="pickup_time">Pickup Time</label>
        <input type="text" id="pickup_time" name="pickup_time" value="<?php echo htmlspecialchars($giveaway['pickup_time']); ?>" required>
    </div>
    <div class="form-group">
        <label for="pickup_instruction">Pickup Instructions</label>
        <textarea id="pickup_instruction" name="pickup_instruction" required><?php echo htmlspecialchars($giveaway['pickup_instruction']); ?></textarea>
    </div>
    <div class="form-group">
        <label for="food_image">Update Image (optional)</label>
        <input type="file" id="food_image" name="food_image">
        <?php if (!empty($giveaway_images)): ?>
            <?php foreach ($giveaway_images as $image): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($image); ?>" alt="Current Image" class="preview-image">
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="location">Location</label>
        <div id="map" style="height: 300px; border: 1px solid #ccc; border-radius: 5px;"></div>
        <small>Drag the marker or search for a new location.</small>
    </div>

    <!-- Hidden inputs to store latitude and longitude -->
    <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($giveaway['latitude']); ?>">
    <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($giveaway['longitude']); ?>">


    <button type="submit" class="submit-btn">Update Giveaway</button>
    <button type="submit" name="delete_giveaway" class="submit-btn" style="background-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this giveaway?');">Delete Giveaway</button>
</form>
<div>
    <a href="<?php echo $back_url; ?>" class="back-btn">Back to Dashboard</a>
</div>

</body>
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css">
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css">

<script>
    mapboxgl.accessToken = 'pk.eyJ1IjoicGVvcGxlcGxhdGZvcm0iLCJhIjoiY20ybGphZHk0MGNmdzJpcHdrcHVyMzh5ZSJ9.t_fL-Pv4n1zsteW466ksTg'; // Replace with your Mapbox access token

    // Initialize the map
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [<?php echo htmlspecialchars($giveaway['longitude'] ?? 0); ?>, <?php echo htmlspecialchars($giveaway['latitude'] ?? 0); ?>],
        zoom: 15
    });

    // Add a draggable marker
    const marker = new mapboxgl.Marker({ draggable: true })
        .setLngLat([<?php echo htmlspecialchars($giveaway['longitude'] ?? 0); ?>, <?php echo htmlspecialchars($giveaway['latitude'] ?? 0); ?>])
        .addTo(map);

    // Update hidden input values when the marker is dragged
    marker.on('dragend', () => {
        const lngLat = marker.getLngLat();
        document.getElementById('latitude').value = lngLat.lat;
        document.getElementById('longitude').value = lngLat.lng;
    });

    // Add a search box
    const geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        mapboxgl: mapboxgl
    });
    map.addControl(geocoder);

    // When a new location is selected, update the marker position and input values
    geocoder.on('result', (event) => {
        const coordinates = event.result.geometry.coordinates;
        marker.setLngLat(coordinates);
        map.setCenter(coordinates);
        document.getElementById('latitude').value = coordinates[1];
        document.getElementById('longitude').value = coordinates[0];
    });
</script>

</html>
