<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/config.php';

requireRole(['company']);

$db = getdbConnection();

$company_id = $_SESSION['company_id'] ?? null;
if (empty($company_id)) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$isError = false;

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
    $isError = (int)($_GET['err'] ?? 0) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code'] ?? '');
    $discount_rate = (float) ($_POST['discount_rate'] ?? 0);
    $usage_limit = (int) ($_POST['usage_limit'] ?? 0);
    $expiry_date = trim($_POST['expiry_date'] ?? '');

    if (empty($code) || $discount_rate <= 0 || $usage_limit <= 0 || empty($expiry_date)) {
        $isError = true;
        $message = "Lütfen tüm alanları eksiksiz ve geçerli doldurun.";
    } elseif ($discount_rate > 1.0) {
        $isError = true;
        $message = "İndirim oranı 1'den (%100) büyük olamaz.";
    } else {
        try {
            $stmt_check = $db->prepare("SELECT COUNT(id) FROM coupons WHERE code = ?");
            $stmt_check->execute([$code]);
            if ($stmt_check->fetchColumn() > 0) {
                $isError = true;
                $message = "Bu kupon kodu zaten sistemde mevcut.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO coupons (code, discount_rate, usage_limit, expiry_date, company_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $code,
                    $discount_rate,
                    $usage_limit,
                    $expiry_date . ' 23:59:59',
                    $company_id
                ]);
                $message = "Kupon başarıyla eklendi!";
            }
        } catch (PDOException $e) {
            $isError = true;
            $message = "VERİTABANI HATASI: " . $e->getMessage();
            error_log("Firma Kupon Ekleme Hata: " . $e->getMessage());
        }
    }
    header("Location: coupons.php?msg=" . urlencode($message) . "&err=" . ($isError ? 1 : 0));
    exit;
}

// --- KUPON SİLME---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $coupon_id = (int)$_POST['delete_coupon'];
    try {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND company_id = ?");
        $result = $stmt->execute([$coupon_id, $company_id]);

        if ($stmt->rowCount() > 0) {
            $message = "Kupon başarıyla silindi!";
            $isError = false;
        } else {
            $isError = true;
            $message = "Kupon bulunamadı veya silme yetkiniz yok.";
        }
    } catch (PDOException $e) {
        $isError = true;
        if (strpos($e->getMessage(), 'FOREIGN KEY constraint failed') !== false) {
            $message = "Kupon silinemedi! Bu kuponu kullanan aktif biletler var.";
        } else {
            $message = "Kupon silme hatası oluştu.";
        }
        error_log("Kupon silme hatası: " . $e->getMessage());
    }
    header("Location: coupons.php?msg=" . urlencode($message) . "&err=" . ($isError ? 1 : 0));
    exit;
}

$all_coupons = getCouponsByCompany($company_id);
$default_coupon_code = generateCouponCode(10);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi - TicketBox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .table td {
            vertical-align: middle;
        }

        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
    </style>
</head>

<body>

    <div class="container-fluid p-4">
        <h2 class="mb-5 mt-3 text-dark">Kupon Yönetimi</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $isError ? 'danger' : 'success' ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-success text-white">Yeni Kupon Ekle</div>
            <div class="card-body">
                <form id="couponForm" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="add_coupon" value="1">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Kupon Kodu</label>
                            <input type="text" class="form-control" name="code"
                                value="<?= htmlspecialchars($default_coupon_code) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">İndirim Oranı (0.01 - 1.0)</label>
                            <input type="number" step="0.01" min="0.01" max="1.0"
                                class="form-control" name="discount_rate" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kullanım Limiti</label>
                            <input type="number" min="1" class="form-control"
                                name="usage_limit" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" name="expiry_date"
                                required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-success btn-sm mt-4">
                                <i class="fas fa-plus"></i> Kupon Ekle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                Mevcut Kuponlar (<?= count($all_coupons) ?>)
            </div>
            <div class="card-body">
                <?php if (!empty($all_coupons)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-success">
                                <tr>
                                    <th>Kod</th>
                                    <th>İndirim</th>
                                    <th>Kullanım</th>
                                    <th>Limit</th>
                                    <th>Son Kullanma</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_coupons as $coupon):
                                    $is_expired = strtotime($coupon['expiry_date']) < time();
                                    $is_limited = $coupon['used_count'] >= $coupon['usage_limit'];
                                    $is_active = !$is_expired && !$is_limited;
                                ?>
                                    <tr class="<?= $is_active ? '' : 'table-danger' ?>">
                                        <td class="fw-bold"><?= htmlspecialchars($coupon['code']) ?></td>
                                        <td><?= number_format($coupon['discount_rate'] * 100, 0) ?>%</td>
                                        <td><?= $coupon['used_count'] ?></td>
                                        <td><?= $coupon['usage_limit'] ?></td>
                                        <td><?= date('d.m.Y', strtotime($coupon['expiry_date'])) ?></td>
                                        <td>
                                            <?php if ($is_expired): ?>
                                                <span class="badge bg-secondary">Süresi Doldu</span>
                                            <?php elseif ($is_limited): ?>
                                                <span class="badge bg-warning text-dark">Limit Doldu</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('<?= htmlspecialchars($coupon['code']) ?> kodlu kuponu silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="delete_coupon"
                                                    value="<?= $coupon['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Henüz tanımlı kupon bulunmamaktadır.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });
    </script>

</body>

</html>