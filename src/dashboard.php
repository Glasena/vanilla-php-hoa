<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['user_id'] ?? '';

$stmt = $pdo->prepare('SELECT id, lot_number, address, area_sqm, property_type, status, created_at FROM properties WHERE user_id = :userId ORDER BY created_at DESC');
$stmt->execute(['userId' => $userId]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HOA Manager</title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>My Properties</h2>
            <div class="user-info">
                <p class="user-welcome">
                    Welcome, <strong><?= htmlspecialchars($username) ?></strong>!
                </p>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-actions">
            <a href="add_property.php" class="btn btn-create">Add Property</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Lot</th>
                        <th>Address</th>
                        <th>Area (sqm)</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($properties)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-state-icon">🏠</div>
                                <p>No properties found.</p>
                                <p style="font-size: 14px;">Start by adding your first property!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td><?= htmlspecialchars($property['lot_number']) ?></td>
                                <td><?= htmlspecialchars($property['address']) ?></td>
                                <td><?= $property['area_sqm'] ? number_format((float)$property['area_sqm'], 2, ',', '.') : '-' ?></td>
                                <td>
                                    <span class="type-badge type-<?= htmlspecialchars($property['property_type']) ?>">
                                        <?= htmlspecialchars(ucfirst($property['property_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($property['status']) ?>">
                                        <?= $property['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="edit_property.php?id=<?= $property['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="delete_property.php?id=<?= $property['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this property?')">Delete</a>
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
