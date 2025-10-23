<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['company']); // Sadece company rolü erişebilir

$db = getdbConnection();

$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trip_id <= 0) {
    echo "invalid";
    exit;
}

try {
    $stmt = $db->prepare("DELETE FROM trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $_SESSION['company_id']]);

    if ($stmt->rowCount() > 0) {
        echo "success";
    } else {
        echo "notfound";
    }
} catch (PDOException $e) {
    echo "error";
}
