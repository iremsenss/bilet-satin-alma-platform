<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['admin']); // Sadece admin

$db = getdbConnection();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: firmadmins.php');
    exit;
}

// Sadece role=company olan kullanıcı silinebilir
$stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'company'");
$stmt->execute([$id]);

header('Location: firmadmins.php');
exit;
