<?php
require '../includes/db.php';
require '../includes/auth.php';

// Get the note ID from the URL
if (!isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit;
}

$note_id = $_GET['id'];

// Fetch the existing note details
$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$note_id, $_SESSION['user_id']]);
$note = $stmt->fetch();

if (!$note) {
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emotion = $_POST['emotion'] ?: '📝';

    // Update the note with the new details
    $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, emotion = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['title'], $_POST['content'], $emotion, $note_id, $_SESSION['user_id']]);

    header("Location: ../dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Note</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #fdf6c9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        h2 {
            font-size: 2rem;
            color: #3e3e00;
            padding: 1rem;
            margin: 0;
        }

        form {
            position: relative;
            width: 100%;
            height: calc(100vh - 60px);
            background: #fffdf3;
            border-left: 5px solid #e6b800;
            padding: 1rem 2rem;
            box-sizing: border-box;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent,
                transparent 28px,
                #f2e59e 29px
            );
        }

        .emoji-trigger {
            position: absolute;
            top: 1.2rem;
            right: 2rem;
            font-size: 1.5rem;
            cursor: pointer;
            user-select: none;
        }

        .emoji-options {
            position: absolute;
            top: 3.2rem;
            right: 2rem;
            background: #fffef3;
            padding: 0.3rem 0.4rem;
            display: none;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .emoji-options span {
            font-size: 1.4rem;
            margin: 0 0.3rem;
            cursor: pointer;
        }

        input[name="title"],
        textarea {
            width: 100%;
            font-size: 1.1rem;
            padding: 0.6rem;
            border: 1px solid #ddd;
            margin-bottom: 1rem;
            background-color: #fffef3;
            font-family: 'Courier New', monospace;
        }

        textarea {
            height: 60vh;
            resize: vertical;
        }

        button {
            background-color: #ffec88;
            border: 2px solid #e0c878;
            color: #5a3b00;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1rem;
            box-shadow: 2px 2px 3px rgba(0,0,0,0.1);
        }

        button:hover {
            background-color: #fff4a3;
        }
    </style>
</head>
<body>

<h2>📝 Edit Note</h2>

<form method="POST">
    <div class="emoji-trigger" id="emoji-trigger"><?= htmlspecialchars($note['emotion']) ?></div>
    <div class="emoji-options" id="emoji-options">
        <span>📝</span>
        <span>😀</span>
        <span>😢</span>
        <span>😡</span>
        <span>🤔</span>
        <span>❤️</span>
    </div>
    <input type="hidden" name="emotion" id="emotion" value="<?= htmlspecialchars($note['emotion']) ?>">

    <input name="title" required placeholder="Title" value="<?= htmlspecialchars($note['title']) ?>">
    <textarea name="content" placeholder="Write your note here..."><?= htmlspecialchars($note['content']) ?></textarea>

    <button type="submit">Update</button>
</form>

<script>
    const trigger = document.getElementById('emoji-trigger');
    const options = document.getElementById('emoji-options');
    const emotionInput = document.getElementById('emotion');

    trigger.addEventListener('click', () => {
        options.style.display = options.style.display === 'block' ? 'none' : 'block';
    });

    options.querySelectorAll('span').forEach(span => {
        span.addEventListener('click', () => {
            emotionInput.value = span.textContent;
            trigger.textContent = span.textContent;
            options.style.display = 'none';
        });
    });

    document.addEventListener('click', (e) => {
        if (!trigger.contains(e.target) && !options.contains(e.target)) {
            options.style.display = 'none';
        }
    });
</script>

</body>
</html>
