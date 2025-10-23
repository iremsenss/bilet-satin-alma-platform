<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);

$db = getdbConnection();

$companies = $db->query("SELECT * FROM companies")->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $company_id = $_POST['company_id'] ?: null;

    if ($full_name && $email && $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (full_name,email,password,role,company_id) VALUES (?,?,?,?,?)");
        $stmt->execute([$full_name, $email, $hashed_password, 'company', $company_id]);
        $message = "Firma Admin başarıyla eklendi!";
    } else {
        $message = "Lütfen tüm alanları doldurun.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Yeni Firma Admin Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h2>Yeni Firma Admin Ekle</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <form method="post" class="mt-4">
            <div class="mb-3">
                <label for="full_name" class="form-label">Ad Soyad</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Parola</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="company_id" class="form-label">Firma</label>
                <select name="company_id" id="company_id" class="form-select">
                    <option value="">Seçiniz...</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success">Ekle</button>
            <a href="firmadmins.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
</body>

</html>