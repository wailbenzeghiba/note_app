<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, status FROM users WHERE id != ?");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
