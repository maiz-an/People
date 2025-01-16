<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

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

// Handle OTP generation and email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $current_time = time();
    $time_difference = isset($_SESSION['last_otp_time']) ? $current_time - $_SESSION['last_otp_time'] : 61;

    // Check if 1 minute has passed since the last OTP was sent
    if ($time_difference < 60) {
        $message = "Please wait " . (60 - $time_difference) . " seconds before resending the OTP.";
    } else {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['last_otp_time'] = $current_time;

        // Fetch user email
        $query = "SELECT email FROM login WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $email = $user['email'];

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'info.people.platfrom@gmail.com';
            $mail->Password = 'xtvqpbrsbtmnbnhv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('no-reply@people.com', 'People: Community Sharing Platform');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Your Verification Code";
            $mail->Body = "<p>Hello,</p>
                           <p>Your OTP for verification is: <strong>{$otp}</strong></p>
                           <p>Best regards,<br>
                           People: Community Sharing Platform</p>";

            $mail->send();
            $message = "OTP has been sent to your email.";
        } catch (Exception $e) {
            $message = "Failed to send OTP. Please try again.";
        }
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    // Check if the entered OTP matches the generated OTP
    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $entered_otp) {
        // Update user status to 'allowed'
        $update_query = "UPDATE login SET status = 'allowed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $user_id);
        if ($update_stmt->execute()) {
            $message = "Your account has been verified successfully.";
            unset($_SESSION['otp']); // Clear OTP from session
            $_SESSION['verified'] = true; // Mark account as verified
        } else {
            $message = "Failed to update account status. Please try again.";
        }
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification | People: Community Sharing Platform</title>
    <link rel="icon" type="image/png" href="people.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #333;
            background-image: url('bg3.jpg');
            background-size: cover; 
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            overflow: hidden; 

        }
        

        .container {
            background: white;
            padding: 40px ;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        h2 {
            color: #4f358e;
            margin-bottom: 20px;
        }

        p {
            color: #555;
            margin-bottom: 20px;
        }

        

        button {
            background-color: #4f358e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        button:hover:enabled {
            background-color: #3d276c;
        }

        .message {
            color: red;
            margin-bottom: 10px;
        }
        .input-container {
            position: relative;
            width: 100%;
        }

        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 15px;
        }


        .resend-container a {
            margin-left: 20rem;
            color: #4f358e;
            text-decoration: none;
            font-size: 0.8rem;
            cursor: not-allowed;
        }
        .resend-container a:enabled {
            cursor: pointer;
        }

        #timer {
            font-size: 0.8rem;
            color: #888;
            margin-top: 5px;
        }
        @media (max-width: 500px) {
            body {
                padding: 0;
                margin: 0;
                font-size: 14px;
            }

            .container {
                padding: 20px;
                border-radius: 10px;
                max-width: 90%;
                width: 80%;
                margin: 10px auto;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            }

            h2 {
                font-size: 1.5rem;
                margin-bottom: 15px;
            }

            p {
                font-size: 1rem;
                margin-bottom: 15px;
            }

            input[type="number"] {
                font-size: 1rem;
                padding: 8px;
                border-radius: 5px;
            }

            button {
                font-size: 0.9rem;
                padding: 8px 15px;
                border-radius: 5px;
                width: 100%;
            }

            .resend-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-top: 15px;
            }

            .resend-container a {
                margin: 10px 0;
                font-size: 0.9rem;
            }

            #timer {
                font-size: 0.9rem;
                margin-top: 10px;
            }
        }

    </style>
</head>
<body>
<div class="container">
    <h2>Verify Your Account</h2>
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['verified']) && $_SESSION['verified']): ?>
        <p>Your account has been successfully verified. You can now access all features.</p>
        <a href="<?php echo $back_url; ?>">
            <button>Go to Dashboard</button>
        </a>
    <?php elseif (!isset($_SESSION['otp'])): ?>
        <form method="POST">
            <p>Click below to send a verification code to your email.</p>
            <button type="submit" name="send_otp">Send OTP</button>
        </form>
    <?php else: ?>
        <!-- OTP Verification Form -->
        <form method="POST" id="verify-form">
            <p>Enter the 6-digit code sent to your email.</p>
            <div class="input-container">
                <input type="number" name="otp" placeholder="Enter OTP" required>
            </div>
            <button type="submit" name="verify_otp">Verify</button>
        </form>

        <!-- Resend OTP Form -->
        <form method="POST" id="resend-form">
            <div class="resend-container">
                <a href="javascript:void(0);" id="resend-link" onclick="resendOtp(event)">Resend OTP</a>
                <p id="timer"></p>
                <input type="hidden" name="send_otp" value="1">
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    // Countdown Timer for Resend Link
    const resendLink = document.getElementById('resend-link');
    const timerDisplay = document.getElementById('timer');
    let countdown = <?php echo isset($_SESSION['last_otp_time']) ? max(0, 60 - (time() - $_SESSION['last_otp_time'])) : 0; ?>;

    if (countdown > 0) {
        updateTimer();
        const interval = setInterval(() => {
            countdown--;
            updateTimer();
            if (countdown <= 0) {
                clearInterval(interval);
                enableResendLink();
            }
        }, 1000);
    } else {
        enableResendLink();
    }

    function updateTimer() {
        timerDisplay.textContent = `Please wait ${countdown} seconds to resend OTP.`;
        resendLink.style.cursor = "not-allowed";
        resendLink.style.color = "grey";
        resendLink.onclick = null; // Disable click while timer is active
    }

    function enableResendLink() {
        timerDisplay.textContent = '';
        resendLink.style.cursor = "pointer";
        resendLink.style.color = "#4f358e";
        resendLink.onclick = resendOtp; // Re-enable click
    }

    function resendOtp(event) {
        event.preventDefault(); // Prevent the default anchor behavior
        document.getElementById('resend-form').submit(); // Submit the form programmatically
    }
</script>