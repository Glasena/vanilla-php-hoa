<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['user_id'] ?? '';

$stmt = $pdo->prepare('SELECT id, title, completed, created_at FROM tasks WHERE user_id = :userId');
$stmt->execute(['userId' => $userId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vanilla</title>
</head>

<body>

    <h2>Dashboard</h2>
    <p>Bem-vindo, <strong>
            <?= htmlspecialchars($username) ?>
        </strong>!</p>
    <p><a href="logout.php">Sair</a></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Completed</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>

            <?php if (empty($tasks)): ?>
                <tr>
                    <td colspan="5">Nenhuma tarefa encontrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['id']) ?></td>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= $task['completed'] ? 'Sim' : 'Não' ?></td>
                        <td><?= htmlspecialchars($task['created_at']) ?></td>
                        <td><a href="edit.php?id=<?= $task['id'] ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>


        </tbody>
    </table>

    <a href="add.php" class="btn btn-create">Criar Novo Registro</a>

</body>

</html>