<?php
require 'includes/db.php';
require 'includes/auth.php';

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];

// Check if the users are already friends (status 'accepted' in the friend_requests table)
$stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE (sender_id = ? AND receiver_id = ? AND status = 'accepted') OR (sender_id = ? AND receiver_id = ? AND status = 'accepted')");
$stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
$friendship = $stmt->fetch();

if ($friendship) {
    // If they are already friends, send a response to show a message
    echo json_encode(['status' => 'error', 'message' => 'You are already friends with this user.']);
    exit;
}

// Check if a friend request is already pending (status 'pending' in friend_requests)
$stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE (sender_id = ? AND receiver_id = ? AND status = 'pending') OR (sender_id = ? AND receiver_id = ? AND status = 'pending')");
$stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
$pending_request = $stmt->fetch();

if ($pending_request) {
    // If there's already a pending request, send a response to show a message
    echo json_encode(['status' => 'error', 'message' => 'A friend request is already pending with this user.']);
    exit;
}

// If neither condition is true, send the friend request
$stmt = $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmt->execute([$sender_id, $receiver_id]);

// Send a success response
echo json_encode(['status' => 'success', 'message' => 'Friend request sent successfully!']);
exit;
