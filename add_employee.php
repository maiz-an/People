<?php
// Start the session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in and is an organization
if (!isset($_SESSION['id']) || $_SESSION['user_type'] !== 'organization') {
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

// Get the organization's ID from the session
$organization_id = $_SESSION['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $post = $_POST['post'];
    $email = $_POST['email'];
    $nic_passport = $_POST['nic_passport'];
    $address = $_POST['address'];
    $photo = addslashes(file_get_contents($_FILES['photo']['tmp_name']));

    // Insert the employee with the organization's ID
    $sql = "INSERT INTO employees (name, age, post, email, nic_passport, address, photo, organization_id) 
            VALUES ('$name', '$age', '$post', '$email', '$nic_passport', '$address', '$photo', '$organization_id')";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Employee added successfully!'); window.location.href = 'org_user_dashboard.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f0f0;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group button {
            width: 100%;
            padding: 10px;
            border: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: #45a049;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007BFF;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<script>
        function validateForm() {
            var username = document.getElementById('username').value;
            var email = document.getElementById('email').value;
            var password = document.getElementById('password').value;

            if (nic_passport.length < 11) {
                alert("Enter valid NIC/Passport");
                return false;
            }
            return true;
        }
    </script>
<body>
    <div class="container">
    <a href="org_user_dashboard.php" class="cart-view">Back</a>
        <h2>Add Employee</h2>
        <form method="post" action="add_employee.php" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="name">Employee Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="age">Employee Age:</label>
                <input type="number" id="age" name="age" min="18" max="45" required>
            </div>
            <div class="form-group">
                <label for="post">Employee Post:</label>
                <input type="text" id="post" name="post" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="nic_passport">NIC/Passport:</label>
                <input type="text" id="nic_passport" name="nic_passport" min="1" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" placeholder="Enter Employee Address and Phone Number" required></textarea>
            </div>
            <div class="form-group">
                <label for="photo">Photo:</label>
                <input type="file" id="photo" name="photo" accept="image/*" required>
            </div>
            <div class="form-group">
                <button type="submit">Add Employee</button>
            </div>
        </form>
        <a href="org_user_dashboard" class="back-link">Back to Manage Members</a>
    </div>
</body>

</html>
