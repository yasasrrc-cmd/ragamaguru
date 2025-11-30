<!DOCTYPE html>
<html>
<head>
    <title>Register - Aviator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Create Account</h2>
        <form id="registerForm">
            <input type="text" id="username" placeholder="Username" required>
            <input type="email" id="email" placeholder="Email" required>
            <input type="password" id="password" placeholder="Password" required>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
        <div id="message"></div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };
            
            const response = await fetch('api/register.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            const msgDiv = document.getElementById('message');
            
            if (result.success) {
                msgDiv.innerHTML = '<p class="success">' + result.message + '</p>';
                setTimeout(() => window.location.href = 'login.php', 2000);
            } else {
                msgDiv.innerHTML = '<p class="error">' + result.message + '</p>';
            }
        });
    </script>
</body>
</html>