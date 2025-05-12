<?php
require 'includes/db.php';
require 'includes/auth.php';

$room_id = $_POST['room_id'] ?? null;
$cell = $_POST['cell'] ?? null;
$user_id = $_SESSION['user_id'];

if ($room_id === null || $cell === null) { http_response_code(400); exit; }

$stmt = $pdo->prepare("SELECT * FROM tictactoe_game WHERE room_id = ?");
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game || $game['game_status'] !== 'active') {
    echo json_encode($game); exit;
}

// Determine player symbol
$symbol = null;
if ($user_id == $game['player1_id'] && $game['current_player'] === 'X') $symbol = 'X';
if ($user_id == $game['player2_id'] && $game['current_player'] === 'O') $symbol = 'O';
if (!$symbol) { echo json_encode($game); exit; }

// Make move if cell is empty
$board = str_split($game['board']);
if ($board[$cell] !== ' ') { echo json_encode($game); exit; }
$board[$cell] = $symbol;

// Check for win/tie
function checkWin($b, $s) {
    $w = [
        [0,1,2],[3,4,5],[6,7,8],
        [0,3,6],[1,4,7],[2,5,8],
        [0,4,8],[2,4,6]
    ];
    foreach ($w as $c) if ($b[$c[0]]===$s && $b[$c[1]]===$s && $b[$c[2]]===$s) return true;
    return false;
}
$status = 'active';
if (checkWin($board, $symbol)) $status = 'won';
elseif (!in_array(' ', $board)) $status = 'tie';

// Switch player
$next_player = $game['current_player'] === 'X' ? 'O' : 'X';

$pdo->prepare("UPDATE tictactoe_game SET board=?, current_player=?, game_status=? WHERE room_id=?")
    ->execute([implode('', $board), $next_player, $status, $room_id]);

// Return updated game
$stmt->execute([$room_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($game);