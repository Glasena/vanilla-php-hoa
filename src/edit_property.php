<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = $_GET['id'] ?? $_POST['property_id'] ?? null;
$associationId = $_GET['association_id'] ?? $_POST['association_id'] ?? null;

if (!$propertyId || !$associationId) {
    header('Location: associations.php');
    exit;
}

// Check membership and get role
$stmt = $pdo->prepare('
    SELECT am.role, a.name
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
$canManage = in_array($userRole, ['admin', 'board_member']);

// Residents cannot edit
if ($userRole === 'resident') {
    header('Location: association_dashboard.php?id=' . $associationId);
    exit;
}

$error = null;
$property = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lotNumber = trim($_POST['lot_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $areaSqm = trim($_POST['area_sqm'] ?? '');
    $propertyType = trim($_POST['property_type'] ?? 'house');
    $status = trim($_POST['status'] ?? 'active');

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
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $error = 'Invalid status.';
    } else {
        // Build WHERE clause based on permissions
        if ($canManage) {
            $sql = 'UPDATE properties
                    SET lot_number = :lotNumber, address = :address, area_sqm = :areaSqm,
                        property_type = :propertyType, status = :status
                    WHERE id = :propertyId AND association_id = :associationId';
            $params = [
                'lotNumber' => $lotNumber,
                'address' => $address,
                'areaSqm' => $areaSqm !== '' ? $areaSqm : null,
                'propertyType' => $propertyType,
                'status' => $status,
                'propertyId' => $propertyId,
                'associationId' => $associationId,
            ];
        } else {
            // Homeowner can only edit their own
            $sql = 'UPDATE properties
                    SET lot_number = :lotNumber, address = :address, area_sqm = :areaSqm,
                        property_type = :propertyType, status = :status
                    WHERE id = :propertyId AND association_id = :associationId AND user_id = :userId';
            $params = [
                'lotNumber' => $lotNumber,
                'address' => $address,
                'areaSqm' => $areaSqm !== '' ? $areaSqm : null,
                'propertyType' => $propertyType,
                'status' => $status,
                'propertyId' => $propertyId,
                'associationId' => $associationId,
                'userId' => $userId,
            ];
        }

        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute($params);
            header('Location: association_dashboard.php?id=' . $associationId);
            exit;
        } catch (\Throwable $th) {
            error_log($th->getMessage());
            $error = 'Error updating property. Please try again.';
        }
    }

    // Re-populate form with POST data on error
    $property = [
        'lot_number' => $lotNumber,
        'address' => $address,
        'area_sqm' => $areaSqm,
        'property_type' => $propertyType,
        'status' => $status,
    ];
} else {
    // Load property - check permissions
    if ($canManage) {
        $stmt = $pdo->prepare('SELECT id, lot_number, address, area_sqm, property_type, status, user_id
                               FROM properties WHERE id = :propertyId AND association_id = :associationId');
        $stmt->execute(['propertyId' => $propertyId, 'associationId' => $associationId]);
    } else {
        // Homeowner can only edit their own
        $stmt = $pdo->prepare('SELECT id, lot_number, address, area_sqm, property_type, status, user_id
                               FROM properties WHERE id = :propertyId AND association_id = :associationId AND user_id = :userId');
        $stmt->execute(['propertyId' => $propertyId, 'associationId' => $associationId, 'userId' => $userId]);
    }

    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        header('Location: association_dashboard.php?id=' . $associationId);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - <?= htmlspecialchars($membership['name']) ?></title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>Edit Property</h2>
            <div class="user-info">
                <a href="association_dashboard.php?id=<?= $associationId ?>" class="btn btn-outline">Back</a>
            </div>
        </header>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="property_id" value="<?= htmlspecialchars($propertyId) ?>">
                <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">

                <div class="form-group">
                    <label for="lot_number">Lot Number:</label>
                    <input type="text" name="lot_number" id="lot_number" required
                           placeholder="e.g.: L-001"
                           value="<?= htmlspecialchars($property['lot_number'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" name="address" id="address" required
                           placeholder="e.g.: 123 Flower St"
                           value="<?= htmlspecialchars($property['address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="area_sqm">Area (sqm):</label>
                    <input type="text" name="area_sqm" id="area_sqm"
                           placeholder="e.g.: 250.00"
                           value="<?= htmlspecialchars($property['area_sqm'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="property_type">Type:</label>
                    <select name="property_type" id="property_type">
                        <option value="house" <?= ($property['property_type'] ?? '') === 'house' ? 'selected' : '' ?>>House</option>
                        <option value="lot" <?= ($property['property_type'] ?? '') === 'lot' ? 'selected' : '' ?>>Lot</option>
                        <option value="apartment" <?= ($property['property_type'] ?? '') === 'apartment' ? 'selected' : '' ?>>Apartment</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="active" <?= ($property['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($property['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
