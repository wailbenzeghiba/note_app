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

        .ttt-cell.playable {
            outline: 2px solid #b6e7a0;
            box-shadow: 0 0 6px #b6e7a088;
            position: relative;
            z-index: 1;
        }
        .ttt-cell.playable::after {
            content: "▼";
            color: #b6e7a0;
            font-size: 1.1rem;
            opacity: 0.7;
            position: absolute;
            top: -1.1rem;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
            font-weight: bold;
        }

        #memory-board {
            display: grid;
            grid-template-columns: repeat(4, 60px); /* 4 columns for 18 tiles */
            grid-gap: 8px;
            justify-content: center;
            margin-bottom: 1.2rem;
        }
        #memory-board .ttt-cell {
            width: 60px;
            height: 60px;
            font-size: 2rem;
            text-align: center;
            line-height: 60px;
            border-radius: 10px;
            border: 2px solid #e0c878;
            background: #f9f6e3;
            transition: background 0.2s;
            user-select: none;
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

<!-- Connect 4 Button -->
<button id="start-connect4" style="margin-top: 1rem; background-color: #ffec88; padding: 0.5rem; border-radius: 6px;">Start Connect 4</button>

<!-- Memory Game Button -->
<button id="start-memory" style="margin-top: 1rem; background-color: #ffec88; padding: 0.5rem; border-radius: 6px;">Start Memory</button>

<!-- Wordle Game Button -->
<button id="start-wordle" style="margin-top: 1rem; background-color: #ffec88; padding: 0.5rem; border-radius: 6px;">Start Wordle</button>

<!-- Hangman Game Button -->
<button id="start-hangman" style="margin-top: 1rem; background-color: #ffec88; padding: 0.5rem; border-radius: 6px;">Start Hangman</button>

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

<!-- Connect 4 Overlay and Game -->
<div id="connect4-overlay" style="display:none; position:fixed; z-index:1000; left:0;top:0;right:0;bottom:0; background:rgba(255,255,200,0.3); justify-content:center; align-items:center;">
    <div id="connect4-container" style="background:#fffbe6; border:3px solid #e0c878; border-radius:18px; padding:2.5rem; min-width:420px; min-height:520px; display:flex; flex-direction:column; align-items:center;">
        <div id="connect4-board" style="display:grid; grid-template-columns:repeat(7,60px); grid-gap:8px; margin-bottom:1.2rem;"></div>
        <div id="connect4-message" style="text-align:center; margin-top:10px; font-size:1.2rem; color:#5a3b00; font-weight:bold; letter-spacing:1px;"></div>
        <div style="text-align:center;">
            <button id="connect4-restart" style="margin:18px 10px 0 10px; background-color:#fff4a3; border:2px solid #e0c878; color:#5a3b00; padding:0.5rem 1.2rem; border-radius:8px; font-weight:bold; box-shadow:2px 2px 3px rgba(0,0,0,0.08); cursor:pointer; font-size:1rem;">Restart Game</button>
            <button id="connect4-leave" style="margin:18px 10px 0 10px; background-color:#fff4a3; border:2px solid #e0c878; color:#5a3b00; padding:0.5rem 1.2rem; border-radius:8px; font-weight:bold; box-shadow:2px 2px 3px rgba(0,0,0,0.08); cursor:pointer; font-size:1rem;">Leave Game</button>
        </div>
    </div>
</div>

<!-- Memory Game Overlay -->
<div id="memory-overlay" style="display:none; position:fixed; z-index:1000; left:0;top:0;right:0;bottom:0; background:rgba(255,255,200,0.3); justify-content:center; align-items:center;">
    <div id="memory-container" style="background:#fffbe6; border:3px solid #e0c878; border-radius:18px; padding:2.5rem; min-width:420px; min-height:520px; display:flex; flex-direction:column; align-items:center;">
        <div id="memory-board" style="display:grid; grid-template-columns:repeat(4,60px); grid-gap:8px; margin-bottom:1.2rem;"></div>
        <div id="memory-message" style="text-align:center; margin-top:10px; font-size:1.2rem; color:#5a3b00; font-weight:bold; letter-spacing:1px;"></div>
        <div style="text-align:center;">
            <button id="memory-restart" style="margin:18px 10px 0 10px;">Restart Game</button>
            <button id="memory-leave" style="margin:18px 10px 0 10px;">Leave Game</button>
        </div>
    </div>
</div>

<!-- Wordle Game Overlay -->
<div id="wordle-overlay" style="display:none; position:fixed; z-index:1000; left:0;top:0;right:0;bottom:0; background:rgba(255,255,200,0.3); justify-content:center; align-items:center;">
    <div id="wordle-container" style="background:#fffbe6; border:3px solid #e0c878; border-radius:18px; padding:2.5rem; min-width:420px; min-height:520px; display:flex; flex-direction:column; align-items:center;">
        <div id="wordle-board" style="display:flex; flex-direction:column; align-items:center; margin-bottom:1.2rem;"></div>
        <div id="wordle-message" style="text-align:center; margin-top:10px; font-size:1.2rem; color:#5a3b00; font-weight:bold; letter-spacing:1px;"></div>
        <form id="wordle-form" style="display:flex; flex-direction:column; align-items:center; margin-top:10px;">
            <input type="text" id="wordle-input" maxlength="5" style="font-size:1.5rem; text-transform:uppercase; padding:0.5rem; border:2px solid #e0c878; border-radius:8px; margin-bottom:10px;">
            <button type="submit" style="background-color:#fff4a3; border:2px solid #e0c878; color:#5a3b00; padding:0.5rem 1.2rem; border-radius:8px; font-weight:bold; box-shadow:2px 2px 3px rgba(0,0,0,0.08); cursor:pointer; font-size:1rem;">Submit Guess</button>
        </form>
        <div style="text-align:center;">
            <button id="wordle-restart" style="margin:18px 10px 0 10px;">Restart Game</button>
            <button id="wordle-leave" style="margin:18px 10px 0 10px;">Leave Game</button>
        </div>
    </div>
</div>

<!-- Hangman Game Overlay -->
<div id="hangman-overlay" style="display:none; position:fixed; z-index:1000; left:0;top:0;right:0;bottom:0; background:rgba(255,255,200,0.3); justify-content:center; align-items:center;">
    <div id="hangman-container" style="background:#fffbe6; border:3px solid #e0c878; border-radius:18px; padding:2.5rem; min-width:420px; min-height:340px; display:flex; flex-direction:column; align-items:center;">
        <div id="hangman-word" style="font-size:2rem; letter-spacing:0.5rem; margin-bottom:1.2rem;"></div>
        <div id="hangman-letters" style="margin-bottom:1rem;"></div>
        <div id="hangman-status" style="font-size:1.1rem; color:#5a3b00; font-weight:bold; margin-bottom:1rem;"></div>
        <form id="hangman-form" style="display:flex; gap:0.5rem; margin-bottom:1rem;">
            <input type="text" id="hangman-input" maxlength="1" style="font-size:1.5rem; text-transform:uppercase; padding:0.5rem; border:2px solid #e0c878; border-radius:8px;">
            <button type="submit" style="background-color:#fff4a3; border:2px solid #e0c878; color:#5a3b00; padding:0.5rem 1.2rem; border-radius:8px; font-weight:bold; box-shadow:2px 2px 3px rgba(0,0,0,0.08); cursor:pointer; font-size:1rem;">Guess</button>
        </form>
        <div style="text-align:center;">
            <button id="hangman-restart" style="margin:18px 10px 0 10px;">Restart Game</button>
            <button id="hangman-leave" style="margin:18px 10px 0 10px;">Leave Game</button>
        </div>
    </div>
</div>

<!-- Add your WAV file -->
<audio id="message-sound" src="sounds/random.wav" preload="auto"></audio>

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
        // Show the previous player as the winner
        let winner = tttGame.current_player === 'X' ? 'O' : 'X';
        tttMessage.textContent = "Player " + winner + " wins!";
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
        // Play sound after sending message
        if (messageSound) {
            messageSound.currentTime = 0;
            messageSound.play();
        }
    });
});

setInterval(fetchMessages, 2000);
fetchMessages();

const startConnect4Button = document.getElementById('start-connect4');
const connect4Overlay = document.getElementById('connect4-overlay');
const connect4Container = document.getElementById('connect4-container');
const connect4Board = document.getElementById('connect4-board');
const connect4Message = document.getElementById('connect4-message');
const connect4Restart = document.getElementById('connect4-restart');
const connect4Leave = document.getElementById('connect4-leave');

let connect4Game = null;
let connect4Interval = null;

// Show Connect 4 UI and blur background
startConnect4Button.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4').forEach(el => {
        el.classList.add('blur-bg');
    });
    connect4Overlay.style.display = 'flex';
    startConnect4Button.style.display = 'none';
    fetchConnect4();
    if (connect4Interval) clearInterval(connect4Interval);
    connect4Interval = setInterval(fetchConnect4, 2000);
});

// Restart Connect 4
connect4Restart.addEventListener('click', () => {
    fetch('connect4_restart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}`
    })
    .then(res => res.json())
    .then(() => {
        fetchConnect4(); // This will trigger creation of a new game in 
    });
});

// Leave Connect 4
connect4Leave.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4').forEach(el => {
        el.classList.remove('blur-bg');
    });
    connect4Overlay.style.display = 'none';
    startConnect4Button.style.display = '';
    if (connect4Interval) clearInterval(connect4Interval);
});

// Fetch Connect 4 game state
function fetchConnect4() {
    fetch('connect4_get.php?room_id=' + roomId)
        .then(res => res.json())
        .then(game => {
            connect4Game = game;
            renderConnect4();
        });
}

// Render Connect 4 board and message
function renderConnect4() {
    if (!connect4Game) return;
    connect4Board.innerHTML = '';
    const boardArr = connect4Game.board.split('');
    // Find the playable row for each column
    let playableRows = Array(7).fill(-1);
    for (let col = 0; col < 7; col++) {
        for (let row = 5; row >= 0; row--) {
            if ((boardArr[row * 7 + col] || ' ') === ' ') {
                playableRows[col] = row;
                break;
            }
        }
    }
    for (let row = 0; row < 6; row++) {
        for (let col = 0; col < 7; col++) {
            const idx = row * 7 + col;
            const cell = boardArr[idx] || ' ';
            const isPlayable = (row === playableRows[col]) &&
                connect4Game.game_status === 'active' &&
                (
                    (connect4Game.current_player === 'R' && userId == connect4Game.player1_id) ||
                    (connect4Game.current_player === 'Y' && userId == connect4Game.player2_id)
                );
            const cellDiv = document.createElement('div');
            cellDiv.className = 'ttt-cell' + (cell.trim() ? ' taken' : '') + (isPlayable ? ' playable' : '');
            cellDiv.style.backgroundColor = cell === 'R' ? '#ff6666' : cell === 'Y' ? '#ffe066' : '#f9f6e3';
            cellDiv.textContent = '';
            if (cell === 'R' || cell === 'Y') {
                cellDiv.innerHTML = `<span style="font-size:2.2rem;">${cell === 'R' ? '🔴' : '🟡'}</span>`;
            }
            if (isPlayable) {
                cellDiv.style.cursor = 'pointer';
                cellDiv.onclick = () => makeConnect4Move(col);
            } else {
                cellDiv.style.cursor = 'default';
                cellDiv.onclick = null;
            }
            connect4Board.appendChild(cellDiv);
        }
    }

    // Always show the turn or winner message
    if (connect4Game.game_status === 'active') {
        let turnText = '';
        if (connect4Game.current_player === 'R') {
            turnText = (userId == connect4Game.player1_id) ? "Your turn (🔴)" : "Player 1's turn (🔴)";
        } else {
            turnText = (userId == connect4Game.player2_id) ? "Your turn (🟡)" : "Player 2's turn (🟡)";
        }
        connect4Message.textContent = turnText;
    } else if (connect4Game.game_status === 'won') {
        connect4Message.textContent = "Player " + (connect4Game.current_player === 'R' ? '🟡' : '🔴') + " wins!";
    } else if (connect4Game.game_status === 'tie') {
        connect4Message.textContent = "It's a tie!";
    }
}

// Helper: get the lowest available row in a column
function getAvailableRow(boardArr, col) {
    for (let row = 5; row >= 0; row--) {
        if ((boardArr[row * 7 + col] || ' ') === ' ') return row;
    }
    return -1;
}

// Make a move in Connect 4
function makeConnect4Move(col) {
    fetch('connect4_move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&col=${col}`
    })
    .then(res => res.json())
    .then(game => {
        connect4Game = game;
        renderConnect4();
    });
}

const startMemoryButton = document.getElementById('start-memory');
const memoryOverlay = document.getElementById('memory-overlay');
const memoryBoard = document.getElementById('memory-board');
const memoryMessage = document.getElementById('memory-message');
const memoryRestart = document.getElementById('memory-restart');
const memoryLeave = document.getElementById('memory-leave');

let memoryGame = null;
let memoryInterval = null;

startMemoryButton.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4, #start-memory').forEach(el => {
        el.classList.add('blur-bg');
    });
    memoryOverlay.style.display = 'flex';
    startMemoryButton.style.display = 'none';
    fetchMemory();
    if (memoryInterval) clearInterval(memoryInterval);
    memoryInterval = setInterval(fetchMemory, 2000);
});

memoryRestart.addEventListener('click', () => {
    fetch('memory_restart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}`
    })
    .then(res => res.json())
    .then(() => {
        fetchMemory();
    });
});

memoryLeave.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4, #start-memory').forEach(el => {
        el.classList.remove('blur-bg');
    });
    memoryOverlay.style.display = 'none';
    startMemoryButton.style.display = '';
    if (memoryInterval) clearInterval(memoryInterval);
});

function fetchMemory() {
    fetch('memory_get.php?room_id=' + roomId)
        .then(res => res.json())
        .then(game => {
            memoryGame = game;
            renderMemory();
        });
}

function renderMemory() {
    if (!memoryGame) return;
    memoryBoard.innerHTML = '';
    const boardArr = memoryGame.board.split('');
    const revealedArr = memoryGame.revealed.split('');
    let revealedCount = 0;
    // Render 20 cells (4x5 grid)
    for (let i = 0; i < 20; i++) {
        const cellDiv = document.createElement('div');
        cellDiv.className = 'ttt-cell';
        if (revealedArr[i] === '2') {
            // Found pair: green background, bold, different border
            cellDiv.textContent = boardArr[i];
            cellDiv.style.backgroundColor = '#b6e7a0';
            cellDiv.style.color = '#226600';
            cellDiv.style.fontWeight = 'bold';
            cellDiv.style.border = '2px solid #7fc97f';
            cellDiv.style.boxShadow = '0 0 8px #b6e7a088';
            revealedCount++;
        } else if (revealedArr[i] === '1') {
            // Currently revealed: yellow background
            cellDiv.textContent = boardArr[i];
            cellDiv.style.backgroundColor = '#fff4a3';
            cellDiv.style.color = '#5a3b00';
            cellDiv.style.fontWeight = 'bold';
            revealedCount++;
        } else if (typeof boardArr[i] !== 'undefined') {
            cellDiv.textContent = '';
            cellDiv.style.backgroundColor = '#f9f6e3';
            if (memoryGame.current_player == userId && memoryGame.game_status === 'active') {
                cellDiv.style.cursor = 'pointer';
                cellDiv.onclick = () => makeMemoryMove(i);
            } else {
                cellDiv.style.cursor = 'default';
                cellDiv.onclick = null;
            }
        } else {
            cellDiv.style.backgroundColor = 'transparent';
            cellDiv.style.border = 'none';
            cellDiv.style.cursor = 'default';
        }
        memoryBoard.appendChild(cellDiv);
    }

    // --- Hints and status ---
    if (memoryGame.game_status === 'active') {
        let hint = '';
        // Count how many cards are currently revealed for this turn
        let turnRevealed = 0;
        for (let i = 0; i < revealedArr.length; i++) {
            if (
                (memoryGame.current_player == memoryGame.player1_id && revealedArr[i] === '1') ||
                (memoryGame.current_player == memoryGame.player2_id && revealedArr[i] === '1')
            ) {
                turnRevealed++;
            }
        }
        if (memoryGame.current_player == userId) {
            if (turnRevealed === 0) {
                hint = "Pick a card to reveal.";
            } else if (turnRevealed === 1) {
                hint = "Pick another card to find a match.";
            } else {
                hint = "Wait for cards to flip or for your turn.";
            }
        } else {
            hint = "Wait for your opponent to play.";
        }
        memoryMessage.textContent =
            (memoryGame.current_player == userId ? "Your turn" : "Opponent's turn") +
            ` | Score: You ${memoryGame.scores.split(',')[memoryGame.player1_id == userId ? 0 : 1]}, Opponent ${memoryGame.scores.split(',')[memoryGame.player1_id == userId ? 1 : 0]}` +
            (hint ? " | Hint: " + hint : "");
    } else if (memoryGame.game_status === 'won') {
        memoryMessage.textContent = "Game over! " + (memoryGame.scores.split(',')[0] > memoryGame.scores.split(',')[1] ? "Player 1 wins!" : "Player 2 wins!");
    } else if (memoryGame.game_status === 'tie') {
        memoryMessage.textContent = "It's a tie!";
    }
}

function makeMemoryMove(idx) {
    fetch('memory_move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&cell=${idx}`
    })
    .then(res => res.json())
    .then(game => {
        memoryGame = game;
        renderMemory();
    });
}

// --- Memory Game Overlay Buttons UI ---
memoryRestart.style.backgroundColor = "#fff4a3";
memoryRestart.style.border = "2px solid #e0c878";
memoryRestart.style.color = "#5a3b00";
memoryRestart.style.padding = "0.5rem 1.2rem";
memoryRestart.style.borderRadius = "8px";
memoryRestart.style.fontWeight = "bold";
memoryRestart.style.boxShadow = "2px 2px 3px rgba(0,0,0,0.08)";
memoryRestart.style.cursor = "pointer";
memoryRestart.style.fontSize = "1rem";
memoryRestart.onmouseover = () => memoryRestart.style.backgroundColor = "#ffec88";
memoryRestart.onmouseout = () => memoryRestart.style.backgroundColor = "#fff4a3";

memoryLeave.style.backgroundColor = "#fff4a3";
memoryLeave.style.border = "2px solid #e0c878";
memoryLeave.style.color = "#5a3b00";
memoryLeave.style.padding = "0.5rem 1.2rem";
memoryLeave.style.borderRadius = "8px";
memoryLeave.style.fontWeight = "bold";
memoryLeave.style.boxShadow = "2px 2px 3px rgba(0,0,0,0.08)";
memoryLeave.style.cursor = "pointer";
memoryLeave.style.fontSize = "1rem";
memoryLeave.onmouseover = () => memoryLeave.style.backgroundColor = "#ffec88";
memoryLeave.onmouseout = () => memoryLeave.style.backgroundColor = "#fff4a3";

// --- Wordle Game Logic ---
const startWordleButton = document.getElementById('start-wordle');
const wordleOverlay = document.getElementById('wordle-overlay');
const wordleBoard = document.getElementById('wordle-board');
const wordleMessage = document.getElementById('wordle-message');
const wordleForm = document.getElementById('wordle-form');
const wordleInput = document.getElementById('wordle-input');
const wordleRestart = document.getElementById('wordle-restart');
const wordleLeave = document.getElementById('wordle-leave');

let wordleGame = null;
let wordleInterval = null;

startWordleButton.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4, #start-memory, #start-wordle').forEach(el => {
        el.classList.add('blur-bg');
    });
    wordleOverlay.style.display = 'flex';
    startWordleButton.style.display = 'none';
    fetchWordle();
    if (wordleInterval) clearInterval(wordleInterval);
    wordleInterval = setInterval(fetchWordle, 2000);
});

wordleRestart.addEventListener('click', () => {
    fetch('wordle_restart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}`
    })
    .then(res => res.json())
    .then(() => {
        fetchWordle();
    });
});

wordleLeave.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4, #start-memory, #start-wordle').forEach(el => {
        el.classList.remove('blur-bg');
    });
    wordleOverlay.style.display = 'none';
    startWordleButton.style.display = '';
    if (wordleInterval) clearInterval(wordleInterval);
});

function fetchWordle() {
    fetch('wordle_get.php?room_id=' + roomId)
        .then(res => res.json())
        .then(game => {
            wordleGame = game;
            renderWordle();
        });
}

function renderWordle() {
    if (!wordleGame) return;
    wordleBoard.innerHTML = '';
    for (let row = 0; row < 6; row++) {
        const guess = wordleGame.guesses[row] || '';
        const result = wordleGame.results[row] || '';
        const rowDiv = document.createElement('div');
        rowDiv.style.display = 'flex';
        for (let col = 0; col < 5; col++) {
            const letter = guess[col] ? guess[col].toUpperCase() : '';
            const cellDiv = document.createElement('div');
            cellDiv.style.width = '48px';
            cellDiv.style.height = '48px';
            cellDiv.style.margin = '2px';
            cellDiv.style.display = 'flex';
            cellDiv.style.alignItems = 'center';
            cellDiv.style.justifyContent = 'center';
            cellDiv.style.fontSize = '2rem';
            cellDiv.style.fontWeight = 'bold';
            cellDiv.style.border = '2px solid #e0c878';
            cellDiv.style.borderRadius = '8px';
            cellDiv.style.background = '#f9f6e3';
            if (result[col] === 'G') cellDiv.style.background = '#b6e7a0'; // Green
            else if (result[col] === 'Y') cellDiv.style.background = '#fff4a3'; // Yellow
            else if (result[col] === 'B') cellDiv.style.background = '#e0e0e0'; // Gray
            cellDiv.textContent = letter;
            rowDiv.appendChild(cellDiv);
        }
        wordleBoard.appendChild(rowDiv);
    }

    // --- Hints and status ---
    let hint = '';
    if (wordleGame.status === 'won') {
        wordleMessage.textContent = "🎉 You guessed it! The word was " + wordleGame.word.toUpperCase();
        wordleForm.style.display = 'none';
    } else if (wordleGame.status === 'lost') {
        wordleMessage.textContent = "❌ Out of tries! The word was " + wordleGame.word.toUpperCase();
        wordleForm.style.display = 'none';
    } else {
        // Show hints: number of guesses left and used letters
        const guessesLeft = 6 - (wordleGame.guesses ? wordleGame.guesses.length : 0);
        let usedLetters = '';
        if (wordleGame.guesses && wordleGame.guesses.length > 0) {
            usedLetters = Array.from(
                new Set(wordleGame.guesses.join('').toUpperCase().split(''))
            ).sort().join(' ');
        }
        hint = `Guesses left: ${guessesLeft}` + (usedLetters ? ` | Used letters: ${usedLetters}` : '');
        wordleMessage.textContent = "Guess the 5-letter word!" + (hint ? " | " + hint : "");
        wordleForm.style.display = '';
    }
}

wordleForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const guess = wordleInput.value.trim().toUpperCase();
    if (guess.length !== 5) {
        wordleMessage.textContent = "Enter a 5-letter word!";
        return;
    }
    fetch('wordle_move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&guess=${guess}`
    })
    .then(res => res.json())
    .then(game => {
        wordleGame = game;
        renderWordle();
        wordleInput.value = ''; // <-- Only clear after submit
    });
});

// --- Wordle Game Overlay Buttons UI ---
wordleRestart.style.backgroundColor = "#fff4a3";
wordleRestart.style.border = "2px solid #e0c878";
wordleRestart.style.color = "#5a3b00";
wordleRestart.style.padding = "0.5rem 1.2rem";
wordleRestart.style.borderRadius = "8px";
wordleRestart.style.fontWeight = "bold";
wordleRestart.style.boxShadow = "2px 2px 3px rgba(0,0,0,0.08)";
wordleRestart.style.cursor = "pointer";
wordleRestart.style.fontSize = "1rem";
wordleRestart.onmouseover = () => wordleRestart.style.backgroundColor = "#ffec88";
wordleRestart.onmouseout = () => wordleRestart.style.backgroundColor = "#fff4a3";

wordleLeave.style.backgroundColor = "#fff4a3";
wordleLeave.style.border = "2px solid #e0c878";
wordleLeave.style.color = "#5a3b00";
wordleLeave.style.padding = "0.5rem 1.2rem";
wordleLeave.style.borderRadius = "8px";
wordleLeave.style.fontWeight = "bold";
wordleLeave.style.boxShadow = "2px 2px 3px rgba(0,0,0,0.08)";
wordleLeave.style.cursor = "pointer";
wordleLeave.style.fontSize = "1rem";
wordleLeave.onmouseover = () => wordleLeave.style.backgroundColor = "#ffec88";
wordleLeave.onmouseout = () => wordleLeave.style.backgroundColor = "#fff4a3";

// --- Hangman Game Logic ---
const startHangmanButton = document.getElementById('start-hangman');
const hangmanOverlay = document.getElementById('hangman-overlay');
const hangmanWord = document.getElementById('hangman-word');
const hangmanLetters = document.getElementById('hangman-letters');
const hangmanStatus = document.getElementById('hangman-status');
const hangmanForm = document.getElementById('hangman-form');
const hangmanInput = document.getElementById('hangman-input');
const hangmanRestart = document.getElementById('hangman-restart');
const hangmanLeave = document.getElementById('hangman-leave');

let hangmanGame = null;
let hangmanInterval = null;

startHangmanButton.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4, #start-memory, #start-wordle, #start-hangman').forEach(el => {
        el.classList.add('blur-bg');
    });
    hangmanOverlay.style.display = 'flex';
    startHangmanButton.style.display = 'none';
    fetchHangman();
    if (hangmanInterval) clearInterval(hangmanInterval);
    hangmanInterval = setInterval(fetchHangman, 2000);
});

hangmanRestart.addEventListener('click', () => {
    fetch('hangman_restart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}`
    })
    .then(res => res.json())
    .then(() => {
        fetchHangman();
    });
});

hangmanLeave.addEventListener('click', () => {
    document.body.querySelectorAll('header, #chat-box, #message-form, #start-tictactoe, #start-connect4, #start-memory, #start-wordle, #start-hangman').forEach(el => {
        el.classList.remove('blur-bg');
    });
    hangmanOverlay.style.display = 'none';
    startHangmanButton.style.display = '';
    if (hangmanInterval) clearInterval(hangmanInterval);
});

function fetchHangman() {
    fetch('hangman_get.php?room_id=' + roomId)
        .then(res => res.json())
        .then(game => {
            hangmanGame = game;
            renderHangman();
        });
}

function renderHangman() {
    if (!hangmanGame) return;
    // Show word with underscores for unguessed letters
    let displayWord = '';
    for (let ch of hangmanGame.word) {
        displayWord += hangmanGame.guessed.includes(ch) ? ch + ' ' : '_ ';
    }
    hangmanWord.textContent = displayWord.trim();

    // Show guessed letters
    hangmanLetters.textContent = "Guessed: " + (hangmanGame.guessed.length ? hangmanGame.guessed.join(' ') : '-');

    // Status
    if (hangmanGame.status === 'won') {
        hangmanStatus.textContent = "🎉 You guessed it! The word was " + hangmanGame.word.toUpperCase();
        hangmanForm.style.display = 'none';
    } else if (hangmanGame.status === 'lost') {
        hangmanStatus.textContent = "❌ Out of tries! The word was " + hangmanGame.word.toUpperCase();
        hangmanForm.style.display = 'none';
    } else {
        hangmanStatus.textContent = `Tries left: ${hangmanGame.tries_left}`;
        hangmanForm.style.display = '';
    }
}

hangmanForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const letter = hangmanInput.value.trim().toUpperCase();
    if (!letter.match(/^[A-Z]$/)) {
        hangmanStatus.textContent = "Enter a single letter!";
        return;
    }
    fetch('hangman_move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&letter=${letter}`
    })
    .then(res => res.json())
    .then(game => {
        hangmanGame = game;
        renderHangman();
        hangmanInput.value = '';
    });
});

// --- Hangman Game Overlay Buttons UI ---
hangmanRestart.style.backgroundColor = "#fff4a3";
hangmanRestart.style.border = "2px solid #e0c878";
hangmanRestart.style.color = "#5a3b00";
hangmanRestart.style.padding = "0.5rem 1.2rem";
hangmanRestart.style.borderRadius = "8px";
hangmanRestart.style.fontWeight = "bold";
hangmanRestart.style.boxShadow = "2px 2px 3px rgba(0,0,0,0.08)";
hangmanRestart.style.cursor = "pointer";
hangmanRestart.style.fontSize = "1rem";
hangmanRestart.onmouseover = () => hangmanRestart.style.backgroundColor = "#ffec88";
hangmanRestart.onmouseout = () => hangmanRestart.style.backgroundColor = "#fff4a3";

hangmanLeave.style.backgroundColor = "#fff4a3";
hangmanLeave.style.border = "2px solid #e0c878";
hangmanLeave.style.color = "#5a3b00";
hangmanLeave.style.padding = "0.5rem 1.2rem";
hangmanLeave.style.borderRadius = "8px";
hangmanLeave.style.fontWeight = "bold";
hangmanLeave.style.boxShadow = "2px 2px 3px rgba(0,0,0,0.08)";
hangmanLeave.style.cursor = "pointer";
hangmanLeave.style.fontSize = "1rem";
hangmanLeave.onmouseover = () => hangmanLeave.style.backgroundColor = "#ffec88";
hangmanLeave.onmouseout = () => hangmanLeave.style.backgroundColor = "#fff4a3";
</script>

</body>
</html>

