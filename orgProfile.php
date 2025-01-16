<?php
session_start();
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

// Check if the user is logged in as an organization
if (!isset($_SESSION['id']) || $_SESSION['user_type'] !== 'organization') {
    header("Location: index.php"); // Redirect to login page if not logged in as an organization
    exit();
}

// Fetch organization details from the database using the session 'id'
$org_id = $_SESSION['id'];
$query = "SELECT * FROM organization WHERE org_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param('i', $org_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Organization not found.");
}
$organization = $result->fetch_assoc();

// Convert the profile picture from the database (BLOB) to a base64-encoded image for preview
$profile_pic = 'default.png'; // Use a default image if there's no profile picture
if (!empty($organization['profile_pic'])) {
    $mime_type = 'image/jpeg'; // Default MIME type (adjust if needed)
    $profile_pic = 'data:' . $mime_type . ';base64,' . base64_encode($organization['profile_pic']);
}

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $org_name = $_POST['org_name'];
    $org_type = $_POST['org_type'];
    $org_registration = $_POST['org_registration'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $website = $_POST['website'];
    $address = $_POST['address'];
    $services = $_POST['services'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $organization['org_password'];
    
    // Handle file upload (profile picture)
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
        $profile_pic_data = file_get_contents($_FILES['profile_pic']['tmp_name']);
        
        // The correct update query for updating the profile picture
        $update_query = "UPDATE organization SET org_name = ?, org_type = ?, org_registration = ?, email = ?, phone = ?, website = ?, address = ?, services = ?, org_password = ?, profile_pic = ? WHERE org_id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param('ssssssssssi', $org_name, $org_type, $org_registration, $email, $phone, $website, $address, $services, $password, $profile_pic_data, $org_id);
    } else {
        // Update without changing profile picture
        $update_query = "UPDATE organization SET org_name = ?, org_type = ?, org_registration = ?, email = ?, phone = ?, website = ?, address = ?, services = ?, org_password = ? WHERE org_id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param('sssssssssi', $org_name, $org_type, $org_registration, $email, $phone, $website, $address, $services, $password, $org_id);
    }
    
    if ($stmt->execute()) {
        // Update session with the new name and email
        $_SESSION['name'] = $org_name;
        $_SESSION['email'] = $email;

        header("Location: organization_dashboard.php"); // Redirect after update
        exit();
    } else {
        echo "Error updating profile: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Organization Profile |  People: Community Sharing Platform</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
    body {
        font-family: 'Poppins', sans-serif;
        line-height: 1.6;
        background-image: url('bg3.jpg');
            background-size: cover; 
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; 
           
    }

    .form-container {
        background-color: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 500px;
        margin: 50px auto;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        text-align: center;
        transition: transform 0.5s ease, opacity 0.5s ease;
    }

    .floating-contact{
        display: none;
    }

    h2 {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: #06C167;
    }

    input[type="text"], input[type="email"], input[type="password"], textarea {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }

    label {
            display: block;
            text-align: left;
            margin-bottom: 5px; 
            font-weight: bold; 
        }

    input[type="file"] {
        display: none; 
    }

    button {
        background-color: #4f358e;
        color: white;
        border: none;
        margin-bottom: 10px;
        margin-top: 10px;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #4f358e;
        transform: scale(1.05);
    }

    /* Profile Picture Styles */
    .profile-preview {
        position: relative;
        display: inline-block;
        margin-top: 30px;
        
    }

    .profile-preview img {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 7px pink solid;
        cursor: pointer; 
    }

    .upload {
        position: absolute;
        top: 62%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color:none; 
        padding: 10px;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .upload img {
        width: 15px;
        height: 15px;
        border: none;
        background: none;
        background-color: none;
    }


    /* Button Styles for back, logout, and upload */
    .btn-container {
            position: absolute;
            top: 10%;  
            left: 27.95%; 
            display: flex;
            gap: 35.57rem;
        }

        .btn-container a {
            background-color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .btn-container a:hover {
            transform: scale(1.1);
        }

        .btn-container a img {
            width: 30px;
            height: 30px;
        }
        span{
            color: #ff009a;
        }
        .upload img{
            width: 60px;
            height: auto;
        }

    @media (max-width: 435px) {
            /* Form Container */
        .form-container {
            position: relative;
            width: 85%; 
            padding: 10px;
        }
        h2 {
            margin-top: 25px;
            font-size: 1.2rem; 
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            font-size: 0.8rem; 
            padding: 6px; 
            width: 95%;
        }
        textarea[type="address"]{
            font-size: 0.8rem; 
            padding: 6px; 
            width: 95%;
        }
        /* Button Styles for back, logout, and upload */
        .btn-container {
            position: static;
            margin-top: -72.9rem;  
            margin-left:  20.9rem; 
            z-index: 9999;
        }

        .btn-container a {
            background-color: white;
            border-radius: 50%;
            width: 0;
            height:0;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            z-index: 9999;
        }

        .btn-container a:hover {
            transform: scale(1.1);
        }

        .btn-container a img {
            width: 30px;
            height: 30px;
        }
        .upload img{
            width: 60px;
            z-index: 1000;
            height: auto;
        }
        .btn-container .back-btn{
            display: none;
        }
        /* Floating buttons position adjustments */
        .floating-contact {
            position: relative;
            top: 10px; 
            left: 30px;
            z-index: 9999; 
            display: inline-block;
        }
        .floating-contact .back-btn img {
            width: 32px; 
            height: 32px;
            z-index: 9999; 
        }
        .back-btn {
            top: 15px;
            left: 10px; 
            width: 3rem;
            height: 3rem;
            z-index: 9999; 
        }
        .back-btn img{
            z-index: 9999; 
            top: 15px;
            left: 10px;
        }
        .profile-preview img {
            margin-top: -25px;
            width: 100px; 
            height: 100px;
        }
        .upload {
            position: absolute;
            top: 53.99%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color:none; 
            padding: 10px;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .upload img {
            width: 33px;
            height: 33px;
            border: none;
            background: none;
            background-color: none;
        }
    }

</style>

</head>
<body>

<div class="form-container">
    <h2>Organization <span >Profile</span></h2>
    <div class="profile-preview">
    <img id="profileImage" src="<?php echo $profile_pic; ?>" alt="Profile Picture" title="Click to change profile picture">
    <div class="upload">
        <img style="width: 50px;" id="profileImage" src="SiteIcons/upload.png" alt="Upload">
    </div>
    <p>Profile Picture</p>
</div>


    <form action="orgProfile.php" method="POST" enctype="multipart/form-data">
        <label for="org_name">Organization Name:</label>
        <input type="text" name="org_name" value="<?php echo htmlspecialchars($organization['org_name']); ?>" required>

        <label for="org_type">Organization Type:</label>
        <input type="text" name="org_type" value="<?php echo htmlspecialchars($organization['org_type']); ?>" required>

        <label for="org_registration">Registration Number:</label>
        <input type="text" name="org_registration" value="<?php echo htmlspecialchars($organization['org_registration']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($organization['email']); ?>" required>

        <label for="phone">Phone:</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($organization['phone']); ?>" required>

        <label for="website">Website:</label>
        <input type="text" name="website" value="<?php echo htmlspecialchars($organization['website']); ?>">

        <label for="address">Address:</label>
        <textarea name="address" required><?php echo htmlspecialchars($organization['address']); ?></textarea>

        <label for="services">Services:</label>
        <textarea name="services"  required><?php echo htmlspecialchars($organization['services']); ?></textarea>

        <label for="password">Password:</label>
        <input type="password" name="password" placeholder="Enter new password (leave blank if unchanged)">

        <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">

        <button type="submit">Update <span style="color: yellowgreen;">Profile</span></button>
    </form>
</div>

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

</body>
<!-- Floating Back, Logout, and Upload buttons -->
<div class="btn-container">
    <a href="organization_dashboard.php" class="back-btn">
        <img src="SiteIcons/left.png" alt="Back">
    </a>
    <a href="logout.php" class="logout-btn">
        <img src="SiteIcons/logout.png" alt="Logout">
    </a>
    
</div>

<!--  back Button -->
<div class="floating-contact">
            <a href="organization_dashboard.php" class="back-btn">
                <img src="SiteIcons/left.png" alt="back"></a>
    </div>

</html>
