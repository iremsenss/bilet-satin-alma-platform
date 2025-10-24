<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$message = '';
$isError = false;

// --- Bilet İptal İşlemi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    $ticket_id = $_POST['ticket_id'] ?? null;

    if ($ticket_id && is_numeric($ticket_id)) {
        // İptal işlemini gerçekleştiren fonksiyon çağrısı yapıyoruz
        $result = cancelTicket($ticket_id, $user_id);

        $isError = !$result['success'];
        $message = $result['message'];
    } else {
        $isError = true;
        $message = "Geçersiz bilet ID'si.";
    }
}

// --- Bakiye Yükleme İşlemi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credit'])) {
    $credit_amount = filter_input(INPUT_POST, 'credit_amount', FILTER_VALIDATE_FLOAT);

    if ($credit_amount !== false && $credit_amount > 0) {
        // Yükleme işlemini gerçekleştiren fonksiyon çağrısı yapıyoruz
        $result = addCreditToUser($user_id, $credit_amount);

        $isError = !$result['success'];
        $message = $result['message'];
    } else {
        $isError = true;
        $message = "Geçerli bir bakiye tutarı girmelisiniz.";
    }
}


$current_time = date('Y-m-d H:i:s');

$active_tickets = getTicketsByUser($user_id, true);

$past_tickets = getTicketsByUser($user_id, false);

// İşlem sonrası bakiyeyi tekrar çekiyoruz
$user_balance = getUserBalance($user_id);


// YENİ: Kullanıcının profil bilgilerini çek
// NOT: getUserProfileInfo() fonksiyonunun functions.php'de tanımlı olması gerekir.
$user_profile = getUserProfileInfo($user_id);
$user_full_name = $user_profile['full_name'] ?? 'Misafir Kullanıcı';
$user_email = $user_profile['email'] ?? 'bilinmiyor@example.com';

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım ve Biletlerim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .company-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .past-ticket {
            opacity: 0.7;
        }

        /* Yeni profil kartı için özel stil ekleyelim */
        .profile-card {
            border-left: 5px solid #28a745;
            /* Yeşil sol kenarlık */
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container-fluid ms-4 me-4">
            <a class="navbar-brand fw-bold" href="../index.php">TicketBox</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto d-flex align-items-center">
                    <li class="nav-item">
                        <span class="nav-link text-white-50">Kredi: <?= number_format($user_balance, 2) ?> ₺</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm ms-2" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h3 class="mb-4 text-success text-center">Hesabım ve Biletlerim</h3>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $isError ? 'danger' : 'success' ?> text-center">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm p-4 mb-4 profile-card">
                    <h5 class="text-success mb-3"><i class="fas fa-user-circle me-2"></i> Hesap Bilgileri</h5>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <p class="mb-0 text-muted">Adı Soyadı:</p>
                            <p class="fw-bold text-dark"><?= htmlspecialchars($user_full_name) ?></p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <p class="mb-0 text-muted">E-posta:</p>
                            <p class="fw-bold text-dark"><?= htmlspecialchars($user_email) ?></p>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="card bg-success shadow-sm p-3 flex-grow-1 text-white text-center me-3 ">
                        <h5 class="mb-0"><i class="fas fa-wallet me-2"></i> Sanal Kredi Bakiyeniz: <span class="fw-bold"><?= number_format($user_balance, 2) ?> ₺</span></h5>
                    </div>
                    <button type="button" class="btn btn-info shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#addCreditModal">
                        <i class="fas fa-plus-circle me-1"></i> Bakiye Yükle
                    </button>
                </div>


                <h3 class="mt-5 mb-3 text-success">Aktif Seferleriniz</h3>
                <div class="card shadow-lg p-3 rounded-4">
                    <?php if (!empty($active_tickets)): ?>
                        <?php foreach ($active_tickets as $ticket):
                            $departure_datetime = strtotime($ticket['departure_time']);
                            // Biletin iptal edilebilir olması için kalkışa 1 saatten fazla süre olmalı
                            $is_cancellable = $departure_datetime > (time() + 3600);
                        ?>
                            <div class="d-flex justify-content-between align-items-center p-3 mb-2 border-bottom ticket-item">

                                <div class="ticket-details-group d-flex align-items-center">

                                    <div class="ticket-details-group d-flex align-items-center">
                                        <?php if (!empty($ticket['logo_path'])): ?>
                                            <img src="<?= htmlspecialchars($ticket['logo_path']) ?>"
                                                alt="<?= htmlspecialchars($ticket['company_name']) ?> Logo"
                                                class="company-logo me-3">
                                        <?php else: ?>
                                            <i class="fas fa-bus-alt me-3 text-primary" style="font-size: 2rem;"></i>
                                        <?php endif; ?>
                                    </div>

                                    <div class="ticket-info">
                                        <div>
                                            <span class="badge me-2 bg-success">AKTİF</span>
                                            <span class="fw-bold"><?= htmlspecialchars($ticket['departure_city_name']) ?> &rarr; <?= htmlspecialchars($ticket['destination_city_name']) ?></span>
                                        </div>

                                        <small class="text-muted mt-1 d-block">
                                            Kalkış: <?= date('d.m.Y H:i', $departure_datetime) ?> | Koltuk: #<?= htmlspecialchars($ticket['seat_number']) ?>
                                        </small>

                                        <small class="fw-bold mt-1 text-primary d-block">Ödenen Tutar: <?= number_format($ticket['final_price'], 2) ?> ₺</small>
                                    </div>
                                </div>
                                <div class="ticket-actions d-flex gap-2">

                                    <a href="generate_pdf.php?ticket_id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-info text-white" title="PDF İndir">
                                        <i class="fas fa-file-pdf"></i> PDF İndir
                                    </a>

                                    <?php if ($is_cancellable): ?>
                                        <form method="post" id="cancelForm_<?= $ticket['ticket_id'] ?>">
                                            <input type="hidden" name="cancel_ticket" value="1">
                                            <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                                            <button type="button" class="btn btn-sm btn-danger" title="Bilet İptal Et"
                                                onclick="showConfirmModal(<?= $ticket['ticket_id'] ?>, '<?= number_format($ticket['final_price'], 2, '.', ',') ?>')">
                                                <i class="fas fa-times"></i> İptal Et
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Kalkışa 1 saatten az kaldı">
                                            İptal Süresi Doldu
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">Aktif biletiniz bulunmamaktadır.</div>
                    <?php endif; ?>
                </div>



                <h3 class="mt-5 mb-3 text-secondary">Geçmiş Seferleriniz</h3>
                <div class="card shadow-lg p-3 rounded-4">
                    <?php if (!empty($past_tickets)): ?>
                        <?php foreach ($past_tickets as $ticket): ?>
                            <div class="d-flex justify-content-between align-items-center p-3 mb-2 border-bottom text-muted ticket-item past-ticket">

                                <div class="ticket-details-group d-flex align-items-center">

                                    <?php if (!empty($ticket['logo_path'])): ?>
                                        <img src="<?= htmlspecialchars($ticket['logo_path']) ?>"
                                            alt="<?= htmlspecialchars($ticket['company_name']) ?> Logo"
                                            class="company-logo me-3">
                                    <?php else: ?>
                                        <i class="fas fa-bus-alt me-3 text-secondary" style="font-size: 2rem;"></i>
                                    <?php endif; ?>

                                    <div class="ticket-info">
                                        <div>
                                            <span class="badge bg-secondary me-2">GEÇMİŞ</span>
                                            <span><?= htmlspecialchars($ticket['departure_city_name']) ?> &rarr; <?= htmlspecialchars($ticket['destination_city_name']) ?></span>
                                        </div>
                                        <small class="text-muted d-block">Kalkış: <?= date('d.m.Y H:i', strtotime($ticket['departure_time'])) ?> | Koltuk: #<?= htmlspecialchars($ticket['seat_number']) ?></small>
                                    </div>
                                </div>
                                <div class="ticket-actions">
                                    <a href="generate_pdf.php?ticket_id=<?= (int)$ticket['ticket_id'] ?>" class="btn btn-sm btn-info text-white" title="PDF İndir">
                                        <i class="fas fa-file-pdf"></i> PDF İndir
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">Daha önce tamamlanmış biletiniz bulunmamaktadır.</div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Bilet İptalini Onayla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bilet iptalini onaylıyor musunuz?</p>
                    <p>Ödenen tutar olan <strong id="refundAmount"></strong> ₺ hesabınıza iade edilecektir.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">Evet, İptal Et</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCreditModal" tabindex="-1" aria-labelledby="addCreditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="addCreditModalLabel"><i class="fas fa-plus-circle me-1"></i> Bakiye Yükle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <p>Hesabınıza sanal kredi yüklemek için tutarı girin.</p>
                        <div class="mb-3">
                            <label for="credit_amount" class="form-label">Yüklenecek Tutar (₺)</label>
                            <input type="number" step="0.01" min="1" class="form-control" id="credit_amount" name="credit_amount" required placeholder="Örn: 50.00">
                        </div>
                        <div class="alert alert-warning small">
                            Bu bir simülasyon ödeme işlemidir. Gerçek bir kredi kartı işlemi yapılmayacaktır.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="add_credit" class="btn btn-info text-white">Yüklemeyi Tamamla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="footer text-center mt-5 p-3 text-muted border-top">
        <small>&copy; <?= date('Y') ?> TicketBox. Tüm hakları saklıdır.</small>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bilet iptal onay modalı fonksiyonu
        function showConfirmModal(ticketId, refundAmount) {
            document.getElementById('refundAmount').textContent = refundAmount;

            const confirmBtn = document.getElementById('confirmCancelBtn');
            confirmBtn.onclick = function() {
                const form = document.getElementById(`cancelForm_${ticketId}`);
                if (form) {
                    form.submit();
                }
            };

            const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
            cancelModal.show();
        }
    </script>
</body>

</html>