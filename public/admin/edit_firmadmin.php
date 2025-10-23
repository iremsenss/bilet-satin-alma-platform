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

$companies = $db->query("SELECT id, name FROM companies")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'company'");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Firma Admin bulunamadı!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $company_id = $_POST['company_id'] ?: null;

    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, company_id = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $company_id, $id]);

    header('Location: firmadmins.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Firma Admin Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h2>Firma Admin Düzenle</h2>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Firma</label>
                <select name="company_id" class="form-select">
                    <option value="">Seçiniz...</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>" <?= ($admin['company_id'] == $company['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="firmadmins.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</body>

</html>