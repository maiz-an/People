<?php
// Include database connection
include 'connection.php';

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    echo "User ID not provided.";
    exit;
}

$user_id = intval($_GET['user_id']);

// Fetch user details
$user_query = "SELECT * FROM login WHERE id = $user_id";
$user_result = mysqli_query($connection, $user_query);
if (!$user_result || mysqli_num_rows($user_result) == 0) {
    echo "User not found.";
    exit;
}
$user = mysqli_fetch_assoc($user_result);
$profile_pic = !empty($user['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : 'default.png';

// Check for latitude and longitude
$user_lat = $user['latitude'] ?? null;
$user_lon = $user['longitude'] ?? null;

// Fetch user's posted giveaways
$giveaway_query = "
    SELECT free_food.*, MIN(free_food_images.food_image) AS food_image
    FROM free_food
    LEFT JOIN free_food_images ON free_food.id = free_food_images.food_id
    WHERE free_food.user_id = $user_id
    GROUP BY free_food.id
    ORDER BY free_food.id DESC";
$giveaway_result = mysqli_query($connection, $giveaway_query);

// Fetch user's requests
$request_query = "
    SELECT notifications.*, free_food.*, free_food_images.food_image,
           login.name AS poster_name, login.profile_pic AS poster_pic
    FROM notifications
    JOIN free_food ON notifications.food_id = free_food.id
    LEFT JOIN free_food_images ON free_food.id = free_food_images.food_id
    JOIN login ON free_food.user_id = login.id
    WHERE notifications.requester_id = $user_id
    ORDER BY notifications.id DESC";
$request_result = mysqli_query($connection, $request_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <style>
        /* General Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f3f4f6, #ffffff);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #4f358e;
            margin-bottom: 20px;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* Profile Section */
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 3px solid #4f358e;
        }

        .user-details {
            text-align: center;
            margin-bottom: 20px;
        }

        .user-details p {
            font-size: 1rem;
            color: #555;
            margin: 5px 0;
        }

        .user-details strong {
            color: #4f358e;
        }

        /* Toggle Buttons */
        .toggle-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .toggle-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #4f358e;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-btn.active {
            background-color: #00c065;
        }

        .toggle-btn:hover {
            background-color: #3d276c;
        }

        /* Sections Styling */
        .section {
            display: none;
            margin-top: 30px;
        }

        .section.active {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .section h2 {
            color: #4f358e;
            border-bottom: 2px solid #4f358e;
            padding-bottom: 5px;
            margin-bottom: 15px;
            grid-column: span 2;
        }

        /* Item Cards */
        .item-card {
            display: flex;
            flex-direction: column;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .item-card h3 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .item-card p {
            font-size: 1rem;
            color: #666;
            margin: 5px 0;
        }

        .item-card img {
            max-width: 150px;
            height: 150px;
            border-radius: 8px;
            margin-top: 15px;
            object-fit: cover;
            align-self: center;
        }

        .item-card strong {
            color: #4f358e;
        }
        .header{
            display: flex;
            padding: 2%;
            align-items: center;
            min-height: 3rem;
            max-height: 4rem;
            gap: 10px;
            width: 90%;
            border-radius: 60px;
            gap: 19rem;

        }

        .header img{
            margin-top: 6px;
            width: 40px;

        }

        .header h2 {
            text-align: center;
            color: white;
            font-size: 1.9rem;
            color: #06C167;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .profile-pic {
                width: 100px;
                height: 100px;
            }

            .item-card h3 {
                font-size: 1.2rem;
            }

            .item-card p {
                font-size: 0.9rem;
            }

            .section {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toggleButtons = document.querySelectorAll(".toggle-btn");
            const sections = document.querySelectorAll(".section");

            toggleButtons.forEach((btn, index) => {
                btn.addEventListener("click", () => {
                    toggleButtons.forEach(b => b.classList.remove("active"));
                    sections.forEach(section => section.classList.remove("active"));

                    btn.classList.add("active");
                    sections[index].classList.add("active");
                });
            });

            // Set default active section
            toggleButtons[0].classList.add("active");
            sections[0].classList.add("active");
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="manage_user.php"><img src="SiteIcons/arrow.png" alt=""></a>
        <h1>User Details</h1>
        </div>
        <div class="user-details">
            <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-pic">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            <p><strong>Type:</strong> <?php echo ucfirst($user['user_type'] ?? ''); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($user['status'] ?? ''); ?></p>
            <p><strong>Location:</strong> 
                <?php if ($user_lat && $user_lon): ?>
                    <a href="https://www.google.com/maps?q=<?php echo $user_lat; ?>,<?php echo $user_lon; ?>" target="_blank">
                        View on Map
                    </a>
                <?php else: ?>
                    Location not available
                <?php endif; ?>
            </p>
        </div>

        <!-- Toggle Buttons -->
        <div class="toggle-buttons">
            <button class="toggle-btn">Giveaways</button>
            <button class="toggle-btn">Requests</button>
        </div>

        <!-- User Giveaways Section -->
        <div class="section">
            <h2>Giveaways</h2>
            <?php if (mysqli_num_rows($giveaway_result) > 0): ?>
                <?php while ($giveaway = mysqli_fetch_assoc($giveaway_result)): ?>
                    <div class="item-card">
                        <h3><?php echo htmlspecialchars($giveaway['food_title'] ?? ''); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($giveaway['description'] ?? ''); ?></p>
                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($giveaway['quantity'] ?? ''); ?></p>
                        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($giveaway['pickup_time'] ?? ''); ?></p>
                        <p><strong>Pickup Instruction:</strong> <?php echo htmlspecialchars($giveaway['pickup_instruction'] ?? ''); ?></p>
                        <p><strong>Expiration Time:</strong> <?php echo htmlspecialchars($giveaway['expiration_time'] ?? ''); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($giveaway['status'] ?? ''); ?></p>
                        <p><strong>Category:</strong> <?php echo ucfirst($giveaway['category'] ?? ''); ?></p>
                        <p><strong>Report Category:</strong> <?php echo htmlspecialchars($giveaway['report_category'] ?? ''); ?></p>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($giveaway['food_image'] ?? ''); ?>" alt="Giveaway Image">
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No giveaways found for this user.</p>
            <?php endif; ?>
        </div>
        <!-- User Requests Section -->
        <div class="section">
            <h2>Requests</h2>
            <?php if (mysqli_num_rows($request_result) > 0): ?>
                <?php while ($request = mysqli_fetch_assoc($request_result)): ?>
                    <div class="item-card">
                        <h3><?php echo htmlspecialchars($request['food_title'] ?? ''); ?></h3>
                        <p><strong>Requested By:</strong> <?php echo htmlspecialchars($request['poster_name'] ?? ''); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($request['description'] ?? ''); ?></p>
                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($request['quantity'] ?? ''); ?></p>
                        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($request['pickup_time'] ?? ''); ?></p>
                        <p><strong>Pickup Instruction:</strong> <?php echo htmlspecialchars($request['pickup_instruction'] ?? ''); ?></p>
                        <p><strong>Expiration Time:</strong> <?php echo htmlspecialchars($request['expiration_time'] ?? ''); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($request['status'] ?? ''); ?></p>
                        <p><strong>Category:</strong> <?php echo ucfirst($request['category'] ?? ''); ?></p>
                        <p><strong>Report Category:</strong> <?php echo htmlspecialchars($request['report_category'] ?? ''); ?></p>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($request['food_image'] ?? ''); ?>" alt="Request Image">
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No requests found for this user.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
mysqli_close($connection);
?>
