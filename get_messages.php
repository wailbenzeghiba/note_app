<?php
require 'includes/db.php';
require 'includes/auth.php';

$room_id = $_GET['room_id'] ?? null;

$stmt = $pdo->prepare("SELECT sender_id, message, created_at FROM messages WHERE chat_room_id = ? ORDER BY created_at ASC");
$stmt->execute([$room_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);
