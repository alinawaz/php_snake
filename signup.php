<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Signup - Bank App</title>
  <link rel="stylesheet" href="assets/style.css">
  <script>
    async function handleSignup(e) {
      e.preventDefault();
      const username = document.querySelector('[name="username"]').value;
      const password = document.querySelector('[name="password"]').value;
      const errorMsg = document.getElementById('error-msg');
      const successMsg = document.getElementById('success-msg');
      errorMsg.textContent = '';
      successMsg.textContent = '';

      const res = await fetch('controllers/auth.php?action=signup', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });
      const data = await res.json();
      if (data.success) {
        successMsg.innerHTML = `${data.message} <a href='index.php'>Login here</a>`;
      } else {
        errorMsg.textContent = data.error || 'Signup failed';
      }
    }
  </script>
</head>
<body class="login-body">
  <div class="login-container">
    <h2 class="login-title">🏦 Create Account</h2>
    <p class="login-subtitle">Join our bank system in a few seconds</p>
    
    <form class="login-form" onsubmit="handleSignup(event)">
      <input type="text" name="username" placeholder="👤 Choose Username" required>
      <input type="password" name="password" placeholder="🔒 Choose Password" required>
      <button type="submit">Sign Up</button>
    </form>
    
    <p class="signup-link">Already have an account? <a href="index.php">Login</a></p>

    <p class="error-msg" id="error-msg"></p>
    <p class="success-msg" id="success-msg"></p>
  </div>
</body>
</html>
