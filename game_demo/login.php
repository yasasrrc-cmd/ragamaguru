<!DOCTYPE html>
<html>
<head>
    <title>Login - Aviator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>
        <form id="loginForm">
            <input type="email" id="email" placeholder="Email" required>
            <input type="password" id="password" placeholder="Password" required>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
        <div id="message"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };
            
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            const msgDiv = document.getElementById('message');
            
            if (result.success) {
                if (result.user.is_admin) {
                    window.location.href = 'admin/index.php';
                } else {
                    window.location.href = 'game.php';
                }
            } else {
                msgDiv.innerHTML = '<p class="error">' + result.message + '</p>';
            }
        });
    </script>
</body>
</html>