<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: game.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Casino Games - Aviator & Chicken Run</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .games-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
            max-width: 800px;
        }
        .game-card {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s;
        }
        .game-card:hover {
            transform: translateY(-10px);
        }
        .game-card h2 {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .game-card h3 {
            margin-bottom: 15px;
        }
        .game-card p {
            font-size: 1rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <h1>🎰 Multi-Game Casino</h1>
        <p>Experience Multiple Exciting Games!</p>
        
        <div class="games-showcase">
            <div class="game-card">
                <h2>🛩️</h2>
                <h3>Aviator</h3>
                <p>Watch the multiplier soar! Cash out before the plane flies away.</p>
            </div>
            <div class="game-card">
                <h2>🐔</h2>
                <h3>Chicken Run</h3>
                <p>Find the bones, avoid the bombs! Multiplayer increases with each bone found.</p>
            </div>
        </div>
        
        <div class="buttons">
            <a href="login.php" class="btn">Login</a>
            <a href="register.php" class="btn btn-primary">Register Now</a>
        </div>
    </div>
</body>
</html>