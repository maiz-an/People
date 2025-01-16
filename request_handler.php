<?php
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
    $back_url = "organizationHome.php";
} else {
    $back_url = "home.php";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_item'])) {
    $food_id = intval($_POST['food_id']);
    $requester_id = $_SESSION['id'];

    $check_request_sql = "SELECT id FROM notifications WHERE food_id = ? AND requester_id = ?";
    $check_request_stmt = $conn->prepare($check_request_sql);
    $check_request_stmt->bind_param("ii", $food_id, $requester_id);
    $check_request_stmt->execute();
    $check_request_result = $check_request_stmt->get_result();

    // Fetch the requester's name
    $requester_sql = "SELECT name FROM login WHERE id = ?";
    $requester_stmt = $conn->prepare($requester_sql);
    $requester_stmt->bind_param("i", $requester_id);
    $requester_stmt->execute();
    $requester_result = $requester_stmt->get_result();
    $requester_data = $requester_result->fetch_assoc();
    $requester_name = $requester_data['name'];
    $requester_stmt->close();

    if ($check_request_result->num_rows > 0) {
        $message = "You have already requested this item.";
    } else {
        // Adjusted SQL to fetch the poster's name
        $poster_sql = "SELECT user_id, food_title, login.email, login.name AS poster_name FROM free_food JOIN login ON free_food.user_id = login.id WHERE free_food.id = ?";
        $poster_stmt = $conn->prepare($poster_sql);
        $poster_stmt->bind_param("i", $food_id);
        $poster_stmt->execute();
        $poster_result = $poster_stmt->get_result();

        if ($poster_result->num_rows > 0) {
            $poster_data = $poster_result->fetch_assoc();
            $poster_id = $poster_data['user_id'];
            $poster_email = $poster_data['email'];
            $food_title = $poster_data['food_title'];
            $poster_name = $poster_data['poster_name'];

            $notification_message = "User has requested your item: $food_title.";
            $notification_sql = "INSERT INTO notifications (food_id, requester_id, poster_id, message) VALUES (?, ?, ?, ?)";
            $notification_stmt = $conn->prepare($notification_sql);
            $notification_stmt->bind_param("iiis", $food_id, $requester_id, $poster_id, $notification_message);
            $notification_stmt->execute();

            $request_message = "Hello! I've seen your giveaway '$food_title' and would love to take it.";
            $message_sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
            $message_stmt = $conn->prepare($message_sql);
            $message_stmt->bind_param("iis", $requester_id, $poster_id, $request_message);
            $message_stmt->execute();

            // Send email to the poster using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'info.people.platfrom@gmail.com';
                $mail->Password = 'xtvqpbrsbtmnbnhv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@people.com', 'Resquest for your GiveWay'); 
                $mail->addAddress($poster_email);

                $mail->isHTML(true);
                $mail->Subject = "{$requester_name} Requested for Your $food_title";
                $mail->Body = "<p>Hello {$poster_name},</p>
                            <p>We’re pleased to inform you that <strong>{$requester_name}</strong> has expressed interest in your item <strong>'$food_title'</strong>, listed on the People: Community Sharing Platform.</p>
                            <p>To view the details and connect with {$requester_name}, please log in to your account on our platform.</p>
                            <p>Best regards,<br>
                            <strong>The People Community Team</strong><br>
                            <i>People: Community Sharing Platform</i></p>";

                $mail->send();
                $message = "You have successfully requested. Check your message box. An email has been sent to the poster.";
            } catch (Exception $e) {
                $message = "You have successfully requested. Check your message box. However, we couldn't send an email notification.";
            }
        } else {
            $message = "Food item not found.";
        }
    }

    if (isset($check_request_stmt)) $check_request_stmt->close();
    if (isset($poster_stmt)) $poster_stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Request Confirmation | People: Community Sharing Platform</title>
    <style>
        body {
        font-family: 'Poppins', sans-serif;
            padding-top: 100px;
            background-image: url('bg3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: #333;
            margin: 0;
            overflow: hidden;
        }
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            background-color: whitesmoke;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7); /* Dark overlay */
            justify-content: center;
            align-items: center;
            padding: 15px;
            box-sizing: border-box;
        }
        .modal-content {
            background-color: whitesmoke;
            padding: 20px;
            border-radius: 18px;
            max-width: 55%;
            text-align: center;
            box-sizing: border-box;
            font-size: 25px; /* Increase font size */
        }
        .close-btn {
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            font-size: 18px;
                font-weight: bold;
                padding: 16px 25px;
                border-radius: 30px;
        }

        /* Responsive styles for extra small screens */
        @media (max-width: 500px) {

            .modal-content {
                border-radius: 30px;
                font-size: 20px;
                padding: 25px;
                width: 90%;
            }
            .close-btn {
                font-size: 18px;
                font-weight: bold;
                padding: 16px 25px;
                border-radius: 30px;
            }
        }
    </style>
</head>
<body>

<?php if (!empty($message)): ?>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <p><?php echo $message; ?></p>
            <button class="close-btn" onclick="closeModal()">Close</button>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($message)): ?>
            document.getElementById("myModal").style.display = "flex";
        <?php endif; ?>
    });

    function closeModal() {
        document.getElementById("myModal").style.display = "none";
        window.location.href = "<?php echo $back_url; ?>";
    }
</script>
</body>
</html>
