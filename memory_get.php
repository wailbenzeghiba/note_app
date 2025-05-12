<?php
require 'includes/db.php';
require 'includes/auth.php';

$room_id = $_GET['room_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$room_id) {
    http_response_code(400);
    exit('Missing room_id');
}

// Try to fetch existing game
$stmt = $pdo->prepare("SELECT * FROM memory_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    // Create new game
    $pairs = array_merge(range('A', 'H'), range('A', 'H')); // 16 pairs, 4x4 grid
    shuffle($pairs);
    $board = implode('', $pairs);
    $revealed = str_repeat('0', 16);
    // Get players from chat_rooms
    $stmt2 = $pdo->prepare("SELECT user1_id, user2_id FROM chat_rooms WHERE id = ?");
    $stmt2->execute([$room_id]);
    $room = $stmt2->fetch(PDO::FETCH_ASSOC);
    $player1 = $room['user1_id'];
    $player2 = $room['user2_id'];
    $scores = "0,0";
    $current_player = $player1;
    $pdo->prepare("INSERT INTO memory_game (room_id, board, revealed, player1_id, player2_id, current_player, scores, game_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')")
        ->execute([$room_id, $board, $revealed, $player1, $player2, $current_player, $scores]);
    $game = [
        'room_id' => $room_id,
        'board' => $board,
        'revealed' => $revealed,
        'player1_id' => $player1,
        'player2_id' => $player2,
        'current_player' => $current_player,
        'scores' => $scores,
        'game_status' => 'active'
    ];
}

header('Content-Type: application/json');
echo json_encode($game);