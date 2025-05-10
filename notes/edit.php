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
    $photo = $note['photo']; // Keep the existing photo by default

    // Handle image upload if it's provided
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        // Define the target directory for images
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is an image
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if ($check !== false) {
            // Delete the old photo if a new one is uploaded
            if ($note['photo']) {
                $old_photo_path = $target_dir . $note['photo'];
                if (file_exists($old_photo_path)) {
                    unlink($old_photo_path);
                }
            }

            // Move the file to the uploads folder and update the photo field
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $photo = basename($_FILES["photo"]["name"]);
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "File is not an image.";
        }
    }

    // Update the note with the new details (including new photo if uploaded)
    $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, emotion = ?, photo = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['title'], $_POST['content'], $emotion, $photo, $note_id, $_SESSION['user_id']]);

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
        textarea,
        input[name="photo"] {
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

        /* Image preview style */
        .image-preview {
            margin-bottom: 1rem;
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

<h2>📝 Edit Note</h2>

<form method="POST" enctype="multipart/form-data">
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

    <!-- Display existing photo if available -->
    <?php if ($note['photo']): ?>
        <div class="image-preview">
            <img src="../uploads/<?= htmlspecialchars($note['photo']) ?>" alt="Current Image">
        </div>
    <?php endif; ?>

    <!-- Add input for the new photo attachment -->
    <input type="file" name="photo" accept="image/*" placeholder="Attach a new photo (optional)">

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
