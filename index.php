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
    $error = 'Usuário ou senha inválidos.';
  }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <title>Login</title>
</head>

<body>
  <h2>Login</h2>

  <?php if (isset($error)): ?>
    <p style="color: red;">
      <?= htmlspecialchars($error) ?>
    </p>
  <?php endif; ?>

  <form method="POST">
    <label for="username">Usuário:</label><br />
    <input type="text" name="username" required /><br /><br />

    <label for="password">Senha:</label><br />
    <input type="password" name="password" required /><br /><br />

    <input type="submit" value="Entrar" />
  </form>
</body>

</html>