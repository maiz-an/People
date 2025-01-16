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
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['id'];
$receiver_id = intval($_GET['user_id']); // Get the ID of the user to chat with

// Mark all unread messages from the other user as read when the chat is opened
$markAsReadSql = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
$markAsReadStmt = $conn->prepare($markAsReadSql);
$markAsReadStmt->bind_param("ii", $receiver_id, $user_id);
$markAsReadStmt->execute();


// Fetch the name of the user to chat with
$receiver_sql = "SELECT name FROM login WHERE id = ?";
$receiver_stmt = $conn->prepare($receiver_sql);
$receiver_stmt->bind_param("i", $receiver_id);
$receiver_stmt->execute();
$receiver_result = $receiver_stmt->get_result();
$receiver = $receiver_result->fetch_assoc();
$receiver_name = $receiver['name'] ?? 'Unknown User';

// Fetch messages between the logged-in user and the selected user
$messages_sql = "SELECT * FROM messages 
                 WHERE (sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)
                 ORDER BY timestamp ASC";
$stmt = $conn->prepare($messages_sql);
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();

// Fetch the name and profile picture of the user to chat with
$receiver_sql = "SELECT name, profile_pic FROM login WHERE id = ?";
$receiver_stmt = $conn->prepare($receiver_sql);
$receiver_stmt->bind_param("i", $receiver_id);
$receiver_stmt->execute();
$receiver_result = $receiver_stmt->get_result();
$receiver = $receiver_result->fetch_assoc();
$receiver_name = $receiver['name'] ?? 'Unknown User';

// Check if there is a profile picture; if not, use a default image
if (!empty($receiver['profile_pic'])) {
    $receiver_profile_pic = 'data:image/jpeg;base64,' . base64_encode($receiver['profile_pic']);
} else {
    $receiver_profile_pic = 'default.png'; // Path to your default profile picture
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="people.png">
    <title>Chat with <?php echo htmlspecialchars($receiver_name); ?>  | People: Community Sharing Platform</title>
    <link rel="stylesheet" href="chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
<div class="chat-container">
    <!-- Display receiver's profile picture and name -->
    <div class="chat-header">
        <img src="<?php echo htmlspecialchars($receiver_profile_pic); ?>" alt="<?php echo htmlspecialchars($receiver_name); ?>'s profile" class="profile-picture">
        <h2><?php echo htmlspecialchars($receiver_name); ?></h2>
    </div>
    <div class="message-list" id="messageList">
        <?php while ($message = $messages_result->fetch_assoc()): ?>
            <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
        <?php endwhile; ?>
    </div>
    <form id="chatForm">
        <textarea name="message" id="chatInput" class="chat-input" placeholder="Type your message here..."></textarea>
        <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
        <button type="submit" class="chat-send">Send</button>
    </form>

</div>

<script>
    document.getElementById('chatForm').addEventListener('submit', async function(event) {
    event.preventDefault(); // Prevent the form from submitting in the traditional way

    const messageInput = document.getElementById('chatInput');
    const messageText = messageInput.value.trim();
    messageInput.value = '';
    console.log('Message input cleared:', messageInput.value);


    if (messageText === '') return; // Prevent sending empty messages

    const formData = new FormData();
    formData.append('message', messageText);
    formData.append('receiver_id', <?php echo $receiver_id; ?>);

    // Send the message using fetch
    const response = await fetch('send_message.php', {
        method: 'POST',
        body: formData
    });
    const newMessage = await response.json(); // Parse the JSON response

    // Add the new message to the chat
    addMessageToChat(newMessage);

    // Clear the input field and scroll to bottom
    messageInput.value = '';
    scrollToBottom();
});

    // Helper to format timestamp into a readable date and time
    function formatDateTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const timeString = date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

            // Check if the message is from today
            if (date.toDateString() === now.toDateString()) {
                return `Today ${timeString}`;
            }

            // Check if the message is from yesterday
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            if (date.toDateString() === yesterday.toDateString()) {
                return `Yesterday ${timeString}`;
            }

            // If older than yesterday, show the actual date and time if it's within a week
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
            if (date > oneWeekAgo) {
                const dayOfWeek = date.toLocaleDateString([], { weekday: 'long' });
                return `${dayOfWeek} ${timeString}`;
            }

            // For dates older than a week, show the full date and time
            return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) + `, ${timeString}`;
        }

    // Function to add a single message to the chat
    function addMessageToChat(message) {
        const messageList = document.getElementById('messageList');
        const messageEl = document.createElement('div');
        messageEl.className = 'message ' + (message.sender_id == <?php echo $user_id; ?> ? 'sent' : 'received');
        
        // Create the message content with timestamp
        const messageContent = document.createElement('span');
        messageContent.textContent = message.message;

        const timestampEl = document.createElement('small');
        timestampEl.textContent = formatDateTime(message.timestamp);
        timestampEl.style.display = 'block'; // Ensures the timestamp is below the message
        timestampEl.style.color = '#888'; // Styling for visibility
        
        messageEl.appendChild(messageContent);
        messageEl.appendChild(timestampEl);
        messageList.appendChild(messageEl);
    }

    let previousMessageCount = 0; // Track the previous count of messages

// Fetch and display messages periodically
async function fetchMessages() {
    const response = await fetch(`fetch_messages.php?user_id=<?php echo $receiver_id; ?>`);
    const messages = await response.json();

    const messageList = document.getElementById('messageList');
    messageList.innerHTML = ''; // Clear current messages

    // Display each message and update the chat
    messages.forEach(addMessageToChat);

    // Check if the number of messages has increased, indicating a new message
    if (messages.length > previousMessageCount) {
        newMessageReceived = true; // Set the flag if there's a new message
    }

    previousMessageCount = messages.length; // Update the previous message count
    scrollToBottom(); // Scroll to the bottom only if new messages were received
}
    let newMessageReceived = false; // Flag to track if a new message was received

// Function to scroll to the bottom only if a new message was received
function scrollToBottom() {
    const messageList = document.getElementById('messageList');
    if (newMessageReceived) {
        messageList.scrollTop = messageList.scrollHeight;
        newMessageReceived = false; // Reset the flag after scrolling
    }
}


    // Initial fetch and periodic update every 3 seconds
    fetchMessages();
    setInterval(fetchMessages, 1000);
</script>


</body>
<div class="back">
    <a href="messages.php"><img src="SiteIcons/arrow.png" alt="back"></a>
    </div>
</html>
