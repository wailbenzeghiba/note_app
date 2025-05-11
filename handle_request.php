<?php
require 'includes/db.php';
require 'includes/auth.php';

$request_id = $_POST['request_id'];
$action = $_POST['action'];
$sender_id = $_POST['sender_id'];
$receiver_id = $_SESSION['user_id'];

if ($action === 'accept') {
    $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?")->execute([$request_id]);

    // Always insert with user1 < user2
    $user1 = min($receiver_id, $sender_id);
    $user2 = max($receiver_id, $sender_id);

    // Prevent duplicates
    $stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE user1_id = ? AND user2_id = ?");
    $stmt->execute([$user1, $user2]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO chat_rooms (user1_id, user2_id) VALUES (?, ?)")->execute([$user1, $user2]);
    }

} elseif ($action === 'deny') {
    $pdo->prepare("UPDATE friend_requests SET status = 'denied' WHERE id = ?")->execute([$request_id]);
}

header("Location: chat.php");
