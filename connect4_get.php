<?php
require 'includes/db.php';

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }

$stmt = $pdo->prepare("SELECT * FROM connect4_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    // Create new game if not exists
    $chat = $pdo->prepare("SELECT user1_id, user2_id FROM chat_rooms WHERE id = ?");
    $chat->execute([$room_id]);
    $chat_room = $chat->fetch(PDO::FETCH_ASSOC);
    if (!$chat_room) { http_response_code(404); exit; }
    $pdo->prepare("INSERT INTO connect4_game (room_id, player1_id, player2_id) VALUES (?, ?, ?)")
        ->execute([$room_id, $chat_room['user1_id'], $chat_room['user2_id']]);
    $stmt->execute([$room_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($game);