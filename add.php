<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $completed = isset($_POST['completed']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Título é obrigatório';
    } elseif (strlen($title) > 255) {
        $error = 'Título muito longo (máx 255 caracteres)';
    } else {
        $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, completed) 
                               VALUES (:userId, :title, :completed)');

        try {
            $stmt->execute([
                'userId' => $_SESSION['user_id'],
                'title' => $title,
                'completed' => $completed
            ]);

            header('Location: dashboard.php');
            exit;
        } catch (\Throwable $th) {
            error_log($th->getMessage());
            $error = 'Erro ao criar tarefa. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Nova Tarefa</title>
</head>

<body>
    <h2>Nova Tarefa</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label>Título:</label><br>
            <input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div><br>

        <div>
            <label>
                <input type="checkbox" name="completed" value="1">
                Marcar como concluída
            </label>
        </div><br>

        <button type="submit">Salvar</button>
        <a href="dashboard.php">Cancelar</a>
    </form>
</body>

</html>