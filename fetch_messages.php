<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['id']) || !isset($_GET['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['id'];
$receiver_id = intval($_GET['user_id']);

// Fetch messages between the logged-in user and the selected user
$messages_sql = "SELECT message, sender_id, receiver_id, timestamp FROM messages 
                 WHERE (sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)
                 ORDER BY timestamp ASC";
$stmt = $conn->prepare($messages_sql);
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message' => $row['message'],
        'sender_id' => $row['sender_id'],
        'timestamp' => $row['timestamp']
    ];
}

echo json_encode($messages);
?>
