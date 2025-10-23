<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['admin']);

$db = getdbConnection();

try {
    $stmt = $db->query("
        SELECT 
            u.id, 
            u.full_name, 
            u.email, 
            c.name AS company_name
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE u.role = 'company'
    ");
    $firmAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Veritabanı hatası: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Firma Adminleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark">Firma Adminleri</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panele Dön</a>
        </div>
        <a href="add_firmadmin.php" class="btn btn-success mb-3">Yeni Firma Admin Ekle</a>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Email</th>
                    <th>Firma</th>
                    <th>Eylemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($firmAdmins as $admin): ?>
                    <tr>
                        <td><?= $admin['id'] ?></td>
                        <td><?= htmlspecialchars($admin['full_name']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td><?= htmlspecialchars($admin['company_name'] ?? '-') ?></td>
                        <td>
                            <a href="edit_firmadmin.php?id=<?= $admin['id'] ?>" class="btn btn-primary btn-sm">Düzenle</a>
                            <a href="delete_firmadmin.php?id=<?= $admin['id'] ?>" class="btn btn-danger btn-sm">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</html>