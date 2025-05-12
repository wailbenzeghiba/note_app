<?php
require 'includes/db.php';
require 'includes/auth.php';

$room_id = $_POST['room_id'] ?? null;
$cell = isset($_POST['cell']) ? intval($_POST['cell']) : null;
$user_id = $_SESSION['user_id'];

if (!$room_id || $cell === null) {
    http_response_code(400);
    exit('Missing data');
}

// Fetch game
$stmt = $pdo->prepare("SELECT * FROM memory_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game || $game['game_status'] !== 'active' || $game['current_player'] != $user_id) {
    http_response_code(403);
    exit('Not your turn or game not active');
}

$board = str_split($game['board']);
$revealed = str_split($game['revealed']);
$scores = explode(',', $game['scores']);
$player1_id = $game['player1_id'];
$player2_id = $game['player2_id'];
$current_player = $game['current_player'];

// Find already revealed but unmatched cards this turn
$revealed_this_turn = [];
for ($i = 0; $i < count($revealed); $i++) {
    if ($revealed[$i] === '2') $revealed_this_turn[] = $i;
}

// If two cards are already revealed this turn, flip them back
if (count($revealed_this_turn) == 2) {
    foreach ($revealed_this_turn as $i) $revealed[$i] = '0';
    $pdo->prepare("UPDATE memory_game SET revealed = ? WHERE room_id = ?")
        ->execute([implode('', $revealed), $room_id]);
    // Switch turn
    $next_player = ($current_player == $player1_id) ? $player2_id : $player1_id;
    $pdo->prepare("UPDATE memory_game SET current_player = ? WHERE room_id = ?")
        ->execute([$next_player, $room_id]);
    // Reload game state
    $stmt->execute([$room_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    $board = str_split($game['board']);
    $revealed = str_split($game['revealed']);
    $scores = explode(',', $game['scores']);
    $current_player = $game['current_player'];
}

// Flip the selected card if not already revealed
if ($revealed[$cell] === '0') {
    $revealed[$cell] = '2'; // 2 = revealed this turn
    // Check if this is the second card revealed this turn
    $revealed_this_turn = [];
    for ($i = 0; $i < count($revealed); $i++) {
        if ($revealed[$i] === '2') $revealed_this_turn[] = $i;
    }
    if (count($revealed_this_turn) == 2) {
        // Check for match
        $a = $revealed_this_turn[0];
        $b = $revealed_this_turn[1];
        if ($board[$a] === $board[$b]) {
            // It's a match! Keep them revealed (set to 1)
            $revealed[$a] = $revealed[$b] = '1';
            // Update score
            if ($current_player == $player1_id) $scores[0]++;
            else $scores[1]++;
            // Player gets another turn (don't switch)
        }
        // Otherwise, cards will be flipped back on next move and turn will switch
    }
    $pdo->prepare("UPDATE memory_game SET revealed = ?, scores = ? WHERE room_id = ?")
        ->execute([implode('', $revealed), implode(',', $scores), $room_id]);
}

// Check for win
if (implode('', $revealed) === str_repeat('1', count($revealed))) {
    $status = ($scores[0] > $scores[1]) ? 'won' : ($scores[0] < $scores[1] ? 'won' : 'tie');
    $pdo->prepare("UPDATE memory_game SET game_status = ? WHERE room_id = ?")
        ->execute([$status, $room_id]);
    $game['game_status'] = $status;
}

// Return updated game state
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($game);