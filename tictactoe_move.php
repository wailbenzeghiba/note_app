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
$board = str_split(str_pad($game['board'], 25, ' '));
if ($board[$cell] !== ' ') { echo json_encode($game); exit; }
$board[$cell] = $symbol;

// Check for win/tie (4 in a row, 5x5)
function checkWin($b, $s) {
    $size = 5;
    $in_row = 4;
    // Horizontal & Vertical
    for ($i = 0; $i < $size; $i++) {
        for ($j = 0; $j <= $size - $in_row; $j++) {
            // Horizontal
            $win = true;
            for ($k = 0; $k < $in_row; $k++) {
                if ($b[$i * $size + $j + $k] !== $s) $win = false;
            }
            if ($win) return true;
            // Vertical
            $win = true;
            for ($k = 0; $k < $in_row; $k++) {
                if ($b[($j + $k) * $size + $i] !== $s) $win = false;
            }
            if ($win) return true;
        }
    }
    // Diagonals
    for ($i = 0; $i <= $size - $in_row; $i++) {
        for ($j = 0; $j <= $size - $in_row; $j++) {
            // Main diagonal
            $win = true;
            for ($k = 0; $k < $in_row; $k++) {
                if ($b[($i + $k) * $size + ($j + $k)] !== $s) $win = false;
            }
            if ($win) return true;
            // Anti-diagonal
            $win = true;
            for ($k = 0; $k < $in_row; $k++) {
                if ($b[($i + $k) * $size + ($j + $in_row - 1 - $k)] !== $s) $win = false;
            }
            if ($win) return true;
        }
    }
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