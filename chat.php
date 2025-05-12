<?php
require 'includes/db.php';
require 'includes/auth.php';

$user_id = $_SESSION['user_id'];

// Update status if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $allowed = ['online', 'offline', 'dnd'];
    $status = in_array($_POST['status'], $allowed) ? $_POST['status'] : 'offline';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $user_id]);
}

// Get current user status
$stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_status = $stmt->fetchColumn();

// Handle user search
$search_username = $_GET['search'] ?? '';
$search_results = [];
if ($search_username) {
    $stmt = $pdo->prepare("SELECT id, username, status FROM users WHERE username LIKE ? AND id != ?");
    $stmt->execute(["%$search_username%", $user_id]);
    $search_results = $stmt->fetchAll();
}

// Get incoming friend requests
$stmt = $pdo->prepare("SELECT fr.id, u.username, fr.sender_id FROM friend_requests fr JOIN users u ON fr.sender_id = u.id WHERE fr.receiver_id = ? AND fr.status = 'pending'");
$stmt->execute([$user_id]);
$incoming_requests = $stmt->fetchAll();

// Get chat rooms
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE user1_id = ? OR user2_id = ?");
$stmt->execute([$user_id, $user_id]);
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat</title>
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

        a {
            color: #7b3f00;
            text-decoration: none;
            font-weight: bold;
            margin-right: 1rem;
        }

        a:hover {
            text-decoration: underline;
        }

        button {
            background-color: #fff4a3;
            border: 2px solid #e0c878;
            color: #5a3b00;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 2px 2px 3px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        button:hover {
            background-color: #ffec88;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background: #fffdf3;
            border-left: 5px solid #e6b800;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            line-height: 1.6;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent,
                transparent 28px,
                #f2e59e 29px
            );
        }

        .search-results {
            margin-bottom: 1rem;
        }

        .incoming-requests, .chat-rooms {
            margin-top: 2rem;
        }

        .incoming-requests ul, .chat-rooms ul {
            padding: 0;
        }

        .note-actions {
            margin-top: 0.7rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<header>
    <h2>💬 Chat</h2>
    <div>
        <form method="post" style="display:inline; margin-right: 1rem;">
            <select name="status" onchange="this.form.submit()">
                <option value="online" <?= ($current_status == 'online') ? 'selected' : '' ?>>🟢 Online</option>
                <option value="offline" <?= ($current_status == 'offline') ? 'selected' : '' ?>>⚫ Offline</option>
                <option value="dnd" <?= ($current_status == 'dnd') ? 'selected' : '' ?>>🔴 Do Not Disturb</option>
            </select>
        </form>
        <a href="dashboard.php">📒 Notes</a>
        <a href="logout.php">Logout</a>
    </div>
</header>

<h3>🔍 Search for a User</h3>
<form method="get">
    <input type="text" name="search" placeholder="Username" value="<?= htmlspecialchars($search_username) ?>" style="padding: 0.5rem; border: 2px solid #e0c878; border-radius: 6px; font-size: 0.8rem;">
    <button type="submit">Search</button>
</form>

<?php if ($search_results): ?>
    <h3>Results:</h3>
    <ul class="search-results">
        <?php foreach ($search_results as $user): ?>
            <li id="user-status-<?= $user['id'] ?>">
                <?= htmlspecialchars($user['username']) ?> <small>(<span class="status"><?= htmlspecialchars($user['status']) ?></span>)</small>
                <button onclick="sendFriendRequest(<?= $user['id'] ?>)">Send Request</button>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<hr>

<h3>📨 Incoming Requests</h3>
<ul class="incoming-requests">
    <?php foreach ($incoming_requests as $req): ?>
        <li>
            <?= htmlspecialchars($req['username']) ?>
            <form method="post" action="handle_request.php" style="display:inline">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <input type="hidden" name="sender_id" value="<?= $req['sender_id'] ?>">
                <button name="action" value="accept">Accept</button>
                <button name="action" value="deny">Deny</button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>

<hr>

<h2>💬 Your Chat Rooms</h2>
<ul class="chat-rooms">
    <?php foreach ($rooms as $room): 
        $other_id = ($room['user1_id'] == $user_id) ? $room['user2_id'] : $room['user1_id'];
        $stmtUser = $pdo->prepare("SELECT username, status FROM users WHERE id = ?");
        $stmtUser->execute([$other_id]);
        $other = $stmtUser->fetch();
    ?>
        <li id="user-status-<?= $other_id ?>">
            <a href="chat_room.php?room=<?= $room['id'] ?>">Chat with <?= htmlspecialchars($other['username']) ?> <small>(<span class="status"><?= htmlspecialchars($other['status']) ?></span>)</small></a>
        </li>
    <?php endforeach; ?>
</ul>

<script>
function sendFriendRequest(receiverId) {
    const formData = new FormData();
    formData.append('receiver_id', receiverId);

    fetch('send_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong. Please try again.');
    });
}

function fetchUserStatuses() {
    fetch('get_statuses.php')
        .then(response => response.json())
        .then(users => {
            users.forEach(user => {
                const el = document.getElementById('user-status-' + user.id);
                if (el) {
                    const statusSpan = el.querySelector('.status');
                    if (statusSpan) {
                        statusSpan.textContent = user.status;
                    }
                }
            });
        });
}

setInterval(fetchUserStatuses, 5000);
window.addEventListener('load', fetchUserStatuses);
</script>

</body>
</html>
