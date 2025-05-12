<?php
require 'includes/db.php';

$room_id = $_POST['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }

// 10 pairs for 20 tiles, randomized
$pairs = array_merge(range('A', 'J'), range('A', 'J'));
shuffle($pairs);
$board = implode('', $pairs);
$revealed = str_repeat('0', 20);
$scores = "0,0";

// Update or insert the new game state
$stmt = $pdo->prepare("UPDATE memory_game SET board=?, revealed=?, scores=?, game_status='active', current_player=player1_id WHERE room_id=?");
$stmt->execute([$board, $revealed, $scores, $room_id]);

echo json_encode(['success' => true]);