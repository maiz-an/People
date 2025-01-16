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


// Fetch user details from the session
$id = $_SESSION['id'];

// Fetch user profile picture from the database
$query = "SELECT profile_pic FROM login WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$profile_pic = !empty($user['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : 'default.png';

// Fetch user information from the 'login' table
$user_query = "SELECT id, email, name FROM login WHERE id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param('i', $id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_info = $user_result->fetch_assoc();

// Check if user_info is valid
$name = isset($user_info['name']) ? htmlspecialchars($user_info['name']) : 'Unknown User';
$email = isset($user_info['email']) ? htmlspecialchars($user_info['email']) : 'Unknown Email';

// Fetch user's posted giveaways from the 'free_food' table
// Fetch user's posted giveaways along with image data
$giveaway_query = "
    SELECT free_food.*, MIN(free_food_images.food_image) AS food_image
    FROM free_food
    LEFT JOIN free_food_images ON free_food.id = free_food_images.food_id
    WHERE free_food.user_id = ?
    GROUP BY free_food.id
    ORDER BY free_food.id DESC
    ";

$stmt_giveaway = $conn->prepare($giveaway_query);
$stmt_giveaway->bind_param('i', $id);
$stmt_giveaway->execute();
$giveaway_result = $stmt_giveaway->get_result();

// Fetch user requests and item details using JOIN with 'free_food' and 'free_food_images' tables
$request_query = "
    SELECT notifications.*, free_food.food_title, free_food.description, free_food.quantity,
           free_food.pickup_time, free_food.pickup_instruction, free_food.latitude, 
           free_food.longitude, free_food.expiration_time, free_food.category, free_food.status, 
           free_food_images.food_image, login.name AS poster_name, login.profile_pic AS poster_pic
    FROM notifications
    JOIN free_food ON notifications.food_id = free_food.id
    LEFT JOIN free_food_images ON free_food.id = free_food_images.food_id
    JOIN login ON free_food.user_id = login.id
    WHERE notifications.requester_id = ?
    ORDER BY notifications.id DESC";

$stmt_request = $conn->prepare($request_query);
$stmt_request->bind_param('i', $id);
$stmt_request->execute();
$request_result = $stmt_request->get_result();



// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM employees WHERE id='$delete_id'";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Employee deleted successfully!'); window.location.href = 'org_user_dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error deleting record: " . mysqli_error($conn) . "'); window.location.href = 'org_user_dashboard.php';</script>";
        exit();
    }
}

$sql = "SELECT * FROM employees WHERE organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id); // Use the current organization's user ID ($id)
$stmt->execute();
$result = $stmt->get_result();

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

// Fetch user information from the 'login' table, including location
$user_query = "SELECT id, email, name, latitude, longitude, status, profile_pic FROM login WHERE id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param('i', $id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_info = $user_result->fetch_assoc();

$profile_pic = !empty($user_info['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user_info['profile_pic']) : 'default.png';
$name = isset($user_info['name']) ? htmlspecialchars($user_info['name']) : 'Unknown User';
$email = isset($user_info['email']) ? htmlspecialchars($user_info['email']) : 'Unknown Email';
$status = isset($user_info['status']) ? $user_info['status'] : 'pending'; // Default to 'pending' if status is not found
$latitude = isset($user_info['latitude']) ? $user_info['latitude'] : null;
$longitude = isset($user_info['longitude']) ? $user_info['longitude'] : null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | People: Community Sharing Platform</title>
    <link rel="icon" type="image/png" href="people.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            background-color: #f0f0f0;
            background-image: url('bg3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;

        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: whitesmoke;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .header{
            display: flex;
            padding: 2%;
            align-items: center;
            min-height: 2.8rem;
            max-height: 2rem;
            gap: 10px;
            background-color: #433434;
            border-radius: 45px;
        }
        .back img{
            margin-top: 6px;
            width: 34px;

        }
        .logout-btn img {
            margin-top: 6px;
            margin-left: 10rem;
            width: 38px;
        }
        span{
            color: #ff009a;
        }
        .header h2 {
            margin-left: 2rem;
            text-align: center;
            font-size: 2.4rem;
            color: #06C167;
        }
        .user-info-header{
            display: flex;
            padding: 2%;
            align-items: center;
            gap: 10px;
        }
        .user-info-header a img{
            width: 25px;
            height: 25px;
        }
        .user-info {
            margin-bottom: 20px;
            padding: 15px;
            border-bottom: 1px solid #ccc;
        }
        .user-info img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        .cicle{
            background-color: #1b9df4;
            border-radius: 50%;
            width: 110px;
            height: 110px;
            display: flex;
            justify-content: center;
            align-content: center;
            
        }
        .cicle img{
            margin-top: 4.6%;
            margin-left: 0.3%;
        }
        .verify{
            margin-top: -2.6rem;
            margin-left: 4rem;
        }
        .verify img{
            width: 45px;
            height: 45px;
        }
        p a{
            font-size: 0.7rem;
            text-decoration: none;
        }
        p a:hover{
            text-decoration: underline;
            cursor: pointer;
        }
        .user-info p {
            margin: 5px 0;
        }
        .giveaway-list {
            margin-top: 20px;
        }
        .giveaway-item {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .giveaway-item h3 {
            color: #4f358e;
        }
        .giveaway-item p {
            margin: 5px 0;
        }
        .back-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            margin-top: 20px;
            background-color: #4f358e;
            color: #fff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-btn:hover {
            background-color: #3d276c;
        }
        .toggle-buttons {
            display: flex;
            justify-content: left;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            color: #4f358e;
            cursor: pointer;
            transition: color 0.3s ease;
        }
    

        .toggle-btn:hover {
            color: #3d276c;
        }

        .toggle-btn.active {
            border-bottom: 3px solid #00c065;
            transition: color 0.3s ease;
        }

        .toggle-section {
            display: none;
        }
        /* Request Card Styles
        .request-card {
            display: flex;
            flex-direction: column;
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .request-card-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .request-card-header h3 {
            color: #4f358e;
            margin: 0;
        }

        .request-card-body p {
            margin: 5px 0;
        }

        .request-card-body p strong {
            color: #333;
        }
        .request-card-image {
            flex: 1;
            text-align: center;
        }

        .request-card-image img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            object-fit: cover;
        } */



        /* Grid layout for request cards */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        /* Food item styling */
        .food-item {
            background-color: whitesmoke;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 15px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
            transition: box-shadow 0.3s ease;
        }

        .request-card {
                    flex-direction: column;
                    background-color: #fff;
                    border: 1px solid #e0e0e0;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    padding: 15px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                }
                .request-card-header {
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                .request-card-header h3 {
                    color: #4f358e;
                    margin: 0;
                }

                .request-card-body p {
                    margin: 5px 0;
                }

                .request-card-body p strong {
                    color: #333;
                }
        /* Image styling with fixed aspect ratio */
        .food-item img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Content layout */
        .food-item-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Title and description styling */
        .food-item h3 {
            font-size: 1.4rem;
            color: #4f358e;
            margin-bottom: 5px;
        }

        .food-item p {
            font-size: 1rem;
            color: #555;
        }
        .request-card-image {
            flex: 1;
            text-align: center;
        }

        .request-card-image img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            object-fit: cover;
        }
        .poster-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #555;
        }

        .poster-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
        }
        .edit-icon {
            top: 0;
            margin-left: 21rem;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 1001;
            transition: transform 0.2s ease;
        }

        .edit-icon:hover {
            transform: scale(1.1);
            opacity: 0.8;
        }
        /* Ensure table fits within the container */
table {
    width: 100%;
    table-layout: auto; /* Distribute columns evenly */
    overflow: hidden;
    border-collapse: collapse;
    margin-bottom: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.table-container {
    width: 100%;
    overflow-x: auto; /* Allow horizontal scroll on smaller screens */
    margin-top: 15px;
    border-radius: 8px;
}

/* Adjust column widths and prevent large content from breaking */
table th, table td {
    padding: 12px 10px;
    text-align: left;
    font-size: 0.9rem;
    color: #333;
    border-bottom: 1px solid #ddd;
    white-space: nowrap; /* Prevent text wrapping */
    overflow: hidden;
    text-overflow: ellipsis; /* Add ellipsis for overflowing text */
}

/* Header styling */
table th {
    background-color: #4f358e;
    color: white;
    text-transform: uppercase;
    font-weight: 600;
}

/* Add padding and shadow for overall container */
.manage-members-section {
    padding: 10px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Media query for smaller screens */
@media (max-width: 768px) {
    .table-container {
        overflow-x: auto; /* Allow horizontal scrolling */
        border-radius: 8px;
    }

    table th, table td {
        font-size: 0.8rem;
    }
}/* Manage Members Section Styling */
.manage-members-section {
    padding: 20px;
    background-color: #f9fbfd;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

.manage-members-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.add-member-btn {
    padding: 10px 15px;
    background-color: #4caf50;
    color: white;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: bold;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.add-member-btn:hover {
    background-color: #3e8e41;
}

.search-bar {
    padding: 10px;
    width: 100%;
    max-width: 300px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 0.9rem;
}

.table-container {
    width: 100%;
    overflow-x: auto;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

table th, table td {
    padding: 15px;
    text-align: left;
    font-size: 0.9rem;
    border-bottom: 1px solid #e0e0e0;
}

table th {
    background-color: #0078d7;
    color: white;
    text-transform: uppercase;
    font-weight: bold;
}

table td {
    color: #555;
}

table tr:hover {
    background-color: #f1f1f1;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.edit-btn {
    padding: 8px 12px;
    background-color: #0078d7;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.edit-btn:hover {
    background-color: #005bb5;
}

.delete-btn {
    padding: 8px 12px;
    background-color: #ff4c4c;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.delete-btn:hover {
    background-color: #d32f2f;
}

.employee-photo {
    width: 50px;
    height: 50px;
    border-radius: 5px;
    object-fit: cover;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    table th, table td {
        font-size: 0.8rem;
        padding: 10px;
    }

    .add-member-btn, .search-bar {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .manage-members-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
}



        /* Responsive adjustments for tablets and mobile devices */
        @media (max-width: 768px) {
            .grid {
                display: block;
            }

            .food-item {
                flex-direction: column;
                align-items: center;
            }

            .food-item img {
                width: 100%;
                max-width: 250px;
                height: auto;
                margin-bottom: 10px;
            }
        }

        /* Mobile view adjustments */
@media (max-width: 455px) {
    /* Container styling */
    .container {
        padding: 5px;
        margin: 0;
        width: 100%;
        box-shadow: none;
        border-radius: 5;
    }

    /* Header styling */
    .header {
        gap: 10px;
        padding: 10px;
    }
    .header h2 {
        font-size: 1.175rem;
        font-weight: bolder;
        margin-left: 0;
    }
    .back img{
        width: 26px;
    }
    .logout-btn img {
        width: 30px;
        margin-left: 0rem;
    }

    /* User Info Section */
    .user-info {
        display: flex;
        flex-direction: column;
        align-items: left;
        text-align: left;
    }
    .user-info img {
        width: 100px;
        height: 100px;
        margin-bottom: 10px;
    }
    .verify img{
        margin-top:0.3rem;
        margin-left: 0.1rem;
        width: 40px;
        height: 40px;
    }
    .user-info p {
        font-size: 0.8rem;
    }

    /* Toggle Buttons */
    .toggle-buttons {
        gap: 10px;
        text-align: center;
    }
    .toggle-buttons h2{
        font-size: 0.75rem;
    }
    .toggle-btn {
        font-size: 1rem;
    }

    /* Giveaways and Requests Section */
    .grid {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .food-item,
    .request-card {
        flex-direction: column;
        padding: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .food-item img,
    .request-card-image img {
        width: 100%;
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }

    .food-item-content,
    .request-card-body {
        text-align: center;
    }
    .food-item h3,
    .request-card-header h3 {
        font-size: 1rem;
        margin-bottom: 5px;
    }
    .food-item p,
    .request-card-body p {
        font-size: 0.8rem;
        margin: 5px 0;
    }

    /* Edit Icon */
    .edit-icon {
        width: 20px;
        height: 20px;
        margin-left: 0;
        margin-top: 10px;
    }

    /* Poster Info Section */
    .poster-info {
        justify-content: center;
        gap: 5px;
    }
    .poster-pic {
        width: 30px;
        height: 30px;
    }

    /* Back Button */
    .back-btn {
        padding: 8px;
        font-size: 0.9rem;
        margin-top: 10px;
    }

    /* Location Link Styling */
    .request-card-body a {
        font-size: 0.9rem;
    }
    .manage-members-actions a{
        width: 22.5%;
        height: 18px;
        font-size: 0.7rem;
    }
    table{
        font-size: 0.8rem;
    }
}

</style>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const giveawaysBtn = document.getElementById("giveaways-btn");
    const requestsBtn = document.getElementById("requests-btn");
    const manageMembersBtn = document.getElementById("manage-members-btn");

    const giveawaysSection = document.getElementById("giveaways-section");
    const requestsSection = document.getElementById("requests-section");
    const manageMembersSection = document.getElementById("manage-members-section");

    // Function to activate a specific tab
    function activateTab(tab) {
        if (tab === "giveaways") {
            giveawaysSection.style.display = "block";
            requestsSection.style.display = "none";
            manageMembersSection.style.display = "none";
            giveawaysBtn.classList.add("active");
            requestsBtn.classList.remove("active");
            manageMembersBtn.classList.remove("active");
        } else if (tab === "requests") {
            requestsSection.style.display = "block";
            giveawaysSection.style.display = "none";
            manageMembersSection.style.display = "none";
            requestsBtn.classList.add("active");
            giveawaysBtn.classList.remove("active");
            manageMembersBtn.classList.remove("active");
        } else if (tab === "manageMembers") {
            manageMembersSection.style.display = "block";
            giveawaysSection.style.display = "none";
            requestsSection.style.display = "none";
            manageMembersBtn.classList.add("active");
            giveawaysBtn.classList.remove("active");
            requestsBtn.classList.remove("active");
        }
    }

    // Check localStorage for the active tab, default to "giveaways"
    const savedTab = localStorage.getItem("activeTab") || "giveaways";
    activateTab(savedTab);

    // Event Listeners for each button
    giveawaysBtn.addEventListener("click", () => {
        activateTab("giveaways");
        localStorage.setItem("activeTab", "giveaways"); // Save to localStorage
    });

    requestsBtn.addEventListener("click", () => {
        activateTab("requests");
        localStorage.setItem("activeTab", "requests"); // Save to localStorage
    });

    manageMembersBtn.addEventListener("click", () => {
        activateTab("manageMembers");
        localStorage.setItem("activeTab", "manageMembers"); // Save to localStorage
    });
});

</script>
<script>
        function searchEmployee() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toLowerCase();
            var table = document.querySelector("table tbody");
            var rows = table.getElementsByTagName('tr');

            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? "" : "none";
            }
        }
    </script>

</head>
<body>

<div class="container">
    <div class="header">
        <a href="organizationHome.php" class="back"><img src="SiteIcons/arrow-w.png" alt="arrow-w" ></a>
        <h2>Organization <span> Dashboard </span> </h2>
        <a href="logout.php" class="logout-btn"> <img src="SiteIcons/out.png" alt="logout.png"> </a>
        
        
    </div>

    <!-- User Information -->
    <div class="user-info">
        <div class="user-info-header">
            <h3>Your Information</h3>
            <a href="profile.php"> <img src="SiteIcons/edit-text.png" alt="editing"></a>
        </div>
        <div class="cicle" style="background-color: <?php echo ($status === 'allowed') ? '#1b9df4' : 'darkred'; ?>;">
            <img src="<?php echo $profile_pic; ?>" alt="Profile Picture">
        </div>
        <div class="verify">
            <?php if ($status === 'allowed'): ?>
                <img src="SiteIcons/verified.png" alt="Verified" title="Verified User" >
                <?php endif; ?>
        </div>
            <p style="margin-top: <?php echo ($status === 'allowed') ? '0' : '3.2rem'; ?>;" >
            <strong>Name:</strong> <?php echo $name; ?>
            <?php if ($status === 'pending'): ?>
            <a href="verify_account.php">verify</a>
            <?php endif; ?>
        </p>
        <p><strong>Email:</strong> <?php echo $email; ?></p>
        <!-- New Location Preview -->
         <!-- Add Mapbox Script and Stylesheet -->
<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>

<!-- Map Location Preview with Marker -->
<?php if (!empty($latitude) && !empty($longitude)): ?>
    <div class="loca">
    <p><strong>Location:</strong></p>
    <div id="location-map" style="width: 50%; height: 200px; border: 3px solid #ddd; border-radius: 5px; margin-top: 12px; margin-bottom: 10px; "></div>
    </div>
    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoicGVvcGxlcGxhdGZvcm0iLCJhIjoiY20ybGphZHk0MGNmdzJpcHdrcHVyMzh5ZSJ9.t_fL-Pv4n1zsteW466ksTg';
        const userLat = <?php echo $latitude; ?>;
        const userLng = <?php echo $longitude; ?>;

        // Initialize the map
        const map = new mapboxgl.Map({
            container: 'location-map', // HTML container ID
            style: 'mapbox://styles/mapbox/streets-v11', // Map style
            center: [userLng, userLat], // User's coordinates
            zoom: 14 // Zoom level
        });

        // Add a marker at the user's location
        new mapboxgl.Marker()
            .setLngLat([userLng, userLat]) // Set marker at user's coordinates
            .addTo(map);
    </script>
<?php else: ?>
    <p><strong>Location:</strong> Not set <a href="profile.php" style="font-size: 0.8rem;"> (set here)</a> </p>
<?php endif; ?>

    </div>

    <!-- Toggle Buttons for Giveaways and Requests -->
    <div class="toggle-buttons">
        <h2 id="giveaways-btn" class="toggle-btn active">Your Giveaways</h2>
        <h2 id="requests-btn" class="toggle-btn">Your Requests</h2>
        <h2 id="manage-members-btn" class="toggle-btn">Manage Members</h2>
    </div>


    <!-- User Giveaways Section -->
<div id="giveaways-section" class="toggle-section">
    <h3>Your Giveaways</h3>
    <div class="grid">
        <?php if ($giveaway_result->num_rows > 0): ?>
            <?php while ($item = $giveaway_result->fetch_assoc()): ?>
                <?php
                    $poster_name = htmlspecialchars($name); // User's name from session
                    $poster_pic = !empty($user['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : 'https://via.placeholder.com/30';
                ?>
                <div class="request-card">
                    <div class="request-card-header">
                        <!-- Edit Icon -->
                        <a href="edit_giveaway.php?food_id=<?php echo $item['id']; ?>" class="edit-icon-link">
                                <img src="http://localhost/people/SiteIcons/edit.png" alt="Edit" title="Edit Giveaway" class="edit-icon">
                            </a>
                        <h3><?php echo htmlspecialchars($item['food_title']); ?> <span style="font-size: 12px; color: #333; " >by you</span> </h3>
                        <p><strong>Posted on:</strong> <?php echo date("F d, Y", strtotime($item['created_at'])); ?></p>
                        <div class="request-card-image">
                            <?php if (!empty($item['food_image'])): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($item['food_image']); ?>" alt="Item Image">
                            <?php else: ?>
                                <img src="default-item.png" alt="Default Image">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="request-card-body">
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($item['quantity']); ?></p>
                        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($item['pickup_time']); ?></p>
                        <p><strong>Pickup Instructions:</strong> <?php echo htmlspecialchars($item['pickup_instruction']); ?></p>
                        <p><strong>Untill:</strong> <?php echo htmlspecialchars($item['expiration_time']); ?> </p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($item['status']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
                        <p>
                            <strong>Location:</strong> 
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo htmlspecialchars($item['latitude']); ?>,<?php echo htmlspecialchars($item['longitude']); ?>" target="_blank">
                                Show Map
                            </a>
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You have not posted any giveaways yet.</p>
        <?php endif; ?>
    </div>
</div>


    <!-- User Requests Section -->
<div id="requests-section" class="toggle-section" style="display: none;">
    <h3>Your Requests</h3>
    <div class="grid">
        <?php if ($request_result->num_rows > 0): ?>
            <?php while ($request = $request_result->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-card-header">
                        <h3><?php echo htmlspecialchars($request['food_title']); ?>
                        <span class="poster-info">
                <img src="<?php echo !empty($request['poster_pic']) ? 'data:image/jpeg;base64,' . base64_encode($request['poster_pic']) : 'https://via.placeholder.com/30'; ?>" 
                     alt="<?php echo htmlspecialchars($request['poster_name']); ?>'s profile picture" 
                     class="poster-pic">
                by <?php echo htmlspecialchars($request['poster_name']); ?>
            </span>
                    </h3>
                        <p><strong>Requested on:</strong> <?php echo date("F d, Y", strtotime($request['created_at'])); ?></p>
                        <div class="request-card-image">
                            <?php if (!empty($request['food_image'])): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($request['food_image']); ?>" alt="Item Image">
                            <?php else: ?>
                                <img src="default-item.png" alt="Default Image">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="request-card-body">
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($request['description']); ?></p>
                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($request['quantity']); ?></p>
                        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($request['pickup_time']); ?></p>
                        <p><strong>Pickup Instructions:</strong> <?php echo htmlspecialchars($request['pickup_instruction']); ?></p>
                        <p><strong>Untill:</strong> <?php echo htmlspecialchars($request['expiration_time']); ?> </p>
                        <p><strong>Category:</strong> <?php echo isset($request['category']) ? htmlspecialchars($request['category']) : 'Unknown'; ?></p>
                        <p><strong>Status:</strong> <?php echo isset($request['status']) ? htmlspecialchars($request['status']) : 'Unknown'; ?></p>
                        <p>
                            <strong>Location:</strong> 
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo htmlspecialchars($request['latitude']); ?>,<?php echo htmlspecialchars($request['longitude']); ?>" target="_blank">
                                Show Map
                            </a>
                        </p>
                    </div>
                    
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You have not made any requests yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Manage Members Section -->
<div id="manage-members-section" class="toggle-section" style="display: none;">
<h3>Manage Members</h3>
    <div class="manage-members-actions">
        <a href="add_employee.php" class="add-member-btn">Add Member</a>
        <input type="text" id="searchInput" onkeyup="searchEmployee()" placeholder="Search for Employee.." class="search-bar">
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Post</th>
                    <th>Email</th>
                    <th>NIC/Passport</th>
                    <th>Address</th>
                    <th>Photo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['age']) . "</td>";
            echo "<td>" . htmlspecialchars($row['post']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nic_passport']) . "</td>";
            echo "<td>" . htmlspecialchars($row['address']) . "</td>";
            echo '<td><img src="data:image/jpeg;base64,' . base64_encode($row['photo']) . '" alt="Employee Photo" class="employee-photo"></td>';
            echo "<td class='action-buttons'>
                <a href='edit_employee.php?id=" . $row['id'] . "' class='edit-btn'>Edit</a>
                <form method='post' action='org_user_dashboard.php' style='display:inline-block;'>
                    <input type='hidden' name='delete_id' value='" . $row['id'] . "'>
                    <button type='submit' class='delete-btn'>Delete</button>
                </form>
              </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='9'>No employees found</td></tr>";
    }
    ?>
</tbody>
        </table>
    </div>


</body>
</html>
