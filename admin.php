<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 0)) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

$current_admin_id = $_SESSION['id'];
$chat_with_id = null;
$chat_with_username = "Select a user";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Chatbot</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="admin-panel-container">
        <div class="admin-header">
            <h3>Admin Panel - Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h3>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <div class="admin-content">
            <div class="user-list-panel">
                <h4>Users:</h4>
                <ul id="user-list">
                    </ul>
            </div>
            <div class="chat-panel">
                <div class="chat-header">
                    <h3>Chatting with: <span id="chat-partner-name"><?php echo htmlspecialchars($chat_with_username); ?></span></h3>
                </div>
                <div class="chat-box" id="chat-box">
                    <p class="no-chat-selected" id="no-chat-message">Select a user from the left panel to start chatting.</p>
                </div>
                <div class="chat-input" style="display: none;">
                    <input type="text" id="message-input" placeholder="Type your message...">
                    <button id="send-button">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($current_admin_id); ?>;
        let chatWithId = null;
        let chatInterval;
        const chatBox = document.getElementById('chat-box');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const chatPartnerName = document.getElementById('chat-partner-name');
        const chatInputDiv = document.querySelector('.chat-input');
        const noChatMessage = document.getElementById('no-chat-message');
        const userListElement = document.getElementById('user-list');

        function fetchUsers() {
            // Using fetch() API for GET request
            fetch('get_users.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(users => {
                    userListElement.innerHTML = '';
                    users.forEach(user => {
                        if (user.id != currentUserId) {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = `<a href="#" data-user-id="${user.id}" data-username="${user.username}">${user.username}</a>`;
                            if (user.id == chatWithId) {
                                listItem.classList.add('active-chat');
                            }
                            userListElement.appendChild(listItem);
                        }
                    });
                    addClickHandlersToUserList();
                })
                .catch(e => {
                    console.error('Error fetching users:', e);
                });
        }

        function addClickHandlersToUserList() {
            const userLinks = userListElement.querySelectorAll('a[data-user-id]');
            userLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const currentActive = userListElement.querySelector('.active-chat');
                    if (currentActive) {
                        currentActive.classList.remove('active-chat');
                    }

                    this.closest('li').classList.add('active-chat');

                    chatWithId = this.dataset.userId;
                    chatPartnerName.textContent = this.dataset.username;

                    chatInputDiv.style.display = 'flex';
                    noChatMessage.style.display = 'none';

                    if (chatInterval) {
                        clearInterval(chatInterval);
                    }

                    fetchMessages();
                    chatInterval = setInterval(fetchMessages, 1000);
                });
            });
        }


        function fetchMessages() {
            if (!chatWithId) {
                chatBox.innerHTML = '';
                noChatMessage.style.display = 'block';
                chatInputDiv.style.display = 'none';
                return;
            }

            chatInputDiv.style.display = 'flex';
            noChatMessage.style.display = 'none';

            // Using fetch() API for GET request
            fetch(`get_messages.php?sender_id=${currentUserId}&receiver_id=${chatWithId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(messages => {
                    const currentScrollHeight = chatBox.scrollHeight;
                    const currentScrollTop = chatBox.scrollTop;
                    const clientHeight = chatBox.clientHeight;
                    const shouldScroll = (currentScrollTop + clientHeight) >= (currentScrollHeight - 10);

                    chatBox.innerHTML = '';
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

                    if (shouldScroll) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                })
                .catch(e => {
                    console.error('Error fetching messages:', e);
                });
        }

        function sendMessage() {
            if (!chatWithId) return;

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
                    messageInput.value = '';
                    fetchMessages();
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

        fetchUsers();
        setInterval(fetchUsers, 5000);
    </script>
</body>
</html>