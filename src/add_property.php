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

// Check membership and permissions
$stmt = $pdo->prepare('
    SELECT am.role, a.name
    FROM association_members am
    INNER JOIN associations a ON a.id = am.association_id
    WHERE am.association_id = :associationId AND am.user_id = :userId
');
$stmt->execute(['associationId' => $associationId, 'userId' => $userId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership || !in_array($membership['role'], ['admin', 'board_member'])) {
    header('Location: associations.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lotNumber = trim($_POST['lot_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $areaSqm = trim($_POST['area_sqm'] ?? '');
    $propertyType = trim($_POST['property_type'] ?? 'house');

    if (empty($lotNumber)) {
        $error = 'Lot number is required.';
    } elseif (strlen($lotNumber) > 20) {
        $error = 'Lot number is too long (max 20 characters).';
    } elseif (empty($address)) {
        $error = 'Address is required.';
    } elseif (strlen($address) > 255) {
        $error = 'Address is too long (max 255 characters).';
    } elseif ($areaSqm !== '' && (!is_numeric($areaSqm) || $areaSqm < 0)) {
        $error = 'Area must be a valid number.';
    } elseif (!in_array($propertyType, ['house', 'lot', 'apartment'])) {
        $error = 'Invalid property type.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO properties (user_id, association_id, lot_number, address, area_sqm, property_type)
                               VALUES (:userId, :associationId, :lotNumber, :address, :areaSqm, :propertyType)');

        try {
            $stmt->execute([
                'userId' => $userId,
                'associationId' => $associationId,
                'lotNumber' => $lotNumber,
                'address' => $address,
                'areaSqm' => $areaSqm !== '' ? $areaSqm : null,
                'propertyType' => $propertyType,
            ]);

            header('Location: association_dashboard.php?id=' . $associationId);
            exit;
        } catch (\Throwable $th) {
            error_log($th->getMessage());
            $error = 'Error adding property. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - <?= htmlspecialchars($membership['name']) ?></title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>New Property</h2>
            <div class="user-info">
                <a href="association_dashboard.php?id=<?= $associationId ?>" class="btn btn-outline">Back</a>
            </div>
        </header>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">

                <div class="form-group">
                    <label for="lot_number">Lot Number:</label>
                    <input type="text" name="lot_number" id="lot_number" required
                           placeholder="e.g.: L-001"
                           value="<?= htmlspecialchars($_POST['lot_number'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" name="address" id="address" required
                           placeholder="e.g.: 123 Flower St"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="area_sqm">Area (sqm):</label>
                    <input type="text" name="area_sqm" id="area_sqm"
                           placeholder="e.g.: 250.00"
                           value="<?= htmlspecialchars($_POST['area_sqm'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="property_type">Type:</label>
                    <select name="property_type" id="property_type">
                        <option value="house" <?= ($_POST['property_type'] ?? '') === 'house' ? 'selected' : '' ?>>House</option>
                        <option value="lot" <?= ($_POST['property_type'] ?? '') === 'lot' ? 'selected' : '' ?>>Lot</option>
                        <option value="apartment" <?= ($_POST['property_type'] ?? '') === 'apartment' ? 'selected' : '' ?>>Apartment</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="association_dashboard.php?id=<?= $associationId ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
