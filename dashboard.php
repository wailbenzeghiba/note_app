<?php
require 'includes/db.php';
require 'includes/auth.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Notes</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #fdf6c9;
            margin: 0;
            padding: 2rem;
            color: #333;
            position: relative;
            min-height: 100vh;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #ccc;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        h2 {
            margin: 0;
            font-size: 2rem;
            color: #3e3e00;
        }

        a {
            color: #7b3f00;
            text-decoration: none;
            font-weight: bold;
            margin-right: 1rem;
        }

        a:hover {
            text-decoration: underline;
        }

        .add-note {
            background-color: #fff4a3;
            border: 2px solid #e0c878;
            color: #5a3b00;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 2px 2px 3px rgba(0,0,0,0.1);
        }

        .add-note:hover {
            background-color: #ffec88;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background: #fffdf3;
            border-left: 5px solid #e6b800;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            line-height: 1.6;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent,
                transparent 28px,
                #f2e59e 29px
            );
        }

        .note-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
            color: #4b3600;
        }

        .note-actions {
            margin-top: 0.7rem;
            font-size: 0.9rem;
        }

        .note-actions a {
            margin-right: 1rem;
        }

        /* Image preview styles */
        .note-image {
            max-width: 100px;
            max-height: 100px;
            margin-top: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        /* Cat styles */
        #cat-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            pointer-events: none;
            overflow: hidden;
            z-index: 9999;
        }

        #moving-cat {
            position: absolute;
            bottom: 0;
            width: 100px;
            cursor: pointer;
            z-index: 10000;
            pointer-events: auto;
        }

        .walk-right {
            animation: walkRight 8s linear forwards;
            transform: scaleX(1);
        }

        .walk-left {
            animation: walkLeft 8s linear forwards;
            transform: scaleX(-1);
        }

        @keyframes walkRight {
            from { left: 0; }
            to { left: calc(100% - 100px); }
        }

        @keyframes walkLeft {
            from { left: calc(100% - 100px); }
            to { left: 0; }
        }

        .heart {
            position: absolute;
            font-size: 1rem;
            color: red;
            animation: heartAnimation 2s ease-out forwards;
        }

        @keyframes heartAnimation {
            from {
                transform: translate(0, 0);
                opacity: 1;
            }
            to {
                transform: var(--heart-translate);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>📒 Your Notes</h2>
    <div>
        <a class="add-note" href="notes/add.php">+ Add Note</a>
        <a href="logout.php">Logout</a>
    </div>
</header>

<ul>
    <?php foreach ($notes as $note): ?>
        <li>
            <div class="note-title">
                <?= htmlspecialchars($note['emotion'] ?? '📝') ?> <?= htmlspecialchars($note['title']) ?>
            </div>
            <div><?= nl2br(htmlspecialchars($note['content'])) ?></div>

            <!-- Display note image if available -->
            <?php if (!empty($note['photo'])): ?>
                <div>
                    <img class="note-image" src="../uploads/<?= htmlspecialchars($note['photo']) ?>" alt="Note Image">
                </div>
            <?php endif; ?>

            <div class="note-actions">
                <a href="notes/edit.php?id=<?= $note['id'] ?>">✏️ Edit</a>
                <a href="notes/delete.php?id=<?= $note['id'] ?>" onclick="return confirm('Delete this note?')">🗑️ Delete</a>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Walking cat at the bottom of the full page -->
<div id="cat-container">
    <img src="images/cat.gif" id="moving-cat" alt="Walking Cat">
</div>

<!-- Meow sound -->
<audio id="meow-sound" src="sounds/meow.mp3" preload="auto"></audio>

<script>
    const cat = document.getElementById('moving-cat');
    const meowSound = document.getElementById('meow-sound');
    let goingRight = true;

    function walk() {
        if (goingRight) {
            cat.classList.remove('walk-left');
            cat.classList.add('walk-right');
        } else {
            cat.classList.remove('walk-right');
            cat.classList.add('walk-left');
        }
    }

    cat.addEventListener('animationend', () => {
        goingRight = !goingRight;
        walk();
    });

    walk();

    cat.addEventListener('click', () => {
        meowSound.play();
        createHearts();
    });

    function createHearts() {
        const numberOfHearts = Math.floor(Math.random() * 5) + 1;
        const catPosition = cat.getBoundingClientRect();

        for (let i = 0; i < numberOfHearts; i++) {
            const heart = document.createElement('div');
            heart.classList.add('heart');
            heart.textContent = '❤️';

            const heartLeft = catPosition.left + (catPosition.width / 2) + (Math.random() * 40 - 20);
            const heartBottom = window.innerHeight - catPosition.bottom + 10;

            heart.style.left = `${heartLeft}px`;
            heart.style.bottom = `${heartBottom}px`;

            const xForce = goingRight ? -30 - Math.random() * 30 : 30 + Math.random() * 30;
            const yForce = -100 - Math.random() * 50;
            heart.style.setProperty('--heart-translate', `translate(${xForce}px, ${yForce}px)`);

            document.body.appendChild(heart);

            setTimeout(() => {
                heart.remove();
            }, 2000);
        }
    }
</script>

</body>
</html>
