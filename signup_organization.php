<?php
// Connect to the database
$connection = mysqli_connect('localhost', 'root', '', 'demo');
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

$msg = ""; // Message to display to the user
$success = false; // Indicates whether account creation was successful

if (isset($_POST['signup_organization'])) {
    // Retrieve and sanitize inputs
    $org_name = mysqli_real_escape_string($connection, $_POST['org_name']);
    $org_email = mysqli_real_escape_string($connection, $_POST['org_email']);
    $org_password = mysqli_real_escape_string($connection, $_POST['org_password']);
    $org_confirm_password = mysqli_real_escape_string($connection, $_POST['org_confirm_password']);

    // Handle profile picture
    $profile_pic = null;
    if (isset($_FILES['profile_pic']['tmp_name']) && $_FILES['profile_pic']['tmp_name']) {
        $profile_pic = file_get_contents($_FILES['profile_pic']['tmp_name']);
    } else {
        $default_image_path = 'SiteIcons/default_org.png';
        $profile_pic = file_get_contents($default_image_path);
    }

    if (!empty($org_name) && !empty($org_email) && !empty($org_password) && !empty($org_confirm_password)) {
        if ($org_password !== $org_confirm_password) {
            $msg = "Passwords do not match.";
        } else {
            $check_email_query = "SELECT * FROM login WHERE email='$org_email'";
            $result = mysqli_query($connection, $check_email_query);

            if (mysqli_num_rows($result) > 0) {
                $msg = "An account with this email already exists.";
            } else {
                $hashed_password = password_hash($org_password, PASSWORD_DEFAULT);

                // Insert into database with gender set to 'organization'
                $insert_query = "INSERT INTO login (name, email, password, user_type, gender, profile_pic) 
                                 VALUES (?, ?, ?, 'organization', 'organization', ?)";
                $stmt = mysqli_prepare($connection, $insert_query);
                mysqli_stmt_bind_param($stmt, 'ssss', $org_name, $org_email, $hashed_password, $profile_pic);

                if (mysqli_stmt_execute($stmt)) {
                    // Redirect to login page with success message
                    echo "<script>
                            window.location.href = 'index.php?success=1';
                            setTimeout(() => { window.close(); }, 1000);
                          </script>";
                    exit();
                } else {
                    $msg = "Error creating account: " . mysqli_error($connection);
                }
            }
        }
    } else {
        $msg = "All fields are required.";
    }
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Organization Signup | People: Community Sharing Platform</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #333;
            background-image: url('bg3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            margin-bottom: 20px;
            color: #4f358e;
            text-align: center;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .input-group input {
            width: 95%;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            color: #fff;
            background-color: #4f358e;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #3c2c7a;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            color: red;
        }
        .header{
            display: flex;
            padding: 2%;
            align-items: center;
            min-height: 2rem;
            max-height: 2rem;
            gap: 10px;
            background-color: #fff;
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
            font-size: 1.6rem;
            color: #06C167;
        }
        @media (max-width: 450px) {
            .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 95%;
            max-width: 20rem;
        }
        .header h2 {
            font-size: 1.4rem;
        }
    }
    </style>
    <script>
        // Automatically close the window if account creation was successful
        document.addEventListener("DOMContentLoaded", function() {
            const success = <?php echo json_encode($success); ?>;
            if (success) {
                setTimeout(() => {
                    alert("Account created successfully. Closing window...");
                    window.close();
                }, 3000); // Close after 3 seconds
            }
        });
    </script>
</head>
<body>
    <div class="form-container">
    <div class="header">
    <a href="javascript:window.close();"><img src="SiteIcons/arrow.png" alt="back"></a>
    <h2><span style="color: #ff009a;">Organization </span>Signup</h2>
    </div> <br> <br>
        <?php if (!empty($msg)): ?>
            <p class="message"><?php echo $msg; ?></p>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="org_name">Organization Name</label>
                <input type="text" id="org_name" name="org_name" required>
            </div>
            <div class="input-group">
                <label for="org_email">Email</label>
                <input type="email" id="org_email" name="org_email" required>
            </div>
            <div class="input-group">
                <label for="org_password">Password</label>
                <input type="password" id="org_password" name="org_password" required>
            </div>
            <div class="input-group">
                <label for="org_confirm_password">Confirm Password</label>
                <input type="password" id="org_confirm_password" name="org_confirm_password" required>
            </div>
<br>
            <button type="submit" name="signup_organization" class="btn">Sign Up</button>
        </form>
    </div>
</body>
</html>
