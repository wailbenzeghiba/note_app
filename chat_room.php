<?php
require 'includes/db.php';
require 'includes/auth.php';

$room_id = $_GET['room'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$room_id) {
    die('Invalid chat room.');
}

// Make sure user is allowed in this room
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
$stmt->execute([$room_id, $user_id, $user_id]);
$chat_room = $stmt->fetch();

if (!$chat_room) {
    die('Access denied.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat Room</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #fdf6c9;
            margin: 0;
            padding: 2rem;
            color: #333;
            position: relative;
            min-height: 100vh;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #ccc;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        h2 {
            margin: 0;
            font-size: 2rem;
            color: #3e3e00;
        }

        .header-buttons {
            display: flex;
            gap: 1rem;
            margin-left: auto; /* Ensures that the buttons are pushed to the right */
        }

        .add-note, .back-to-chat, a {
            background-color: #fff4a3;
            border: 2px solid #e0c878;
            color: #5a3b00;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 2px 2px 3px rgba(0,0,0,0.1);
            text-decoration: none; /* Ensures no underline */
        }

        .add-note:hover, .back-to-chat:hover, a:hover {
            background-color: #ffec88;
        }

        #chat-box {
            background: #fffdf3;
            border: 1px solid #ccc;
            padding: 1rem;
            height: 400px;
            overflow-y: scroll;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .message {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-radius: 5px;
        }

        .self {
            color: blue;
            font-weight: bold;
            background-color: #e6f7ff;
        }

        .other {
            color: darkgreen;
            background-color: #e6ffe6;
        }

        #message-form {
            display: flex;
            gap: 0.5rem;
        }

        #message-form input[type="text"] {
            flex: 1;
            padding: 0.5rem;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        #message-form button {
            padding: 0.5rem 1rem;
            background-color: #fff4a3;
            border: 2px solid #e0c878;
            color: #5a3b00;
            font-weight: bold;
            border-radius: 6px;
            box-shadow: 2px 2px 3px rgba(0,0,0,0.1);
        }

        #message-form button:hover {
            background-color: #ffec88;
        }
    </style>
</head>
<body>

<header>
    <h2>💬 Chat Room #<?= htmlspecialchars($room_id) ?></h2>
    <div class="header-buttons">
        <a href="dashboard.php" class="add-note">📒 Notes</a>
        <a href="chat.php" class="back-to-chat">⬅️ Back to Chat</a> <!-- Added the back button -->
        <a href="logout.php">Logout</a>
    </div>
</header>

<div id="chat-box"></div>

<form id="message-form">
    <input type="text" id="message-input" placeholder="Type your message..." autocomplete="off" required>
    <button type="submit">Send</button>
</form>

<!-- Add your WAV file -->
<audio id="message-sound" src="sounds/random.wav" preload="auto"></audio>

<script>
const chatBox = document.getElementById('chat-box');
const messageForm = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const roomId = <?= json_encode($room_id) ?>;
const userId = <?= json_encode($_SESSION['user_id']) ?>;
const messageSound = document.getElementById('message-sound'); // Get the audio element

function fetchMessages() {
    fetch('get_messages.php?room_id=' + roomId)
        .then(response => response.json())
        .then(data => {
            chatBox.innerHTML = '';
            data.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.sender_id == userId ? 'self' : 'other');
                div.textContent = (msg.sender_id == userId ? 'You: ' : 'Them: ') + msg.message;
                chatBox.appendChild(div);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

messageForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message === '') return;

    fetch('send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&message=${encodeURIComponent(message)}`
    }).then(() => {
        messageInput.value = '';
        fetchMessages();
        messageSound.play(); // Play sound after the message is sent
    });
});

setInterval(fetchMessages, 2000);
fetchMessages();
</script>

</body>
</html>
