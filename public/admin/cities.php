<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';



if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pdo = getdbConnection();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_city'])) {
    $city_name = trim($_POST['city_name'] ?? '');

    if (empty($city_name)) {
        $error_message = 'Şehir adı boş bırakılamaz.';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM cities WHERE name = :name");
        $check->bindParam(':name', $city_name);
        $check->execute();
        $exists = $check->fetchColumn();

        if ($exists > 0) {
            $error_message = 'Bu şehir zaten mevcut.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO cities (name) VALUES (:name)");
            $stmt->bindParam(':name', $city_name);

            if ($stmt->execute()) {
                $success_message = 'Şehir başarıyla eklendi.';
            } else {
                $error_message = 'Şehir eklenirken bir hata oluştu.';
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_city'])) {
    $city_id = $_POST['city_id'] ?? '';

    if (empty($city_id)) {
        $error_message = 'Lütfen silinecek şehri seçiniz.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM cities WHERE id = :id");
        $stmt->bindParam(':id', $city_id);

        if ($stmt->execute()) {
            $success_message = 'Şehir başarıyla silindi.';
        } else {
            $error_message = 'Şehir silinirken bir hata oluştu.';
        }
    }
}

$cities = $pdo->query("SELECT * FROM cities ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şehir Yönetimi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <div class="container-fluid d-flex flex-column min-vh-100 p-0">

        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-dark text-white text-center p-4">
                <h4 class="m-0" style="font-size: 1.25rem;">Şehir Yönetimi</h4>

                <div class="text-end m-0" style="position: absolute; right: 10px; top: 20px;">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">← Admin Panele Dön</a>
                </div>
            </div>
            <div class="card-body">

                <?php if ($error_message): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
                <?php elseif ($success_message): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <form action="cities.php" method="post" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="city_name" placeholder="Yeni şehir adı girin..." required>
                        <button type="submit" name="add_city" class="btn btn-success">Ekle</button>
                    </div>
                </form>

                <h5 class="text-center mb-3">Mevcut Şehirler</h5>
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Şehir Adı</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cities): ?>
                            <?php foreach ($cities as $city): ?>
                                <tr>
                                    <td><?= htmlspecialchars($city['id']) ?></td>
                                    <td><?= htmlspecialchars($city['name']) ?></td>
                                    <td>
                                        <form action="cities.php" method="post" onsubmit="return confirm('Bu şehri silmek istediğine emin misin?');">
                                            <input type="hidden" name="city_id" value="<?= $city['id'] ?>">
                                            <button type="submit" name="delete_city" class="btn btn-sm btn-danger">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-muted">Henüz şehir eklenmemiş.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>


            </div>
        </div>

    </div>

</body>

</html>