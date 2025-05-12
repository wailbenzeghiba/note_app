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

        /* Tic-Tac-Toe Styles */
        #tictactoe-container {
            display: block;
            margin: 0;
            position: relative;
            z-index: 1001;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
            background: #fffbe6;
            border: 3px solid #e0c878;
            border-radius: 18px;
            padding: 2.5rem 2.5rem 1.5rem 2.5rem;
            min-width: 340px;
            min-height: 420px;
            max-width: 95vw;
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ttt-board {
            display: grid;
            grid-template-columns: repeat(5, 60px);
            grid-gap: 8px;
            margin-bottom: 1.2rem;
        }

        .ttt-cell {
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f9f6e3;
            border: 2px solid #e0c878;
            font-size: 2.5rem;
            cursor: pointer;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .ttt-cell.taken {
            background-color: #f3e7b7;
            color: #bfa14a;
            cursor: default;
        }

        .ttt-cell:hover:not(.taken) {
            background: #fff4a3;
        }

        .ttt-message {
            text-align: center;
            margin-top: 10px;
            font-size: 1.2rem;
            color: #5a3b00;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Blur effect for background */
        .blur-bg {
            filter: blur(5px);
            pointer-events: none;
            user-select: none;
        }

        /* Overlay for centering the Tic-Tac-Toe */
        #ttt-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 200, 0.3);
            justify-content: center;
            align-items: center;
        }

        /* Restart and Leave Game button styles */
        #ttt-restart, #ttt-leave {
            display: inline-block;
            margin: 18px 10px 0 10px;
            background-color: #fff4a3;
            border: 2px solid #e0c878;
            color: #5a3b00;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 2px 2px 3px rgba(0,0,0,0.08);
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }
        #ttt-restart:hover, #ttt-leave:hover {
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

<!-- Tic-Tac-Toe Button -->
<button id="start-tictactoe" style="margin-top: 1rem; background-color: #ffec88; padding: 0.5rem; border-radius: 6px;">Start Tic-Tac-Toe</button>

<!-- Tic-Tac-Toe Overlay and Game -->
<div id="ttt-overlay">
    <div id="tictactoe-container">
        <div class="ttt-board" id="ttt-board"></div>
        <div class="ttt-message" id="ttt-message"></div>
        <div style="text-align:center;">
            <button id="ttt-restart">Restart Game</button>
            <button id="ttt-leave">Leave Game</button>
        </div>
    </div>
</div>

<!-- Add your WAV file -->
<audio id="message-sound" src="sounds/randomg.wav" preload="auto"></audio>

<script>
const chatBox = document.getElementById('chat-box');
const messageForm = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const roomId = <?= json_encode($room_id) ?>;
const userId = <?= json_encode($_SESSION['user_id']) ?>;
const messageSound = document.getElementById('message-sound'); // Get the audio element

const startTictactoeButton = document.getElementById('start-tictactoe');
const tttOverlay = document.getElementById('ttt-overlay');
const tictactoeContainer = document.getElementById('tictactoe-container');
const tttBoard = document.getElementById('ttt-board');
const tttMessage = document.getElementById('ttt-message');
const tttRestart = document.getElementById('ttt-restart');
const tttLeave = document.getElementById('ttt-leave');

let tttGame = null; // Will hold the fetched game state
let tttInterval = null;

// Show the game UI and blur background
startTictactoeButton.addEventListener('click', () => {
    // Blur everything except overlay
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe').forEach(el => {
        el.classList.add('blur-bg');
    });
    tttOverlay.style.display = 'flex';
    startTictactoeButton.style.display = 'none';
    fetchTicTacToe();
    if (tttInterval) clearInterval(tttInterval);
    tttInterval = setInterval(fetchTicTacToe, 2000); // Poll every 2s
});

// Restart game button
tttRestart.addEventListener('click', () => {
    fetch('tictactoe_restart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}`
    })
    .then(res => res.json())
    .then(() => {
        fetchTicTacToe(); // This will trigger creation of a new game in tictactoe_get.php
    });
});

// Leave game button: unblur and hide overlay, show start button again
tttLeave.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe').forEach(el => {
        el.classList.remove('blur-bg');
    });
    tttOverlay.style.display = 'none';
    startTictactoeButton.style.display = '';
    if (tttInterval) clearInterval(tttInterval);
});

// Fetch game state from server
function fetchTicTacToe() {
    fetch('tictactoe_get.php?room_id=' + roomId)
        .then(res => res.json())
        .then(game => {
            tttGame = game;
            renderBoard();
        });
}

// Render board and message
function renderBoard() {
    if (!tttGame) return;
    tttBoard.innerHTML = '';
    const boardArr = tttGame.board.split('');
    for (let i = 0; i < 25; i++) {
        const cell = boardArr[i] || ' ';
        const cellDiv = document.createElement('div');
        cellDiv.className = 'ttt-cell' + (cell.trim() ? ' taken' : '');
        cellDiv.textContent = cell.trim();
        if (
            tttGame.game_status === 'active' &&
            cell === ' ' &&
            (
                (tttGame.current_player === 'X' && userId == tttGame.player1_id) ||
                (tttGame.current_player === 'O' && userId == tttGame.player2_id)
            )
        ) {
            cellDiv.style.cursor = 'pointer';
            cellDiv.onclick = () => makeMove(i);
        }
        tttBoard.appendChild(cellDiv);
    }

    // Status message
    if (tttGame.game_status === 'active') {
        if (
            (tttGame.current_player === 'X' && userId == tttGame.player1_id) ||
            (tttGame.current_player === 'O' && userId == tttGame.player2_id)
        ) {
            tttMessage.textContent = "Your turn (" + tttGame.current_player + ")";
        } else {
            tttMessage.textContent = "Waiting for opponent...";
        }
    } else if (tttGame.game_status === 'won') {
        tttMessage.textContent = "Player " + tttGame.current_player + " wins!";
    } else if (tttGame.game_status === 'tie') {
        tttMessage.textContent = "It's a tie!";
    }
}

// Make a move
function makeMove(idx) {
    fetch('tictactoe_move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&cell=${idx}`
    })
    .then(res => res.json())
    .then(game => {
        tttGame = game;
        renderBoard();
    });
}

// Fetch Messages
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
