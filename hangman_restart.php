<?php
require 'includes/db.php';
$room_id = $_POST['room_id'] ?? $_GET['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }
$pdo->prepare("DELETE FROM hangman_game WHERE room_id = ?")->execute([$room_id]);
$words = file(__DIR__ . '/hangman_words.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$word = strtoupper(trim($words[array_rand($words)]));
$guessed = json_encode([]);
$tries_left = 6;
$status = 'active';
$stmt = $pdo->prepare("INSERT INTO hangman_game (room_id, word, guessed, tries_left, status) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$room_id, $word, $guessed, $tries_left, $status]);
echo json_encode(['success' => true]);