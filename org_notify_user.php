<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

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

$user_id = $_SESSION['id'];

// Handle status change requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $food_id = intval($_POST['food_id']);
    $new_status = $_POST['new_status'];

    // Update the status of the item if requested by the owner
    $update_status_sql = "UPDATE free_food SET status = ? WHERE id = ? AND user_id = ?";
    $update_status_stmt = $conn->prepare($update_status_sql);
    $update_status_stmt->bind_param("sii", $new_status, $food_id, $user_id);
    $update_status_stmt->execute();

    // Fetch the food title for the item
    $food_title_sql = "SELECT food_title FROM free_food WHERE id = ?";
    $food_title_stmt = $conn->prepare($food_title_sql);
    $food_title_stmt->bind_param("i", $food_id);
    $food_title_stmt->execute();
    $food_title_result = $food_title_stmt->get_result();
    $food_title_row = $food_title_result->fetch_assoc();
    $item_name = htmlspecialchars($food_title_row['food_title']);

    // If the status is changed to "holded", send an automatic message to the requester
    if ($new_status === 'holded') {
        // Retrieve the requester’s ID for this item from the notifications table
        $requester_sql = "SELECT id, requester_id FROM notifications WHERE food_id = ? AND poster_id = ? LIMIT 1";
        $requester_stmt = $conn->prepare($requester_sql);
        $requester_stmt->bind_param("ii", $food_id, $user_id);
        $requester_stmt->execute();
        $requester_result = $requester_stmt->get_result();
    
        if ($requester_result->num_rows > 0) {
            $requester_data = $requester_result->fetch_assoc();
            $requester_id = $requester_data['requester_id'];
            $notification_id = $requester_data['id'];
    
            // Mark the notification as read
            $mark_read_sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
            $mark_read_stmt = $conn->prepare($mark_read_sql);
            $mark_read_stmt->bind_param("i", $notification_id);
            $mark_read_stmt->execute();
    
            // Insert an automatic message into the messages table
            $auto_message = "I have placed the $item_name on hold for you.";
            $message_sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
            $message_stmt = $conn->prepare($message_sql);
            $message_stmt->bind_param("iis", $user_id, $requester_id, $auto_message);
            $message_stmt->execute();

            // Fetch the requester's email
            $requester_email_sql = "SELECT email, name FROM login WHERE id = ?";
            $requester_email_stmt = $conn->prepare($requester_email_sql);
            $requester_email_stmt->bind_param("i", $requester_id);
            $requester_email_stmt->execute();
            $requester_email_result = $requester_email_stmt->get_result();
            $requester_email_data = $requester_email_result->fetch_assoc();
            $requester_email = $requester_email_data['email'];
            $requester_name = $requester_email_data['name'];

            // Fetch the poster’s name
            $poster_name_sql = "SELECT name FROM login WHERE id = ?";
            $poster_name_stmt = $conn->prepare($poster_name_sql);
            $poster_name_stmt->bind_param("i", $user_id);
            $poster_name_stmt->execute();
            $poster_name_result = $poster_name_stmt->get_result();
            $poster_name_data = $poster_name_result->fetch_assoc();
            $poster_name = $poster_name_data['name'];

        

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'info.people.platfrom@gmail.com';
            $mail->Password = 'xtvqpbrsbtmnbnhv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('no-reply@people.com', 'no-reply@people.com');
            $mail->addAddress($requester_email);

            $mail->isHTML(true);
            $mail->Subject = "Your Request for '$item_name' Has Been Placed on Hold";
            $mail->Body = "<p>Dear {$requester_name},</p>
                           <p>Good news! <strong>{$poster_name}</strong> has placed the item <strong>'$item_name'</strong> on hold for you.</p>
                           <p>Please log in to your account to view the details and complete the exchange.</p>
                           <p>Best regards,<br>
                           <strong>The People Community Team</strong><br>
                           <i>People: Community Sharing Platform</i></p>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        }
        }
    }
}



// Fetch all notifications with item details
$notifications_sql = "SELECT notifications.id, notifications.message, notifications.created_at, 
                             free_food.food_title, free_food.description, free_food.status, 
                             free_food.id AS food_id, login.id AS requester_id, -- Add requester_id here
                             login.name AS requester_name, login.profile_pic AS requester_profile_pic,
                             free_food_images.food_image, free_food_images.image_type,
                             notifications.is_read
                      FROM notifications
                      JOIN free_food ON notifications.food_id = free_food.id
                      JOIN login ON notifications.requester_id = login.id
                      LEFT JOIN free_food_images ON free_food.id = free_food_images.food_id
                      WHERE notifications.poster_id = ?
                      GROUP BY notifications.id
                      ORDER BY notifications.created_at DESC";

$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();


// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['notification_id']);
    $update_sql = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $notification_id);
    $update_stmt->execute();
    header("Location: notify_user.php"); // Refresh the page
    exit;
}
// Toggle read/unread notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_read_status'])) {
    $notification_id = intval($_POST['notification_id']);
    $current_status = intval($_POST['current_status']); // 1 for read, 0 for unread
    $new_status = $current_status ? 0 : 1; // Toggle status

    $update_sql = "UPDATE notifications SET is_read = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_status, $notification_id);
    $update_stmt->execute();
    
    header("Location: notify_user.php"); // Refresh the page
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="people.png">
<link href="notify_user.css" rel="stylesheet">
<title>Notifications | People: Community Sharing Platform</title>
    <script>
        // JavaScript function to confirm the action before submitting the form
        function confirmAction(event, action) {
            if (!confirm(`Are you sure you want to ${action}?`)) {
                event.preventDefault();
            }
        }
    </script>
</head>
<body>
<div class="mai">
<div class="container">
    
    <div class="back">
        <a href="organizationHome.php"><img src="SiteIcons/arrow-w.png" alt="back"></a>
    </div>
<div class="noty">
    <h2>Notifications</h2>
    <?php if ($notifications_result->num_rows > 0): ?>
        <?php while ($notification = $notifications_result->fetch_assoc()): ?>
            <!-- Apply .unread-notification if the notification is unread -->
            <div class="notification <?php echo !$notification['is_read'] ? 'unread-notification' : ''; ?>">
                <!-- Requester Profile Picture -->
                <?php if (!empty($notification['requester_profile_pic'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($notification['requester_profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
                <?php else: ?>
                    <img src="default_profile.png" alt="Default Profile Picture" class="profile-pic">
                <?php endif; ?>

                <!-- Food Image -->
                <?php if (!empty($notification['food_image'])): ?>
                    <img src="data:<?php echo $notification['image_type']; ?>;base64,<?php echo base64_encode($notification['food_image']); ?>" alt="Food Image" class="food-image-m">
                <?php else: ?>
                    <img src="default_food.png" alt="Default Food Image" class="food-image-m">
                <?php endif; ?>

                <!-- Notification Content -->
                <div>
                    <p><strong><?php echo htmlspecialchars($notification['requester_name']); ?></strong> has requested your <strong><?php echo htmlspecialchars($notification['food_title']); ?></strong> (<em><?php echo $notification['created_at']; ?></em>)</p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($notification['description']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($notification['status']); ?></p>

                    <?php if ($notification['status'] === 'completed'): ?>
                        <p class="completed-label">Completed</p>
                    <?php else: ?>
                        
                        <!-- Toggle Read/Unread Button -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $notification['is_read']; ?>">
                            <button type="submit" name="toggle_read_status" class="mark-read-btn" onclick="confirmAction(event, '<?php echo $notification['is_read'] ? 'mark as unread' : 'mark as read'; ?>')">
                                <?php echo $notification['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>
                            </button>
                        </form>
                        <!-- Hold/Unhold/Complete Buttons with confirmation -->
                        <?php if ($notification['status'] === 'normal'): ?>
                            <!-- Hold button if status is normal -->
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="food_id" value="<?php echo $notification['food_id']; ?>">
                                <input type="hidden" name="new_status" value="holded">
                                <button type="submit" name="change_status" class="hold-btn" onclick="confirmAction(event, 'hold this item')">Hold Item</button>
                            </form>
                        <?php elseif ($notification['status'] === 'holded'): ?>
                            <!-- Unhold and Complete buttons if status is holded -->
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="food_id" value="<?php echo $notification['food_id']; ?>">
                                <input type="hidden" name="new_status" value="normal">
                                <button type="submit" name="change_status" class="unhold-btn" onclick="confirmAction(event, 'unhold this item')">Unhold Item</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="food_id" value="<?php echo $notification['food_id']; ?>">
                                <input type="hidden" name="new_status" value="completed">
                                <button type="submit" name="change_status" class="complete-btn" onclick="confirmAction(event, 'complete this item')">Complete Item</button>
                            </form>
                            <?php endif; ?>
                        <!-- Open DM Button -->
                        <form action="chat.php" method="get" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $notification['requester_id']; ?>">
                            <button type="submit" class="mark-read-btn">Open DM</button>
                        </form>
                    <?php endif; ?>
                </div>
                <!-- Food Image -->
                <?php if (!empty($notification['food_image'])): ?>
                    <img src="data:<?php echo $notification['image_type']; ?>;base64,<?php echo base64_encode($notification['food_image']); ?>" alt="Food Image" class="food-image">
                <?php else: ?>
                    <img src="default_food.png" alt="Default Food Image" class="food-image">
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No new notifications.</p>
    <?php endif; ?>
</div>
</div>
</div>
</body>

</html>
