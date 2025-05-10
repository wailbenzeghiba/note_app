<?php
require '../includes/db.php';
require '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emotion = $_POST['emotion'] ?: '📝';

    // Handle image upload
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Define upload directory
        $uploadDir = '../uploads/';
        
        // Get file details
        $fileName = $_FILES['photo']['name'];
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Generate a unique name for the file to avoid collisions
        $newFileName = uniqid('note_', true) . '.' . $fileExtension;

        // Move the file to the uploads directory
        $destinationPath = $uploadDir . $newFileName;
        move_uploaded_file($fileTmpPath, $destinationPath);

        // Store the new file name in the $photo variable
        $photo = $newFileName;
    }

    // Insert the note into the database
    $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, emotion, photo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['title'], $_POST['content'], $emotion, $photo]);

    // Redirect after saving the note
    header("Location: ../dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Note</title>
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

<h2>📝 Add Note</h2>

<form method="POST" enctype="multipart/form-data">
    <div class="emoji-trigger" id="emoji-trigger">📝</div>
    <div class="emoji-options" id="emoji-options">
        <span>📝</span>
        <span>😀</span>
        <span>😢</span>
        <span>😡</span>
        <span>🤔</span>
        <span>❤️</span>
    </div>
    <input type="hidden" name="emotion" id="emotion" value="📝">

    <input name="title" required placeholder="Title">
    <textarea name="content" placeholder="Write your note here..."></textarea>

    <!-- Image upload input -->
    <input type="file" name="photo" accept="image/*">

    <button type="submit">Save</button>
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
