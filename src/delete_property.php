<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = $_GET['id'] ?? null;

if ($propertyId) {
    $stmt = $pdo->prepare('DELETE FROM properties WHERE id = :propertyId AND user_id = :userId');
    $stmt->execute(['propertyId' => $propertyId, 'userId' => $userId]);
}

header('Location: dashboard.php');
exit;
