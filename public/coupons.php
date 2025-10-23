<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDbConnection();

$is_logged_in = isLoggedIn();
$user_role = $_SESSION['user_role'] ?? null;

$active_campaigns = getActiveCouponsForDisplay();


?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktif Kampanyalar - TicketBox</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container-fluid ms-4 me-4">
            <a class="navbar-brand fw-bold" href="index.php">TicketBox</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto d-flex align-items-center">
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item"><a class="nav-link text-white" href="user/my_account.php"><i class="fas fa-user-circle"></i> Hesabım / Biletlerim</a></li>
                        <?php if ($user_role === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link text-warning fw-bold" href="admin/index.php"><i class="fas fa-tools"></i> Admin</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link btn btn-sm  ms-2" href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link text-white" href="login.php"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-5 mb-5">
        <h4 class="text-center fw-bold mb-5 text-dark">
            <i class="fas fa-tags me-2" style="color: #9E9999A8;"></i> TÜM KAMPANYALAR
        </h4>

        <?php if (!empty($active_campaigns)): ?>
            <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center">
                <?php foreach ($active_campaigns as $campaign): ?>
                    <?php
                    $firm_text = is_null($campaign['company_id'])
                        ? 'TÜM FİRMALARDA GEÇERLİ'
                        : ($campaign['company_name'] . ' firması seferlerinde geçerli');

                    $discount_percent = (float)$campaign['discount_rate'] * 100;
                    ?>
                    <div class="col">
                        <div class="card h-100 campaign-card shadow-lg border-success">
                            <div class="card-header bg-success text-white fw-bold text-center">
                                %<?= $discount_percent ?> İNDİRİM FIRSATI
                            </div>
                            <div class="card-body text-center">
                                <p class="mb-1 text-muted">Kullanmanız Gereken Kod:</p>
                                <div class="input-group mb-3" style="max-width: 250px; margin-left: auto; margin-right: auto;">
                                    <input type="text" class="form-control text-center fw-bold"
                                        value="<?= htmlspecialchars($campaign['code']) ?>"
                                        readonly style="color: #dc3545;">
                                    <button class="btn btn-outline-success copy-coupon-btn" type="button"
                                        title="Kodu Kopyala" data-coupon-code="<?= htmlspecialchars($campaign['code']) ?>">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                                <p class="card-text">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    <?= htmlspecialchars($firm_text) ?>
                                </p>
                            </div>
                            <div class="card-footer bg-light border-0 text-center">
                                <small class="text-dark fw-bold">
                                    Son Kullanım Tarihi:
                                </small>
                                <small class="text-danger d-block">
                                    <?= date('d.m.Y H:i', strtotime($campaign['expiry_date'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center mx-auto" style="max-width: 600px;">
                <i class="fas fa-exclamation-triangle me-2"></i> Şu anda aktif kullanılabilecek kupon veya kampanya bulunmamaktadır.
            </div>
        <?php endif; ?>
    </div>

    <div class="footer text-center mt-5 p-3 text-muted border-top">
        <small>&copy; <?= date('Y') ?> TicketBox. Tüm hakları saklıdır.</small>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- //kopyalama scripti -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyButtons = document.querySelectorAll('.copy-coupon-btn');

            copyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const couponCode = this.getAttribute('data-coupon-code');

                    navigator.clipboard.writeText(couponCode).then(() => {
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        this.classList.remove('btn-outline-success');
                        this.classList.add('btn-success');

                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.classList.remove('btn-success');
                            this.classList.add('btn-outline-success');
                        }, 2000);
                    }).catch(err => {
                        console.error('Kopyalama hatası:', err);
                        alert('Kod kopyalanamadı: ' + couponCode);
                    });
                });
            });
        });
    </script>
</body>

</html>