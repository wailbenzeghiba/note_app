<?php
require 'includes/db.php';
require 'includes/auth.php';

$room_id = $_POST['room_id'] ?? null;
$col = $_POST['col'] ?? null;
$user_id = $_SESSION['user_id'];

if ($room_id === null || $col === null) { http_response_code(400); exit; }

$stmt = $pdo->prepare("SELECT * FROM connect4_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game || $game['game_status'] !== 'active') {
    echo json_encode($game); exit;
}

// Determine player symbol
$symbol = null;
if ($user_id == $game['player1_id'] && $game['current_player'] === 'R') $symbol = 'R';
if ($user_id == $game['player2_id'] && $game['current_player'] === 'Y') $symbol = 'Y';
if (!$symbol) { echo json_encode($game); exit; }

// Place piece in column
$board = str_split(str_pad($game['board'], 42, ' '));
$placed = false;
for ($row = 5; $row >= 0; $row--) {
    $idx = $row * 7 + $col;
    if ($board[$idx] === ' ') {
        $board[$idx] = $symbol;
        $placed = true;
        break;
    }
}
if (!$placed) { echo json_encode($game); exit; }

// Check for win/tie
function checkWin($b, $s) {
    // Horizontal, vertical, diagonal checks for 4 in a row
    for ($row = 0; $row < 6; $row++) {
        for ($col = 0; $col < 7; $col++) {
            // Horizontal
            if ($col <= 3 && $b[$row*7+$col]===$s && $b[$row*7+$col+1]===$s && $b[$row*7+$col+2]===$s && $b[$row*7+$col+3]===$s) return true;
            // Vertical
            if ($row <= 2 && $b[$row*7+$col]===$s && $b[($row+1)*7+$col]===$s && $b[($row+2)*7+$col]===$s && $b[($row+3)*7+$col]===$s) return true;
            // Diagonal /
            if ($row <= 2 && $col >= 3 && $b[$row*7+$col]===$s && $b[($row+1)*7+$col-1]===$s && $b[($row+2)*7+$col-2]===$s && $b[($row+3)*7+$col-3]===$s) return true;
            // Diagonal \
            if ($row <= 2 && $col <= 3 && $b[$row*7+$col]===$s && $b[($row+1)*7+$col+1]===$s && $b[($row+2)*7+$col+2]===$s && $b[($row+3)*7+$col+3]===$s) return true;
        }
    }
    return false;
}
$status = 'active';
if (checkWin($board, $symbol)) $status = 'won';
elseif (!in_array(' ', $board)) $status = 'tie';

// Switch player
$next_player = $game['current_player'] === 'R' ? 'Y' : 'R';

$pdo->prepare("UPDATE connect4_game SET board=?, current_player=?, game_status=? WHERE room_id=?")
    ->execute([implode('', $board), $next_player, $status, $room_id]);

// Return updated game
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($game);