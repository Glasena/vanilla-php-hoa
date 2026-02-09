<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = $_GET['id'] ?? null;
$associationId = $_GET['association_id'] ?? null;

if (!$propertyId || !$associationId) {
    header('Location: associations.php');
    exit;
}

// Check membership - only admin and board_member can delete
$stmt = $pdo->prepare('
    SELECT am.role
    FROM association_members am
    WHERE am.association_id = :associationId AND am.user_id = :userId
');
$stmt->execute(['associationId' => $associationId, 'userId' => $userId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if ($membership && in_array($membership['role'], ['admin', 'board_member'])) {
    $stmt = $pdo->prepare('DELETE FROM properties WHERE id = :propertyId AND association_id = :associationId');
    $stmt->execute(['propertyId' => $propertyId, 'associationId' => $associationId]);
}

header('Location: association_dashboard.php?id=' . $associationId);
exit;
