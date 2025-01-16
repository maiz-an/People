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

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['id'];

// Query to fetch unread message counts and the latest message for each user
$unread_sql = "
    SELECT 
        login.id AS user_id, 
        login.name, 
        login.profile_pic,
        COUNT(CASE WHEN messages.is_read = 0 AND messages.receiver_id = ? THEN 1 END) AS unread_count,
        MAX(messages.timestamp) AS last_message_time,
        MAX(CASE WHEN messages.timestamp = (SELECT MAX(timestamp) FROM messages WHERE (sender_id = login.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = login.id)) THEN messages.message END) AS last_message_content,
        MAX(CASE WHEN messages.timestamp = (SELECT MAX(timestamp) FROM messages WHERE (sender_id = login.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = login.id)) THEN messages.sender_id END) AS last_sender_id
    FROM login
    LEFT JOIN messages ON (login.id = messages.sender_id OR login.id = messages.receiver_id)
    WHERE (messages.sender_id = ? OR messages.receiver_id = ?)
    GROUP BY login.id
";

$stmt = $conn->prepare($unread_sql);
$stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$unread_data = [];
while ($row = $result->fetch_assoc()) {
    $row['profile_pic'] = !empty($row['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($row['profile_pic']) : 'default.png';
    $unread_data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($unread_data);
?>
