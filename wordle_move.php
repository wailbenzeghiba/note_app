<?php
require 'includes/db.php';

$room_id = $_POST['room_id'] ?? null;
$guess = strtoupper($_POST['guess'] ?? '');
if (!$room_id || strlen($guess) !== 5) { http_response_code(400); exit; }

$stmt = $pdo->prepare("SELECT * FROM wordle_game WHERE room_id=?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game || $game['status'] !== 'active') { http_response_code(400); exit; }

$word = strtoupper($game['word']);
$guesses = json_decode($game['guesses'], true);
$results = json_decode($game['results'], true);

if (count($guesses) >= 6) { http_response_code(400); exit; }

$res = [];
$wordArr = str_split($word);
$guessArr = str_split($guess);
$used = array_fill(0, 5, false);

// First pass: correct position
for ($i = 0; $i < 5; $i++) {
    if ($guessArr[$i] === $wordArr[$i]) {
        $res[$i] = 'G';
        $used[$i] = true;
    } else {
        $res[$i] = '';
    }
}
// Second pass: correct letter, wrong position
for ($i = 0; $i < 5; $i++) {
    if ($res[$i] === '') {
        $found = false;
        for ($j = 0; $j < 5; $j++) {
            if (!$used[$j] && $guessArr[$i] === $wordArr[$j]) {
                $found = true;
                $used[$j] = true;
                break;
            }
        }
        $res[$i] = $found ? 'Y' : 'B';
    }
}

$guesses[] = $guess;
$results[] = implode('', $res);

$status = 'active';
if ($guess === $word) $status = 'won';
elseif (count($guesses) >= 6) $status = 'lost';

$stmt = $pdo->prepare("UPDATE wordle_game SET guesses=?, results=?, status=? WHERE room_id=?");
$stmt->execute([json_encode($guesses), json_encode($results), $status, $room_id]);

echo json_encode([
    'word' => $word,
    'guesses' => $guesses,
    'results' => $results,
    'status' => $status
]);