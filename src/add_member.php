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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? 'homeowner');

    if (empty($username)) {
        $error = 'Username is required.';
    } elseif (!in_array($role, ['admin', 'board_member', 'homeowner', 'resident'])) {
        $error = 'Invalid role.';
    } else {
        // Find the user
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            $error = 'User not found. Make sure the username is correct.';
        } else {
            // Check if already a member
            $stmt = $pdo->prepare('SELECT id FROM association_members WHERE association_id = :associationId AND user_id = :targetUserId');
            $stmt->execute(['associationId' => $associationId, 'targetUserId' => $targetUser['id']]);

            if ($stmt->fetch()) {
                $error = 'This user is already a member of this association.';
            } else {
                try {
                    $stmt = $pdo->prepare('INSERT INTO association_members (association_id, user_id, role)
                                           VALUES (:associationId, :userId, :role)');
                    $stmt->execute([
                        'associationId' => $associationId,
                        'userId' => $targetUser['id'],
                        'role' => $role,
                    ]);

                    header('Location: manage_members.php?association_id=' . $associationId);
                    exit;
                } catch (\Throwable $th) {
                    error_log($th->getMessage());
                    $error = 'Error adding member. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member - <?= htmlspecialchars($associationName) ?></title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>Add Member</h2>
            <div class="user-info">
                <a href="manage_members.php?association_id=<?= $associationId ?>" class="btn btn-outline">Back</a>
            </div>
        </header>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required
                           placeholder="Enter the username of the person to add"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select name="role" id="role">
                        <option value="homeowner" <?= ($_POST['role'] ?? '') === 'homeowner' ? 'selected' : '' ?>>Homeowner</option>
                        <option value="resident" <?= ($_POST['role'] ?? '') === 'resident' ? 'selected' : '' ?>>Resident</option>
                        <option value="board_member" <?= ($_POST['role'] ?? '') === 'board_member' ? 'selected' : '' ?>>Board Member</option>
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Member</button>
                    <a href="manage_members.php?association_id=<?= $associationId ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
