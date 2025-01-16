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
$user['last_message_time'] = !empty($user['last_message_time']) ? strtotime($user['last_message_time']) : null;
$messages[] = $user;


// SQL query to get the latest messages
$users_sql = "
    SELECT DISTINCT 
        login.id, 
        login.name, 
        login.profile_pic,
        last_message.message AS last_message_content, 
        last_message.timestamp AS last_message_time,
        last_message.sender_id AS last_sender_id
    FROM messages
    JOIN login ON login.id = IF(messages.sender_id = ?, messages.receiver_id, messages.sender_id)
    LEFT JOIN (
        SELECT 
            IF(sender_id = ?, receiver_id, sender_id) AS other_user_id,
            sender_id,
            message,
            timestamp
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
        ORDER BY timestamp DESC
    ) AS last_message ON last_message.other_user_id = login.id
    WHERE messages.sender_id = ? OR messages.receiver_id = ?
    GROUP BY login.id
    ORDER BY last_message_time DESC
";

$stmt = $conn->prepare($users_sql);
$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$users_result = $stmt->get_result();

$messages = [];
while ($user = $users_result->fetch_assoc()) {
    $user['profile_pic'] = !empty($user['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : 'default.png';
    $messages[] = $user;
}

// Return messages as JSON
header('Content-Type: application/json');
echo json_encode($messages);
?>
