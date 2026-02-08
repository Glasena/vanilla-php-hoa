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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="dashboard-header">
            <h2>📋 Dashboard</h2>
            <div class="user-info">
                <p class="user-welcome">
                    Welcome, <strong><?= htmlspecialchars($username) ?></strong>!
                </p>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <!-- Ações -->
        <div class="dashboard-actions">
            <a href="add.php" class="btn btn-create">Create New Task</a>
        </div>

        <!-- Tabela de Tarefas -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Creation Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div class="empty-state-icon">📝</div>
                                <p>No tasks found.</p>
                                <p style="font-size: 14px;">Start by creating your first task!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['id']) ?></td>
                                <td><?= htmlspecialchars($task['title']) ?></td>
                                <td>
                                    <span class="status-badge <?= $task['completed'] ? 'status-completed' : 'status-pending' ?>">
                                        <?= $task['completed'] ? '✓ Completed' : '⏳ Pending' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($task['created_at'])) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $task['id'] ?>" class="btn-edit">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>