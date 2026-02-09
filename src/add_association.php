<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Association name is required.';
    } elseif (strlen($name) > 100) {
        $error = 'Name is too long (max 100 characters).';
    } elseif (strlen($address) > 255) {
        $error = 'Address is too long (max 255 characters).';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO associations (name, address, description, created_by)
                                   VALUES (:name, :address, :description, :createdBy)');
            $stmt->execute([
                'name' => $name,
                'address' => $address !== '' ? $address : null,
                'description' => $description !== '' ? $description : null,
                'createdBy' => $_SESSION['user_id'],
            ]);

            $associationId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO association_members (association_id, user_id, role)
                                   VALUES (:associationId, :userId, :role)');
            $stmt->execute([
                'associationId' => $associationId,
                'userId' => $_SESSION['user_id'],
                'role' => 'admin',
            ]);

            $pdo->commit();

            header('Location: associations.php');
            exit;
        } catch (\Throwable $th) {
            $pdo->rollBack();
            error_log($th->getMessage());
            $error = 'Error creating association. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Association - HOA Manager</title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>New Association</h2>
            <div class="user-info">
                <a href="associations.php" class="btn btn-outline">Back</a>
            </div>
        </header>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Association Name:</label>
                    <input type="text" name="name" id="name" required
                           placeholder="e.g.: Sunset Valley HOA"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" name="address" id="address"
                           placeholder="e.g.: 100 Main St, Springfield"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4"
                              placeholder="Brief description of the association..."
                              style="width: 100%;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="associations.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
