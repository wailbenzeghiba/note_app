<?php
require 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Courier New', monospace;
            background-color: #fdf6c9;
            color: #333;
            position: relative;
        }

        h2 {
            font-size: 2rem;
            color: #3e3e00;
            padding: 1rem;
            margin: 0;
            z-index: 2;
            position: relative;
        }

        form {
            width: 100%;
            height: 100vh;
            background: #fffdf3;
            border-left: 5px solid #e6b800;
            padding: 2rem;
            box-sizing: border-box;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent,
                transparent 28px,
                #f2e59e 29px
            );
            position: absolute;
            z-index: 1;
            overflow: hidden;
        }

        input, button {
            z-index: 999;
            width: 100%;
            font-size: 1.1rem;
            padding: 0.8rem;
            margin-bottom: 1.2rem;
            border-radius: 8px;
        }

        input {
            border: 1px solid #ddd;
            background-color: #fffef3;
            transition: border 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus {
            outline: none;
            border: 1px solid #e0c878;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button {
            background-color: #ffec88;
            border: 2px solid #e0c878;
            color: #5a3b00;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background-color: #fff4a3;
            transform: translateY(-2px);
        }

        button:active {
            background-color: #ffec66;
            transform: translateY(2px);
        }

        p {
            margin-top: 1rem;
        }

        .error {
            color: red;
        }

        #canvas {
            position: absolute;
            top: 60vh;
            left: 0;
            z-index: 99;
            width: 100vw;
            height: calc(100vh - 120px);
            pointer-events: auto;
        }

        form p a {
            color: #5a3b00;
            text-decoration: underline;
            font-weight: bold;
            z-index: 999999;
        }

        form p a:hover {
            color: #3e3e00;
        }
    </style>
</head>
<body>

<canvas id="canvas"></canvas>

<h2>🔐 Login</h2>

<form method="POST">
    <input name="username" required placeholder="Username">
    <input name="password" type="password" required placeholder="Password">
    <button type="submit" id="loginBtn">Login</button>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <p>Don't have an account? <a href="register.php">Sign up here</a>.</p>
</form>

<script>
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight - 120;
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    ctx.lineWidth = 2;
    ctx.strokeStyle = '#3e3e00';
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.globalAlpha = 0.5;

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    function getMousePos(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    canvas.addEventListener('mousedown', (e) => {
        const { x, y } = getMousePos(e);
        isDrawing = true;
        lastX = x;
        lastY = y;
    });

    canvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        const { x, y } = getMousePos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
        lastX = x;
        lastY = y;
    });

    canvas.addEventListener('mouseup', () => {
        isDrawing = false;
    });

    canvas.addEventListener('mouseout', () => {
        isDrawing = false;
    });

    window.addEventListener('load', () => {
        canvas.style.pointerEvents = 'auto';
    });
</script>

</body>
</html>
