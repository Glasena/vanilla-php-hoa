<?php

session_start();
require 'config/database.php';

// Se já tá logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);

        if ($stmt->fetch()) {
            $error = 'Username already taken.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
                $stmt->execute([
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                ]);
                $id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                header('Location: dashboard.php');
            } catch (\Throwable $th) {
                error_log('Registration error: ' . $th->getMessage());
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Task Manager</title>
    <link rel="stylesheet" href="public/css/global.css" />
    <link rel="stylesheet" href="public/css/index.css" />
</head>

<body>
    <div class="login-container">
        <h2>Register</h2>

        <?php if ($error): ?>
            <div class="message error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required />

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required />

            <input type="submit" value="Register" />
        </form>

        <a href="index.php" class="btn-register">Back to Login</a>
    </div>
</body>

</html>