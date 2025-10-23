<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/config.php'; // DB bağlantısı buradan gelir.

requireRole(['admin']); // Sadece Admin erişebilir

$db = getdbConnection(); // Bağlantıyı al

$message = '';
$isError = false;

// --- 1. KUPON EKLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code'] ?? '');
    $discount_rate = (float) ($_POST['discount_rate'] ?? 0);
    $usage_limit = (int) ($_POST['usage_limit'] ?? 0);
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $company_id = empty($_POST['company_id']) ? null : (int) $_POST['company_id'];

    if (empty($code) || $discount_rate <= 0 || $usage_limit <= 0 || empty($expiry_date)) {
        $isError = true;
        $message = "Lütfen tüm alanları eksiksiz ve geçerli doldurun.";
    } elseif ($discount_rate > 1.0) {
        $isError = true;
        $message = "İndirim oranı 1'den (%100) büyük olamaz.";
    } else {
        try {
            // Kupon kodunun benzersizliğini kontrol et
            $stmt_check = $db->prepare("SELECT COUNT(id) FROM coupons WHERE code = ?");
            $stmt_check->execute([$code]);
            if ($stmt_check->fetchColumn() > 0) {
                $isError = true;
                $message = "Bu kupon kodu zaten mevcut.";
            } else {
                // Kuponu veritabanına ekle
                // NOT: 'used_count' sütununu atlıyoruz, çünkü DB 'DEFAULT 0' atayacaktır.
                $stmt = $db->prepare("
                    INSERT INTO coupons (code, discount_rate, usage_limit, expiry_date, company_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $code,
                    $discount_rate,
                    $usage_limit,
                    $expiry_date . ' 23:59:59', // Günün sonuna ayarla
                    $company_id
                ]);
                $message = "Kupon başarıyla eklendi!";
            }
        } catch (PDOException $e) {
            $isError = true;
            // KRİTİK DÜZELTME: Veritabanı hatasını ekranda göster
            $message = "VERİTABANI HATASI: " . $e->getMessage();
            error_log("Kupon Ekleme Hata: " . $e->getMessage());
        }
    }
}


// --- 2. KUPON SİLME İŞLEMİ ---
// Kupon Silme İşlemi (Düzeltildi)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $coupon_id = (int)$_POST['delete_coupon'];

    // YENİ MANTIK: Kullanımdaysa silme hatasını göster
    try {
        // Kuponu direkt siliyoruz. Eğer FOREIGN KEY kısıtlaması varsa, PDOException fırlatılacak.
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);

        $message = "Kupon başarıyla silindi!";
        $isError = false;
    } catch (PDOException $e) {
        $isError = true;

        // SQLite'ta FOREIGN KEY hatası genellikle "FOREIGN KEY constraint failed" içerir
        if (strpos($e->getMessage(), 'FOREIGN KEY constraint failed') !== false) {
            $message = "Kupon silinemedi! Bu kuponu kullanan aktif biletler bulunmaktadır. Önce biletleri iptal edin.";
        } else {
            $message = "Kupon silme hatası: " . $e->getMessage();
        }
    }
}


// --- 3. PASİF YAPMA VE VERİ ÇEKME ---

// DÜZELTME: Kupon tablosunda 'status' sütunu yok. Bu yüzden bu kısmı tamamen siliyoruz
// (veya tablonuza eklemedikçe kullanmıyoruz). Durum kontrolü sadece PHP'de yapılır.
// $db->query("SELECT * FROM coupons"); satırı da gereksizdir ve kaldırılmıştır.

$all_coupons = getAllCoupons();
$all_companies = getAllCompanies();

// Kupon kodu üretme
$default_coupon_code = '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $isError) {
    $default_coupon_code = generateCouponCode(10);
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Kupon Yönetimi | Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark">Kupon Yönetimi</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panele Dön</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-dark text-white">Yeni Kupon Ekle</div>
            <div class="card-body">
                <form id="couponForm" method="POST" action="coupons.php">
                    <input type="hidden" name="add_coupon" value="1">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="code" class="form-label">Kupon Kodu</label>
                            <input type="text" class="form-control" id="code" name="code" value="<?= htmlspecialchars($default_coupon_code) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="discount_rate" class="form-label">İndirim Oranı (0.01 - 1.0)</label>
                            <input type="number" step="0.01" min="0.01" max="1.0" class="form-control" id="discount_rate" name="discount_rate" required>
                        </div>
                        <div class="col-md-2">
                            <label for="usage_limit" class="form-label">Kullanım Limiti</label>
                            <input type="number" min="1" class="form-control" id="usage_limit" name="usage_limit" required>
                        </div>
                        <div class="col-md-2">
                            <label for="expiry_date" class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="company_id" class="form-label">Firma (Opsiyonel)</label>
                            <select class="form-select" id="company_id" name="company_id">
                                <option value="">Tüm Firmalar (Genel)</option>
                                <?php foreach ($all_companies as $company): ?>
                                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success mt-3"><i class="fas fa-plus"></i> Kupon Ekle</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h3 class="mt-5 mb-3">Mevcut Kuponlar (<?= count($all_coupons) ?>)</h3>

        <?php if (!empty($all_coupons)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Kod</th>
                            <th>Firma</th>
                            <th>İndirim</th>
                            <th>Kullanım</th>
                            <th>Sınır</th>
                            <th>S.K. Tarihi</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
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
                                <td><?= htmlspecialchars($coupon['company_name'] ?? 'GENEL') ?></td>
                                <td><?= (float)$coupon['discount_rate'] * 100 ?>%</td>
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
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCouponModal<?= $coupon['id'] ?>">
                                        <i class="fas fa-trash"></i> Sil
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Henüz tanımlı kupon bulunmamaktadır.</div>
        <?php endif; ?>

    </div>

    <?php foreach ($all_coupons as $coupon): ?>
        <div class="modal fade" id="deleteCouponModal<?= $coupon['id'] ?>" tabindex="-1" aria-labelledby="deleteCouponLabel<?= $coupon['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteCouponLabel<?= $coupon['id'] ?>">Kupon Silme Onayı</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3"><strong><?= htmlspecialchars($coupon['code']) ?></strong> kodlu kuponu silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger"
                            onclick="confirmDelete(<?= $coupon['id'] ?>)">
                            Evet, Sil
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <form id="masterDeleteForm" method="post" action="coupons.php" style="display:none;">
        <input type="hidden" name="delete_coupon" id="deleteCouponIdInput" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function confirmDelete(couponId) {
            // Modal ID'sini al
            const modalElement = document.getElementById('deleteCouponModal' + couponId);

            // 1. Modalı kapat
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
                // 100ms gecikme ekliyoruz, Bootstrap'in kapanma animasyonu bitene kadar beklemek için
                setTimeout(() => {
                    // 2. Gizli forma değeri at
                    document.getElementById('deleteCouponIdInput').value = couponId;
                    // 3. Formu gönder
                    document.getElementById('masterDeleteForm').submit();
                }, 100);
            } else {
                // Eğer modal örneği bulunamazsa, direkt submit et (hata toleransı)
                document.getElementById('deleteCouponIdInput').value = couponId;
                document.getElementById('masterDeleteForm').submit();
            }
        }
    </script>
</body>

</html>