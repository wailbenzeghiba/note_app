<?php
require 'includes/db.php';

$room_id = $_GET['room_id'] ?? null;

if (!$room_id) {
    die('Invalid chat room.');
}

$stmt = $pdo->prepare("SELECT * FROM games WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch();

if (!$game) {
    // If no game state exists, create a new one
    $stmt = $pdo->prepare("INSERT INTO games (room_id, board, current_player) VALUES (?, '         ', 'X')");
    $stmt->execute([$room_id]);
    $game = ['board' => '         ', 'current_player' => 'X', 'game_active' => true];
}

echo json_encode($game);
?>
