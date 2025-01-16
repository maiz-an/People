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

// Get employee ID from query string
$employee_id = $_GET['id'];

// Fetch employee details
$sql = "SELECT * FROM employees WHERE id='$employee_id'";
$result = mysqli_query($conn, $sql);
$employee = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $post = $_POST['post'];
    $email = $_POST['email'];
    $nic_passport = $_POST['nic_passport'];
    $address = $_POST['address'];

    if ($_FILES['photo']['tmp_name']) {
        $photo = addslashes(file_get_contents($_FILES['photo']['tmp_name']));
        $sql = "UPDATE employees SET name='$name', age='$age', post='$post', email='$email', nic_passport='$nic_passport', address='$address', photo='$photo' WHERE id='$employee_id'";
    } else {
        $sql = "UPDATE employees SET name='$name', age='$age', post='$post', email='$email', nic_passport='$nic_passport', address='$address' WHERE id='$employee_id'";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Employee updated successfully!'); window.location.href = 'org_user_dashboard.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>

    <link rel="stylesheet" href="style/admin_manage_users.css">
    <link rel="stylesheet" href="style/floating_contact_button.css">
    <link rel="stylesheet" href="style/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style/h1.css">

    <style>
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #4CAF50; 
    --primary-hover: #45a049;
    --text-color: #333; 
    --form-bg-color: #fff; 
    --border-color: #ddd;
    --background-color: #f5f5f5;
}

/* Body Styling */
body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--background-color);
    color: var(--text-color);
    line-height: 1.6;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* Form Container */
.form-container {
    background-color: var(--form-bg-color);
    width: 100%;
    max-width: 600px;
    margin: 20px auto;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-color);
}

.form-container h2 {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 20px;
    font-size: 1.8rem;
    font-weight: bold;
}

/* Form Group Styling */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 1rem;
    margin-bottom: 8px;
    color: var(--text-color);
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 15px;
    font-size: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    background-color: #f9f9f9;
    outline: none;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    background-color: #fff;
}

textarea {
    resize: none;
}

.form-group button {
    display: block;
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.form-group button:hover {
    background-color: var(--primary-hover);
}

/* Image Section */
.img {
    margin-top: 10px;
    text-align: center;
}

.img img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-container {
        padding: 20px;
    }

    .form-container h2 {
        font-size: 1.5rem;
    }

    .form-group button {
        font-size: 0.9rem;
        padding: 10px;
    }
}

    </style>
    
</head>

<body>

    <div class="form-container">
        <h2>Edit Employee</h2>
        <form method="post" action="edit_employee.php?id=<?php echo $employee_id; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $employee['name']; ?>" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" value="<?php echo $employee['age']; ?>" required>
            </div>
            <div class="form-group">
                <label for="post">Post:</label>
                <input type="text" id="post" name="post" value="<?php echo $employee['post']; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $employee['email']; ?>" required>
            </div>
            <div class="form-group">
                <label for="nic_passport">NIC/Passport:</label>
                <input type="text" id="nic_passport" name="nic_passport" value="<?php echo $employee['nic_passport']; ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" rows="3" required><?php echo $employee['address']; ?></textarea>
            </div>
            <div class="form-group">
                <label for="photo">Photo:</label>
                <input type="file" id="photo" name="photo" accept="image/*">
                <p>Current Photo:</p>
                <div class="img">
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($employee['photo']); ?>" alt="Employee Photo">
                </div>
            </div>
            <div class="form-group">
                <button type="submit">Update Employee</button>
            </div>
        </form>
    </div>
</body>

</html>