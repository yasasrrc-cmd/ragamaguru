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
    <title>Aviator Casino Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="landing-page">
        <h1>🛩️ Aviator Casino</h1>
        <p>Fly High, Win Big!</p>
        <div class="buttons">
            <a href="login.php" class="btn">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>
    </div>
</body>
</html>