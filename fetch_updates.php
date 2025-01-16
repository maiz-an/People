<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

if (!isset($_SESSION['id'])) {
    die(json_encode(['error' => 'User not logged in']));
}

$id = $_SESSION['id'];
$data = [
    'unread_notifications' => 0,
    'unread_messages' => 0,
    'food_items' => []
];

// Fetch unread notifications count
$notification_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE poster_id = ? AND is_read = 0";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();
if ($notification_result) {
    $data['unread_notifications'] = $notification_result->fetch_assoc()['unread_count'] ?? 0;
} else {
    error_log('Notification query failed: ' . $conn->error);
}

// Fetch unread messages count
$message_sql = "SELECT COUNT(*) AS unread_messages FROM messages WHERE receiver_id = ? AND is_read = 0";
$message_stmt = $conn->prepare($message_sql);
$message_stmt->bind_param("i", $id);
$message_stmt->execute();
$message_result = $message_stmt->get_result();
if ($message_result) {
    $data['unread_messages'] = $message_result->fetch_assoc()['unread_messages'] ?? 0;
} else {
    error_log('Message query failed: ' . $conn->error);
}

// Fetch available food items
$food_query = "
    SELECT free_food.*, login.name AS poster_name, login.profile_pic AS poster_pic
    FROM free_food 
    JOIN login ON free_food.user_id = login.id 
    WHERE free_food.user_id != ? AND free_food.status = 'normal' 
    ORDER BY free_food.id DESC";
$food_stmt = $conn->prepare($food_query);
$food_stmt->bind_param("i", $id);
$food_stmt->execute();
$food_result = $food_stmt->get_result();
if ($food_result) {
    while ($row = $food_result->fetch_assoc()) {
        $row['poster_pic'] = $row['poster_pic'] 
            ? 'data:image/jpeg;base64,' . base64_encode($row['poster_pic']) 
            : 'default.png';
        $data['food_items'][] = $row;
    }
} else {
    error_log('Food query failed: ' . $conn->error);
}

// Log output to check what data is being returned
error_log(print_r($data, true));
echo json_encode($data);
?>