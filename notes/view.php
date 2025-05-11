<?php
require '../includes/db.php';  // Adjusted file path
require '../includes/auth.php';  // Adjusted file path

$user_id = $_SESSION['user_id'];
$note_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$note_id, $user_id]);
$note = $stmt->fetch();

if (!$note) {
    die("Note not found or you don't have permission to view it.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Note</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #fdf6c9;
            margin: 0;
            padding: 0;
            color: #333;
            height: 100vh; /* Full viewport height */
            display: flex;
            justify-content: center;
            align-items: flex-start;
            text-align: left;
        }

        .note-container {
            background: #fffdf3;
            border-left: 5px solid #e6b800;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 1200px;
            height: 100%; /* Full height of the page */
            overflow-y: auto;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent,
                transparent 28px,
                #f2e59e 29px
            );
            padding-top: 3rem; /* Space for the title at the top */
        }

        .note-title {
            font-weight: bold;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4b3600;
            text-align: left; /* Title aligned to the left */
        }

        .note-content {
            font-size: 1.5rem;
            line-height: 1.8;
            white-space: pre-wrap;  /* Preserves whitespace formatting */
            overflow-wrap: break-word;
            max-width: 100%;
            word-wrap: break-word;
            margin-top: 2rem;
        }

        .note-image {
            max-width: 100%;
            max-height: 400px;
            margin-top: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="note-container">
    <div class="note-title">
        <?= htmlspecialchars($note['emotion'] ?? '📝') ?> <?= htmlspecialchars($note['title']) ?>
    </div>
    <div class="note-content">
        <?= nl2br(htmlspecialchars($note['content'])) ?>
    </div>

    <!-- Display note image if available -->
    <?php if (!empty($note['photo'])): ?>
        <div>
            <img class="note-image" src="../uploads/<?= htmlspecialchars($note['photo']) ?>" alt="Note Image">
        </div>
    <?php endif; ?>
</div>

</body>
</html>
