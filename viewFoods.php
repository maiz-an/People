<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo"; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all food items
$sql = "SELECT * FROM free_food ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Grid View</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            grid-gap: 20px;
        }
        .food-item {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .food-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }
        h2 {
            text-align: center;
            color: #4f358e;
            margin-bottom: 30px;
        }
        .food-title {
            font-weight: 600;
            margin: 10px 0;
            color: #333;
        }
        .food-description {
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #4f358e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Available Free Food</h2>
    <div class="grid">

    <?php
    // Check if there are results
    if ($result->num_rows > 0) {
        // Loop through all food items
        while ($row = $result->fetch_assoc()) {
            $food_id = $row['id'];
            $food_title = htmlspecialchars($row['food_title']);
            $description = htmlspecialchars($row['description']);
            $quantity = htmlspecialchars($row['quantity']);
            $pickup_time = htmlspecialchars($row['pickup_time']);
            $show_up_duration = htmlspecialchars($row['show_up_duration']);

            // Query to get the first image of the food
            $img_sql = "SELECT food_image, image_type FROM free_food_images WHERE food_id = $food_id LIMIT 1";
            $img_result = $conn->query($img_sql);
            $img_data = $img_result->fetch_assoc();

            if ($img_data) {
                // Convert image blob to base64 to display as an image
                $image_src = 'data:' . $img_data['image_type'] . ';base64,' . base64_encode($img_data['food_image']);
            } else {
                // Placeholder image if no image found
                $image_src = 'https://via.placeholder.com/300x200?text=No+Image';
            }

            // Display each food item
            echo "
            <div class='food-item'>
                <img src='$image_src' alt='$food_title'>
                <h3 class='food-title'>$food_title</h3>
                <p class='food-description'>$description</p>
                <p><strong>Quantity:</strong> $quantity</p>
                <p><strong>Pickup Time:</strong> $pickup_time</p>
                <p><strong>Visible For:</strong> $show_up_duration</p>
               <a href='/people/Forms/view_item.php?food_id=<?php echo $food_id; ?>' class='btn'>View Details</a>


            </div>";
        }
    } else {
        echo "<p>No food items found.</p>";
    }

    // Close the connection
    $conn->close();
    ?>

    </div>
</div>

</body>
</html>
