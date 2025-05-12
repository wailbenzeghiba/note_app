<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $allowed = ['online', 'offline', 'dnd'];
    $status = in_array($_POST['status'], $allowed) ? $_POST['status'] : 'offline';

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $_SESSION['user_id']]);
}

header("Location: chat.php");
exit;
?>
