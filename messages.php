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
$user_type = $_SESSION['user_type'];

// Determine the back button URL based on the user type
if ($user_type === 'organization') {
    $back_url = "organizationHome.php";
} else {
    $back_url = "home.php";
}

// Fetch users with whom the current user has exchanged messages along with the last message, sender info, and timestamp
$users_sql = "
    SELECT DISTINCT 
        login.id, 
        login.name, 
        login.profile_pic,
        last_message.message AS last_message_content, 
        last_message.timestamp AS last_message_time,
        last_message.sender_id AS last_sender_id,
        -- Count unread messages from each user to the current user
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = login.id AND receiver_id = ? AND is_read = 0) AS unread_count
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
$stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$users_result = $stmt->get_result();


?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="people.png">
<title>Messages | People: Community Sharing Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="message.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Direct Messages</title>

</head>
<body>
    <div class="chat-list-container">
        <h2>Your Messages</h2>
        <div class="back">
        <a href="<?php echo $back_url; ?>"><img src="SiteIcons/arrow-w.png" alt="back"></a>
    </div>
        <div id="chatList" class="chaty">
        <?php if ($users_result->num_rows > 0): ?>
            <?php while ($user = $users_result->fetch_assoc()): ?>
                <?php
                    $profile_pic = !empty($user['profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : 'default.png';
                    $sender_label = ($user['last_sender_id'] == $user_id) ? "You: " : "{$user['name']}: ";
                    $last_message = !empty($user['last_message_content']) ? htmlspecialchars($user['last_message_content']) : "No messages yet.";

                    // Format the timestamp
                    if (!empty($user['last_message_time'])) {
                        $timestamp = strtotime($user['last_message_time']);
                        $current_time = strtotime(date('Y-m-d H:i:s'));
                        $formatted_time = '';

                        if (date('Y-m-d', $timestamp) == date('Y-m-d', $current_time)) {
                            // Message is from today
                            $formatted_time = "Today " . date("g:i A", $timestamp);
                        } elseif (date('Y-m-d', $timestamp) == date('Y-m-d', strtotime('yesterday'))) {
                            // Message is from yesterday
                            $formatted_time = "Yesterday " . date("g:i A", $timestamp);
                        } else {
                            // Message is from earlier
                            $formatted_time = date("M j, Y, g:i A", $timestamp);
                        }
                    } else {
                        $formatted_time = '';
                    }
                ?>
                <a href="chat.php?user_id=<?php echo $user['id']; ?>" class="chat-list-item">
                    <img src="<?php echo $profile_pic; ?>" alt="<?php echo htmlspecialchars($user['name']); ?>'s profile picture" class="profile-pic">
                    <div>
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="last-message"><?php echo $sender_label . $last_message; ?></p>
                        <?php if ($formatted_time): ?>
                            <p class="timestamp"><?php echo $formatted_time; ?></p>
                        <?php endif; ?>
                        <span class="unread-count">
                            <?php echo $user['unread_count'] > 0 ? $user['unread_count'] . ' unread' : ''; ?>
                        </span>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You have no messages yet.</p>
        <?php endif; ?>
    </div>
    </div>
</body>

<script>
const chatList = document.getElementById('chatList');

// Fetch messages from the server and render them
async function fetchMessages() {
    try {
        const response = await fetch('load_chat_list.php');
        const messages = await response.json();
        renderMessages(messages);
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
}

// Function to render messages in the chat list
function renderMessages(messages) {
    chatList.innerHTML = ''; // Clear current messages
    messages.forEach(user => {
        const userElement = document.createElement('a');
        userElement.href = `chat.php?user_id=${user.id}`;
        userElement.className = 'chat-list-item';

        const formattedTime = formatDateTime(user.last_message_time);

        userElement.innerHTML = `
            <img src="${user.profile_pic}" alt="${user.name}'s profile picture" class="profile-pic">
            <div>
                <h3>${user.name}</h3>
                <p class="last-message">${user.last_sender_id == <?php echo $user_id; ?> ? 'You: ' : user.name + ': '} ${user.last_message_content}</p>
                <p class="timestamp">${formattedTime}</p>
            </div>
        `;
        chatList.appendChild(userElement);
    });
}

// Format date and time
function formatDateTime(timestamp) {
    const date = new Date(timestamp); // Already in milliseconds
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    const isYesterday = date.toDateString() === new Date(now - 24 * 60 * 60 * 1000).toDateString();

    if (isToday) {
        return `Today ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    } else if (isYesterday) {
        return `Yesterday ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) +
               `, ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }
}

// Fetch messages every second
setInterval(fetchMessages, 1000);


// Function to fetch unread counts and update the chat list in real-time
async function fetchUnreadCounts() {
    try {
        const response = await fetch('load_unread_counts.php');
        const users = await response.json();
        updateUnreadCounts(users);
    } catch (error) {
        console.error('Error fetching unread counts:', error);
    }
}

// Function to update unread counts and last message details in the chat list
function updateUnreadCounts(users) {
    users.forEach(user => {
        const userElement = document.querySelector(`a[href='chat.php?user_id=${user.user_id}']`);
        if (userElement) {
            // Update the last message content and sender
            const lastMessageEl = userElement.querySelector('.last-message');
            const senderLabel = user.last_sender_id == <?php echo $user_id; ?> ? 'You: ' : `${user.name}: `;
            lastMessageEl.textContent = senderLabel + user.last_message_content;

            // Update the timestamp
            const timestampEl = userElement.querySelector('.timestamp');
            const formattedTime = formatDateTime(new Date(user.last_message_time).getTime());
            timestampEl.textContent = formattedTime;

            // Update the unread count indicator
            let unreadEl = userElement.querySelector('.unread-count');
            if (!unreadEl) {
                // Apply mobile-specific styles based on screen width
                if (window.innerWidth <= 550) { // Adjust for mobile view
                    unreadEl = document.createElement('span');
                    unreadEl.className = 'unread-count';
                    unreadEl.style.display = 'inline-flex';
                    unreadEl.style.position = 'relative';
                    unreadEl.style.backgroundColor = 'yellowgreen';
                    unreadEl.style.color = 'blue';
                    unreadEl.style.fontSize = '0.75rem';
                    unreadEl.style.fontWeight = 'bold';
                    unreadEl.style.borderRadius = '12px';
                    unreadEl.style.padding = '4px 8px';
                    unreadEl.style.marginLeft = 'auto';
                    unreadEl.style.height = '20px'; 
                    unreadEl.style.alignItems = 'center'; 
                    unreadEl.style.justifyContent = 'center'; 
                    unreadEl.style.lineHeight = '1';     

                    userElement.querySelector('div').appendChild(unreadEl);
                    
                } else {                    
                    unreadEl = document.createElement('span');
                    unreadEl.className = 'unread-count';
                    unreadEl.style.backgroundColor = 'yellowgreen';
                    unreadEl.style.color = 'blue';   
                    unreadEl.style.fontSize = '0.75rem';    
                    unreadEl.style.fontWeight = '550';      
                    unreadEl.style.marginTop = '0.5rem'; 
                    unreadEl.style.marginLeft = '36.5rem'; 
                    unreadEl.style.position = 'absolute';  
                    unreadEl.style.borderRadius = '12px';   
                    unreadEl.style.padding = '2px 8px';       
                    unreadEl.style.justifyContent = 'center';  
                    unreadEl.style.height = '18px';   
                    unreadEl.style.textAlign = 'center';    
                    unreadEl.style.display = 'inline-block';  
                    unreadEl.style.verticalAlign = 'middle';
                    unreadEl.style.lineHeight = '18px';  
                    unreadEl.style.paddingTop = '2px';       
                    userElement.querySelector('div').appendChild(unreadEl);
                    }
            }
            unreadEl.textContent = user.unread_count > 0 ? `${user.unread_count} unread` : '';
        }
    });
}

// Function to format the timestamp
function formatDateTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    const isYesterday = date.toDateString() === new Date(now - 24 * 60 * 60 * 1000).toDateString();

    if (isToday) {
        return `Today ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    } else if (isYesterday) {
        return `Yesterday ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) +
               `, ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }
}

// Fetch unread counts every second
setInterval(fetchUnreadCounts, 1);
</script>

</html>
