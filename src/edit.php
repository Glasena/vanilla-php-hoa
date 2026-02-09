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
        $stmt = $pdo->prepare('
            UPDATE tasks SET title = :title, completed = :completed 
            WHERE id = :taskId AND user_id = :userId');
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
            $error = 'Erro ao editar tarefa. Tente novamente.';
        }
    }
} else {
    $stmt = $pdo->prepare('SELECT id, title, completed, created_at FROM tasks WHERE user_id = :userId and title = :title');
    $stmt->execute(['userId' => $userId, 'title' => $title]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
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
            <input type="text" name="title" required value="<?= htmlspecialchars($task['title'] ?? '') ?>">
        </div><br>

        <div>
            <label>
                <input type="checkbox" name="completed" value=<?php htmlspecialchars($task['completed'] ?? '0') ?>>
                Marcar como concluída
            </label>
        </div><br>

        <button type="submit">Salvar</button>
        <a href="dashboard.php">Cancelar</a>
    </form>
</body>

</html>