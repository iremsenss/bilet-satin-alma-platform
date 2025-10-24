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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark">Firma Adminleri</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panele Dön</a>
        </div>

        <div id="successAlert" class="alert alert-success d-none" role="alert">
            Kullanıcı başarıyla silindi!
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
                    <tr id="row-<?= $admin['id'] ?>">
                        <td><?= $admin['id'] ?></td>
                        <td><?= htmlspecialchars($admin['full_name']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td><?= htmlspecialchars($admin['company_name'] ?? '-') ?></td>
                        <td>
                            <a href="edit_firmadmin.php?id=<?= $admin['id'] ?>" class="btn btn-primary btn-sm">Düzenle</a>
                            <button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $admin['id'] ?>">Sil</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Silme -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Onay Gerekiyor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Bu kullanıcıyı silmek istediğinize emin misiniz?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hayır</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Evet, sil</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const deleteBtns = document.querySelectorAll('.deleteBtn');
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        let userIdToDelete = null;

        deleteBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                userIdToDelete = btn.dataset.id;
                confirmModal.show();
            });
        });

        document.getElementById('confirmDelete').addEventListener('click', () => {
            fetch('delete_firmadmin.php?id=' + userIdToDelete, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'confirm=yes'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        confirmModal.hide();
                        const row = document.getElementById('row-' + userIdToDelete);
                        if (row) row.remove();

                        const alert = document.getElementById('successAlert');
                        alert.classList.remove('d-none');

                        setTimeout(() => alert.classList.add('d-none'), 3000);
                    } else {
                        alert('Silme işlemi başarısız!');
                    }
                });
        });
    </script>
</body>

</html>