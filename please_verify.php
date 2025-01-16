<?php
// Start the session (make sure session_start() is called)
session_start();

// Check if the user is logged in and either a regular or organization user
if (!isset($_SESSION['id']) || !in_array($_SESSION['user_type'], ['regular', 'organization'])) {
    header("Location: index.php");
    exit();
}
// Determine the back button URL based on the user type
$user_id = $_SESSION['id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'organization') {
    $back_url = "org_user_dashboard.php";
} else {
    $back_url = "user_dashboard.php";
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification Required</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to global stylesheet -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('bg3.jpg');
            background-size: cover; 
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        body{
            background-image: url('bg3.jpg');
            background-size: cover; 
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            text-align: center;
        }

        h2 {
            color: #4f358e;
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        a {
            color: #4f358e;
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
        }

        .contact-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background-color: #4f358e;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .contact-btn:hover {
            background-color: #382674;
        }

        .container img {
            width: 150px;
            height: auto;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            h2 {
                font-size: 1.8rem;
            }

            p {
                font-size: 1rem;
            }

            .contact-btn {
                padding: 10px 25px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="SiteIcons/expired.png" alt="Verification Pending Icon"> <!-- Replace with the correct path to the icon -->
        <h2>Account Verification Required</h2>
        <p>Your account has not been verified yet. Please verify your account from your dashboard to gain full access.</p>
        <p>If you have any questions or need assistance, feel free to <a href="contact_form.php">contact support</a>.</p>
        <button class="contact-btn" onclick="window.location.href='<?php echo $back_url; ?>'">go back to dashboard</button>
    </div>
</body>
</html>
