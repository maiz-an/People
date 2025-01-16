<?php
require 'vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['id'];
    $receiver_id = intval($_POST['receiver_id']);
    $message = $_POST['message'];

    if (empty(trim($message))) {
        echo json_encode(['error' => 'Message cannot be empty.']);
        exit;
    }

    // Insert the new message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    
    if ($stmt->execute()) {
        // Retrieve the inserted message
        $message_id = $conn->insert_id;
        $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_message = $result->fetch_assoc();

        // Send push notification to receiver
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:mohamedmaizanmunas@gmail.com', 
                'publicKey' => 'BC-7WsmMZx3cQ_wfByFsOq4nD8uIJu7Nz-CuAVbSgT8dAQRATGqBp_w9Mp5pTFOCTlkTDL0VuSfKCoMcev7K14U',
                'privateKey' => 'BhhQq-SapNlz6otMb-jVWMRe6qjAb_IPo1G9uGiiYkA',
            ],
        ];
        $webPush = new WebPush($auth);

        // Fetch the recipient's subscription info
        $subscription_sql = "SELECT * FROM subscriptions WHERE user_id = ?";
        $subscription_stmt = $conn->prepare($subscription_sql);
        $subscription_stmt->bind_param("i", $receiver_id);
        $subscription_stmt->execute();
        $subscription_result = $subscription_stmt->get_result();

        // Send a notification to each subscription
        $payload = json_encode([
            'title' => 'New Message!',
            'body' => $message,
            'url' => 'https://yourwebsite.com/chat.php?user_id=' . $sender_id,
        ]);

        while ($subscription = $subscription_result->fetch_assoc()) {
            $sub = Subscription::create([
                'endpoint' => $subscription['endpoint'],
                'keys' => [
                    'p256dh' => $subscription['p256dh'],
                    'auth' => $subscription['auth'],
                ],
            ]);
            $webPush->queueNotification($sub, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                error_log("Message failed: {$report->getReason()}");
            }
        }

        echo json_encode($new_message);
    } else {
        echo json_encode(['error' => 'Failed to send message.']);
    }
}
?>
