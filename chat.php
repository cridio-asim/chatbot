<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1)) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Get admin ID
$admin_id = null;
$sql = "SELECT id FROM users WHERE is_admin = 1 LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $admin_id = $row['id'];
} else {
    die("Admin user not found. Please ensure an admin user is registered.");
}

$current_user_id = $_SESSION['id'];
$chat_with_id = $admin_id; // Always chat with admin
$chat_with_username = "Admin"; // Display "Admin" as chat partner
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($chat_with_username); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h3>Chatting with: <?php echo htmlspecialchars($chat_with_username); ?></h3>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <div class="chat-box" id="chat-box">
            </div>
        <div class="chat-input">
            <input type="text" id="message-input" placeholder="Type your message...">
            <button id="send-button">Send</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        const chatWithId = <?php echo json_encode($chat_with_id); ?>;
        const chatBox = document.getElementById('chat-box');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');

        function fetchMessages() {
            // Using fetch() API for GET request
            fetch(`get_messages.php?sender_id=${currentUserId}&receiver_id=${chatWithId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(messages => {
                    chatBox.innerHTML = ''; // Clear existing messages
                    messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.classList.add('message-bubble');
                        messageDiv.classList.add(msg.sender_id == currentUserId ? 'sent' : 'received');

                        // Format timestamp without seconds
                        const date = new Date(msg.timestamp);
                        const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                        messageDiv.innerHTML = `
                            <p>${msg.message}</p>
                            <span class="timestamp">${timeString}</span>
                        `;
                        chatBox.appendChild(messageDiv);
                    });
                    chatBox.scrollTop = chatBox.scrollHeight; // Scroll to bottom
                })
                .catch(e => {
                    console.error('Error fetching messages:', e);
                });
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message === '') return;

            const formData = new URLSearchParams();
            formData.append('sender_id', currentUserId);
            formData.append('receiver_id', chatWithId);
            formData.append('message', message);

            // Using fetch() API for POST request
            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    messageInput.value = ''; // Clear input
                    fetchMessages(); // Refresh messages
                } else {
                    console.error('Error sending message:', data.message);
                }
            })
            .catch(e => {
                console.error('Error sending message:', e);
            });
        }

        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initial fetch and then poll every 1 second
        fetchMessages();
        setInterval(fetchMessages, 1000);
    </script>
</body>
</html>