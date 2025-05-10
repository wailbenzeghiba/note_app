<?php
require '../includes/db.php';
require '../includes/auth.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
header("Location: ../dashboard.php");
