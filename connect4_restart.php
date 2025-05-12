<?php
require 'includes/db.php';

$room_id = $_POST['room_id'] ?? null;
if (!$room_id) { http_response_code(400); exit; }

// Delete the game for this room
$stmt = $pdo->prepare("DELETE FROM connect4_game WHERE room_id = ?");
$stmt->execute([$room_id]);

echo json_encode(['deleted' => true]);