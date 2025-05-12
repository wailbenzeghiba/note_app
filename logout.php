<?php
session_start();
require 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'offline' WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

session_destroy();
header("Location: login.php");
exit;
?>
