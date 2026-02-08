<?php
session_start();
require 'config/database.php';

// Se já tá logado, redireciona
if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}

// Processa o login quando formulário for submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  // TODO: Validar no banco
  $stmt = $pdo->prepare('SELECT id, password, username FROM users WHERE username = :username');
  $stmt->execute(['username' => $username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    header('Location: dashboard.php');
    exit;
  } else {
    $error = 'Invalid username or password.';
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Task Manager</title>
  <link rel="stylesheet" href="public/css/global.css" />
  <link rel="stylesheet" href="public/css/index.css" />
</head>

<body>
  <div class="login-container">
    <h2>Login</h2>

    <?php if (isset($error)): ?>
      <div class="error-message">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <label for="username">Username:</label>
      <input type="text" name="username" id="username" required />

      <label for="password">Password:</label>
      <input type="password" name="password" id="password" required />

      <input type="submit" value="Login" />
    </form>

    <a href="register.php" class="btn-register">Register New User</a>

    <p class="register-text">Don't have an account? Register above!</p>
  </div>
</body>

</html>