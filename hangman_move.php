<?php
require 'includes/db.php';
$room_id = $_POST['room_id'] ?? null;
$letter = strtoupper($_POST['letter'] ?? '');
if (!$room_id || !$letter) { http_response_code(400); exit; }
$stmt = $pdo->prepare("SELECT * FROM hangman_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game || $game['status'] !== 'active') { echo json_encode(['error'=>'No active game']); exit; }
$guessed = json_decode($game['guessed'], true);
if (!in_array($letter, $guessed)) $guessed[] = $letter;
$word = $game['word'];
$tries_left = $game['tries_left'];
if (strpos($word, $letter) === false) $tries_left--;
$status = 'active';
$allGuessed = true;
for ($i=0; $i<strlen($word); $i++) {
    if (!in_array($word[$i], $guessed)) $allGuessed = false;
}
if ($allGuessed) $status = 'won';
elseif ($tries_left <= 0) $status = 'lost';
$stmt = $pdo->prepare("UPDATE hangman_game SET guessed=?, tries_left=?, status=? WHERE room_id=?");
$stmt->execute([json_encode($guessed), $tries_left, $status, $room_id]);
echo json_encode([
    'word' => $word,
    'guessed' => $guessed,
    'tries_left' => $tries_left,
    'status' => $status
]);