<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bank Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <script>
        async function handleLogin(e) {
            e.preventDefault();
            const username = document.querySelector('[name="username"]').value;
            const password = document.querySelector('[name="password"]').value;
            const errorMsg = document.getElementById('error-msg');
            errorMsg.textContent = '';

            const res = await fetch('/auth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username,
                    password
                })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                errorMsg.textContent = data.error || 'Login failed';
            }
        }
    </script>
</head>

<body class="login-body">
    <div class="login-container">
        <h2 class="login-title">🏦 Bank Portal</h2>
        <p class="login-subtitle">Please sign in to continue</p>
        <form class="login-form" onsubmit="handleLogin(event)">
            <input type="text" name="username" placeholder="👤 Username" required>
            <input type="password" name="password" placeholder="🔒 Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="signup-link">Don’t have an account? <a href="signup.php">Sign up</a></p>
        <p class="error-msg" id="error-msg"></p>
    </div>
</body>

</html>