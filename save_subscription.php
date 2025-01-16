<?php
session_start();

if (!isset($_SESSION['id'])) {
    exit('Unauthorized');
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

// Create a database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
file_put_contents('debug_log.txt', print_r($data, true), FILE_APPEND);

// Decode the JSON payload from the POST request
$data = json_decode(file_get_contents('php://input'), true);
if ($data) {
    $user_id = $_SESSION['id'];
    $endpoint = $data['endpoint'];
    $p256dh = $data['keys']['p256dh'];
    $auth = $data['keys']['auth'];

    // Prepare and execute SQL statement to save subscription data
    $stmt = $conn->prepare("REPLACE INTO subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isss", $user_id, $endpoint, $p256dh, $auth);
    $stmt->execute();

    // Check if execution was successful
    if ($stmt->affected_rows > 0) {
        echo "Subscription saved successfully.";
    } else {
        echo "Failed to save subscription.";
    }

    $stmt->close();
} else {
    echo "Invalid subscription data.";
}

$conn->close();
