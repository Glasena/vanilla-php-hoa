<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$associationId = $_GET['id'] ?? null;

if (!$associationId) {
    header('Location: associations.php');
    exit;
}

// Check membership and get role
$stmt = $pdo->prepare('
    SELECT am.role, a.name, a.address, a.description
    FROM association_members am
    INNER JOIN associations a ON a.id = am.association_id
    WHERE am.association_id = :associationId AND am.user_id = :userId
');
$stmt->execute(['associationId' => $associationId, 'userId' => $userId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    header('Location: associations.php');
    exit;
}

$userRole = $membership['role'];
$associationName = $membership['name'];
$canManage = in_array($userRole, ['admin', 'board_member']);

// Fetch properties for this association
$stmt = $pdo->prepare('
    SELECT p.id, p.lot_number, p.address, p.area_sqm, p.property_type, p.status, p.user_id,
           u.username AS owner_name
    FROM properties p
    LEFT JOIN users u ON u.id = p.user_id
    WHERE p.association_id = :associationId
    ORDER BY p.created_at DESC
');
$stmt->execute(['associationId' => $associationId]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($associationName) ?> - HOA Manager</title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div>
                <h2><?= htmlspecialchars($associationName) ?></h2>
                <p style="color: var(--text-light); margin-top: 4px;">
                    <span class="role-badge role-<?= htmlspecialchars($userRole) ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $userRole))) ?>
                    </span>
                </p>
            </div>
            <div class="user-info">
                <p class="user-welcome">
                    Welcome, <strong><?= htmlspecialchars($username) ?></strong>!
                </p>
                <a href="associations.php" class="btn btn-outline">My Associations</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-actions">
            <?php if ($canManage): ?>
                <a href="add_property.php?association_id=<?= $associationId ?>" class="btn btn-create">Add Property</a>
            <?php endif; ?>
            <?php if ($userRole === 'admin'): ?>
                <a href="manage_members.php?association_id=<?= $associationId ?>" class="btn btn-outline" style="margin-left: var(--spacing-sm);">Manage Members</a>
            <?php endif; ?>
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
                        <th>Owner</th>
                        <?php if ($userRole !== 'resident'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($properties)): ?>
                        <tr>
                            <td colspan="<?= $userRole !== 'resident' ? 7 : 6 ?>" class="empty-state">
                                <p>No properties found.</p>
                                <?php if ($canManage): ?>
                                    <p style="font-size: 14px;">Start by adding the first property!</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($properties as $property): ?>
                            <?php
                            $isOwner = (int)$property['user_id'] === (int)$userId;
                            $canEdit = $canManage || ($userRole === 'homeowner' && $isOwner);
                            $canDelete = $canManage;
                            ?>
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
                                <td><?= htmlspecialchars($property['owner_name'] ?? '-') ?></td>
                                <?php if ($userRole !== 'resident'): ?>
                                    <td class="actions-cell">
                                        <?php if ($canEdit): ?>
                                            <a href="edit_property.php?id=<?= $property['id'] ?>&association_id=<?= $associationId ?>" class="btn-edit">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <a href="delete_property.php?id=<?= $property['id'] ?>&association_id=<?= $associationId ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this property?')">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
