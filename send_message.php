<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_room_id = $_POST['room_id']; // Update to use the correct variable name
    $message = $_POST['message'];
    $user_id = $_SESSION['user_id'];

    // Make sure the room exists and the user is allowed to send a message in this room
    $stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$chat_room_id, $user_id, $user_id]);
    $chat_room = $stmt->fetch();

    if (!$chat_room) {
        die('Access denied.');
    }

    // Insert the message into the database, updating the column name to 'chat_room_id'
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (chat_room_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$chat_room_id, $user_id, $message]);

        echo 'Message sent successfully';
    } catch (Exception $e) {
        // If there is an error, output it
        echo 'Error: ' . $e->getMessage();
    }
} else {
    die('Invalid request method');
}
?>
