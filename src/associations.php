<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('
    SELECT a.id, a.name, a.address, a.description, am.role,
           (SELECT COUNT(*) FROM properties p WHERE p.association_id = a.id) AS property_count,
           (SELECT COUNT(*) FROM association_members m WHERE m.association_id = a.id) AS member_count
    FROM associations a
    INNER JOIN association_members am ON am.association_id = a.id
    WHERE am.user_id = :userId
    ORDER BY a.name ASC
');
$stmt->execute(['userId' => $userId]);
$associations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Associations - HOA Manager</title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>My Associations</h2>
            <div class="user-info">
                <p class="user-welcome">
                    Welcome, <strong><?= htmlspecialchars($username) ?></strong>!
                </p>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-actions">
            <a href="add_association.php" class="btn btn-create">Create Association</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>My Role</th>
                        <th>Members</th>
                        <th>Properties</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($associations)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <p>No associations found.</p>
                                <p style="font-size: 14px;">Create your first HOA or ask an admin to add you!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($associations as $assoc): ?>
                            <tr>
                                <td>
                                    <a href="association_dashboard.php?id=<?= $assoc['id'] ?>" style="text-decoration: none; color: var(--primary-color); font-weight: 600;">
                                        <?= htmlspecialchars($assoc['name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($assoc['address'] ?? '-') ?></td>
                                <td>
                                    <span class="role-badge role-<?= htmlspecialchars($assoc['role']) ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $assoc['role']))) ?>
                                    </span>
                                </td>
                                <td><?= (int)$assoc['member_count'] ?></td>
                                <td><?= (int)$assoc['property_count'] ?></td>
                                <td class="actions-cell">
                                    <a href="association_dashboard.php?id=<?= $assoc['id'] ?>" class="btn-edit">Open</a>
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
