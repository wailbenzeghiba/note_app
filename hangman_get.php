<?php
require 'includes/db.php';
$room_id = $_GET['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }
$stmt = $pdo->prepare("SELECT * FROM hangman_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    // Start new game if not exists
    require 'hangman_restart.php';
    $stmt->execute([$room_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
}
echo json_encode([
    'word' => $game['word'],
    'guessed' => json_decode($game['guessed']),
    'tries_left' => $game['tries_left'],
    'status' => $game['status']
]);