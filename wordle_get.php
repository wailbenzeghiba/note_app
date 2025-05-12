<?php
require 'includes/db.php';

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }

$stmt = $pdo->prepare("SELECT * FROM wordle_game WHERE room_id=?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    // Start new game
    $words = file(__DIR__ . '/wordle_words.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $word = strtoupper(trim($words[array_rand($words)]));
    $guesses = json_encode([]);
    $results = json_encode([]);
    $status = 'active';
    $stmt = $pdo->prepare("INSERT INTO wordle_game (room_id, word, guesses, results, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$room_id, $word, $guesses, $results, $status]);
    $game = [
        'room_id' => $room_id,
        'word' => $word,
        'guesses' => [],
        'results' => [],
        'status' => $status
    ];
} else {
    $game['guesses'] = json_decode($game['guesses'], true);
    $game['results'] = json_decode($game['results'], true);
}

echo json_encode($game);