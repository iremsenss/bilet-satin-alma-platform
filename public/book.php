<?php
// Oturum ve veritabanı ayarları
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    // Giriş yapmamışsa, giriş sayfasına yönlendir.
    header('Location: ../login.php');
    exit;
}

// URL'den sefer ID'sini (trip_id) al
$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id || !is_numeric($trip_id)) {
    // Geçersiz veya eksik ID ise ana sayfaya yönlendir.
    header('Location: ../index.php');
    exit;
}

// Sefer bilgilerini veritabanından çek
$stmt_trip = $db->prepare("SELECT * FROM trips WHERE id = ?");
$stmt_trip->execute([$trip_id]);
$trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    // Sefer bulunamazsa hata mesajı göster
    echo "Hata: Sefer bulunamadı.";
    exit;
}

// Dolu koltukları veritabanından çek
$stmt_booked = $db->prepare("
    SELECT bs.seat_number 
    FROM booked_seats bs
    JOIN tickets t ON bs.ticket_id = t.id
    WHERE t.trip_id = ?
");
$stmt_booked->execute([$trip_id]);
$booked_seats = $stmt_booked->fetchAll(PDO::FETCH_COLUMN);

// Kullanıcı bilgilerini ve bakiyesini al
$user_id = getCurrentUserId();
$user_balance = getUserBalance($user_id);
$initial_price = $trip['price'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container-fluid ms-4 me-4">
            <a class="navbar-brand fw-bold" href="index.php">TicketBox</a>
            <a class="nav-link btn btn-sm  ms-2" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="text-center mb-4 display-6 fw-bold text-dark">Bilet Satın Alma</h1>

                <div class="card shadow-lg p-4 rounded-4">
                    <h5 class="card-title text-center mb-4">
                        <strong><?= htmlspecialchars(getCityNameById($trip['departure_city'])) ?> &rarr; <?= htmlspecialchars(getCityNameById($trip['destination_city'])) ?></strong>
                        <br>
                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></small>
                    </h5>

                    <div id="alert-container"></div>

                    <form id="bookingForm" action="process_booking.php" method="POST">
                        <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
                        <input type="hidden" name="selected_seat" id="selected-seat-input">
                        <input type="hidden" name="coupon_id" id="coupon-id-input">


                        <div class="text-center mb-4">
                            <p class="mb-1">Lütfen koltuğunuzu seçin:</p>
                            <div class="seat-container">
                                <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                                    <?php $is_booked = in_array($i, $booked_seats); ?>
                                    <button type="button"
                                        class="seat-button <?= $is_booked ? 'seat-booked' : 'seat-available' ?>"
                                        data-seat-number="<?= $i ?>"
                                        <?= $is_booked ? 'disabled' : '' ?>>
                                        <?= $i ?>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted">Seçilen Koltuk: <span id="selected-seat-display" class="fw-bold text-primary">Yok</span></p>
                        </div>

                        <hr>

                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-md-6">
                                <label for="coupon_code" class="form-label">Kupon Kodu:</label>
                                <div class="input-group">
                                    <input type="text" id="coupon_code" name="coupon_code" class="form-control" placeholder="Kupon kodunu girin">
                                    <button type="button" id="apply-coupon-btn" class="btn btn-outline-secondary">Uygula</button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="mb-1">Bilet Fiyatı:</p>
                                <h3 class="trip-price" id="final-price"><?= number_format($initial_price, 2, ',', '.') ?> ₺</h3>
                                <input type="hidden" id="initial-price-input" value="<?= htmlspecialchars($initial_price) ?>">
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg" id="buy-button" disabled>
                                <i class="fas fa-ticket-alt"></i> Satın Al
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const seatButtons = document.querySelectorAll('.seat-button');
            const selectedSeatInput = document.getElementById('selected-seat-input');
            const selectedSeatDisplay = document.getElementById('selected-seat-display');
            const applyCouponBtn = document.getElementById('apply-coupon-btn');
            const couponCodeInput = document.getElementById('coupon_code');
            const finalPriceDisplay = document.getElementById('final-price');
            const buyButton = document.getElementById('buy-button');
            const initialPrice = parseFloat(document.getElementById('initial-price-input').value);
            const tripId = document.querySelector('input[name="trip_id"]').value; // hidden inputtan trip_id'yi al

            let currentPrice = initialPrice;
            let selectedSeat = null;

            seatButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (button.classList.contains('seat-booked')) {
                        return;
                    }
                    seatButtons.forEach(btn => btn.classList.remove('seat-selected'));
                    button.classList.add('seat-selected');
                    selectedSeat = button.getAttribute('data-seat-number');
                    selectedSeatInput.value = selectedSeat;
                    selectedSeatDisplay.textContent = selectedSeat;
                    buyButton.disabled = false;
                });
            });

            applyCouponBtn.addEventListener('click', () => {
                const couponCode = couponCodeInput.value.trim();
                if (couponCode === '') {
                    showAlert('Lütfen bir kupon kodu giriniz.', 'danger');
                    return;
                }

                fetch('user/apply_coupon.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `coupon_code=${encodeURIComponent(couponCode)}&trip_id=${encodeURIComponent(tripId)}`
                    })


                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentPrice = data.final_price;
                            finalPriceDisplay.textContent = currentPrice.toFixed(2).replace('.', ',') + ' ₺';
                            showAlert(data.message, 'success');
                            document.getElementById('coupon-id-input').value = data.coupon_id;

                        } else {
                            currentPrice = initialPrice;
                            finalPriceDisplay.textContent = currentPrice.toFixed(2).replace('.', ',') + ' ₺';
                            showAlert(data.message, 'danger');
                            document.getElementById('coupon-id-input').value = '';

                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        showAlert('Kupon kontrolü sırasında bir hata oluştu.', 'danger');
                    });
            });

            function showAlert(message, type) {
                const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
                document.getElementById('alert-container').innerHTML = alertHtml;
            }
        });
    </script>

</body>

</html>