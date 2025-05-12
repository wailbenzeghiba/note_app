<?php
require 'includes/db.php';

$room_id = $_POST['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }

// Delete the previous game for this room
$stmt = $pdo->prepare("DELETE FROM wordle_game WHERE room_id = ?");
$stmt->execute([$room_id]);

// Pick a new random word
$words = file(__DIR__ . '/wordle_words.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$word = strtoupper(trim($words[array_rand($words)]));
$guesses = json_encode([]);
$results = json_encode([]);
$status = 'active';

// Insert new game
$stmt = $pdo->prepare("INSERT INTO wordle_game (room_id, word, guesses, results, status) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$room_id, $word, $guesses, $results, $status]);

echo json_encode(['success' => true]);