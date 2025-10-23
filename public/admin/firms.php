<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pdo = getdbConnection();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_firm'])) {
    $firm_name = trim($_POST['firm_name'] ?? '');
    $logo_path = !empty($_POST['logo_path']) ? trim($_POST['logo_path']) : null;

    if (empty($firm_name)) {
        $error_message = 'Firma adı boş bırakılamaz.';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE name = :name");
        $check->bindParam(':name', $firm_name);
        $check->execute();

        if ($check->fetchColumn() > 0) {
            $error_message = 'Bu firma zaten mevcut.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO companies (name, logo_path, created_at) VALUES (:name, :logo_path,datetime('now'))");
            $stmt->bindParam(':name', $firm_name);
            $stmt->bindParam(':logo_path', $logo_path);


            if ($stmt->execute()) {
                $success_message = 'Firma başarıyla eklendi.';
            } else {
                $error_message = 'Firma eklenirken bir hata oluştu.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_firm'])) {
    $firm_id = $_POST['firm_id'] ?? '';

    if (empty($firm_id)) {
        $error_message = 'Lütfen silinecek firmayı seçiniz.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM companies WHERE id = :id");
        $stmt->bindParam(':id', $firm_id);

        if ($stmt->execute()) {
            $success_message = 'Firma başarıyla silindi.';
        } else {
            $error_message = 'Firma silinirken bir hata oluştu.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_firm'])) {
    $firm_id = $_POST['firm_id'] ?? '';
    $firm_name = trim($_POST['firm_name'] ?? '');
    $logo_path = !empty($_POST['logo_path']) ? trim($_POST['logo_path']) : null;

    if (empty($firm_id)) {
        $error_message = 'Lütfen firmayı seçiniz.';
    } else {
        $stmt = $pdo->prepare("UPDATE companies SET name = :name, logo_path = :logo_path WHERE id = :id");
        $stmt->bindParam(':name', $firm_name);
        $stmt->bindParam(':logo_path', $logo_path);
        $stmt->bindParam(':id', $firm_id);

        if ($stmt->execute()) {
            $success_message = 'Firma başarıyla güncellendi.';
        } else {
            $error_message = 'Firma güncellenirken bir hata oluştu.';
        }
    }
}
$edit_mode = false;
$edit_firm_id = '';
$edit_firm_name = '';
$edit_logo_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_firm'])) {
    $edit_mode = true;
    $edit_firm_id = $_POST['firm_id'];
    $edit_firm_name = $_POST['firm_name'];
    $edit_logo_path = $_POST['logo_path'];
}


$firms = $pdo->query("SELECT * FROM companies ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetimi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <div class="container-fluid d-flex flex-column min-vh-100 p-0">


        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-dark text-white text-center">
                <h4>Firma Yönetimi</h4>
            </div>
            <div class="card-body">

                <?php if ($error_message): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
                <?php elseif ($success_message): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <form action="firms.php" method="post" class="mb-4 row">
                    <div class="row g-2">
                        <input type="hidden" name="firm_id" value="<?= $edit_firm_id ?>">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="firm_name" placeholder="Firma adı..." required value="<?= htmlspecialchars($edit_firm_name) ?>">
                        </div>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="logo_path" placeholder="Logo URL (opsiyonel)" value="<?= htmlspecialchars($edit_logo_path) ?>">
                        </div>
                        <div class="col-md-2 d-grid">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="update_firm" class="btn btn-primary">Güncelle</button>
                            <?php else: ?>
                                <button type="submit" name="add_firm" class="btn btn-success">Ekle</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>


                <h5 class="text-center mb-3">Mevcut Firmalar</h5>
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th></th>
                            <th>Logo</th>
                            <th>Firma Adı</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($firms): ?>
                            <?php foreach ($firms as $firm): ?>
                                <tr>
                                    <td><?= htmlspecialchars($firm['id']) ?></td>
                                    <td>
                                        <?php if ($firm['logo_path']): ?>
                                            <img src="<?= htmlspecialchars($firm['logo_path']) ?>" alt="Logo" style="height:40px;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($firm['name']) ?></td>
                                    <td><?= htmlspecialchars($firm['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <form action="firms.php" method="post" class="bg-danger" onsubmit="return confirm('Bu firmayı silmek istediğine emin misin?');">
                                                <input type="hidden" name="firm_id" value="<?= $firm['id'] ?>">
                                                <button type="submit" name="delete_firm" class="btn btn-sm ">Sil</button>
                                            </form>

                                            <form action="firms.php" method="post" class="d-inline mt-2 bg-warning" onsubmit="return confirm('Bu firmayı düzenlemek istediğine emin misin?');">
                                                <input type="hidden" name="firm_id" value="<?= $firm['id'] ?>">
                                                <input type="hidden" name="firm_name" value="<?= htmlspecialchars($firm['name']) ?>">
                                                <input type="hidden" name="logo_path" value="<?= htmlspecialchars($firm['logo_path']) ?>">
                                                <button type="submit" name="edit_firm" class="btn btn-sm ">Güncelle</button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-muted">Henüz firma eklenmemiş.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">← Admin Panele Dön</a>
                </div>
            </div>
        </div>

    </div>


</body>

</html>