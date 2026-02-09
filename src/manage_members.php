<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$associationId = $_GET['association_id'] ?? $_POST['association_id'] ?? null;

if (!$associationId) {
    header('Location: associations.php');
    exit;
}

// Check that user is admin of this association
$stmt = $pdo->prepare('
    SELECT am.role, a.name
    FROM association_members am
    INNER JOIN associations a ON a.id = am.association_id
    WHERE am.association_id = :associationId AND am.user_id = :userId
');
$stmt->execute(['associationId' => $associationId, 'userId' => $userId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership || $membership['role'] !== 'admin') {
    header('Location: associations.php');
    exit;
}

$associationName = $membership['name'];
$error = null;
$success = null;

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $memberId = $_POST['member_id'] ?? null;
    $action = $_POST['action'];

    if ($action === 'update_role') {
        $newRole = $_POST['role'] ?? '';
        if (!in_array($newRole, ['admin', 'board_member', 'homeowner', 'resident'])) {
            $error = 'Invalid role.';
        } elseif ($memberId) {
            // Prevent removing last admin
            if ($newRole !== 'admin') {
                $stmt = $pdo->prepare('SELECT user_id FROM association_members WHERE id = :memberId AND association_id = :associationId');
                $stmt->execute(['memberId' => $memberId, 'associationId' => $associationId]);
                $targetMember = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($targetMember) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM association_members WHERE association_id = :associationId AND role = :role AND user_id != :targetUserId');
                    $stmt->execute(['associationId' => $associationId, 'role' => 'admin', 'targetUserId' => $targetMember['user_id']]);
                    $otherAdmins = (int)$stmt->fetchColumn();

                    if ($otherAdmins === 0) {
                        $error = 'Cannot change role. At least one admin must remain.';
                    }
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare('UPDATE association_members SET role = :role WHERE id = :memberId AND association_id = :associationId');
                $stmt->execute(['role' => $newRole, 'memberId' => $memberId, 'associationId' => $associationId]);
                $success = 'Role updated successfully.';
            }
        }
    } elseif ($action === 'remove') {
        if ($memberId) {
            // Prevent removing yourself if you're the last admin
            $stmt = $pdo->prepare('SELECT user_id FROM association_members WHERE id = :memberId AND association_id = :associationId');
            $stmt->execute(['memberId' => $memberId, 'associationId' => $associationId]);
            $targetMember = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($targetMember && (int)$targetMember['user_id'] === (int)$userId) {
                $error = 'You cannot remove yourself from the association.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM association_members WHERE id = :memberId AND association_id = :associationId');
                $stmt->execute(['memberId' => $memberId, 'associationId' => $associationId]);
                $success = 'Member removed successfully.';
            }
        }
    }
}

// Fetch members
$stmt = $pdo->prepare('
    SELECT am.id, am.role, am.joined_at, u.username
    FROM association_members am
    INNER JOIN users u ON u.id = am.user_id
    WHERE am.association_id = :associationId
    ORDER BY am.role ASC, u.username ASC
');
$stmt->execute(['associationId' => $associationId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - <?= htmlspecialchars($associationName) ?></title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>Members - <?= htmlspecialchars($associationName) ?></h2>
            <div class="user-info">
                <a href="association_dashboard.php?id=<?= $associationId ?>" class="btn btn-outline">Back</a>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="error-message message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="dashboard-actions">
            <a href="add_member.php?association_id=<?= $associationId ?>" class="btn btn-create">Add Member</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <p>No members found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= htmlspecialchars($member['username']) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="association_id" value="<?= $associationId ?>">
                                        <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                        <input type="hidden" name="action" value="update_role">
                                        <select name="role" onchange="this.form.submit()" style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                            <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            <option value="board_member" <?= $member['role'] === 'board_member' ? 'selected' : '' ?>>Board Member</option>
                                            <option value="homeowner" <?= $member['role'] === 'homeowner' ? 'selected' : '' ?>>Homeowner</option>
                                            <option value="resident" <?= $member['role'] === 'resident' ? 'selected' : '' ?>>Resident</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?= date('M d, Y', strtotime($member['joined_at'])) ?></td>
                                <td class="actions-cell">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="association_id" value="<?= $associationId ?>">
                                        <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Remove this member?')" style="background: none; cursor: pointer; font-size: inherit;">Remove</button>
                                    </form>
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
