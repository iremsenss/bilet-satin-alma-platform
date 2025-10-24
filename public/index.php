<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$is_logged_in = isLoggedIn();
$user_role = $_SESSION['user_role'] ?? null;
$full_name = $_SESSION['full_name'] ?? 'Kullanıcı';

$cities = getCities();

$departure = '';
$destination = '';
$date = date('Y-m-d');
$departure_name = '';
$destination_name = '';
$search_message = '';
$trips = [];
$show_results = false;
date_default_timezone_set('Europe/Istanbul');

if (!function_exists('calculateDuration')) {
    function calculateDuration($departureTime, $arrivalTime)
    {
        $start = new DateTime($departureTime);
        $end = new DateTime($arrivalTime);
        $interval = $start->diff($end);
        $hours = $interval->h + ($interval->days * 24);
        $minutes = $interval->i;

        $duration = [];
        if ($hours > 0) {
            $duration[] = "$hours saat";
        }
        if ($minutes > 0) {
            $duration[] = "$minutes dakika";
        }

        return empty($duration) ? "Belirtilmemiş" : implode(' ', $duration);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_tickets'])) {
    $departure = trim($_POST['departure_city'] ?? '');
    $destination = trim($_POST['destination_city'] ?? '');
    $date = trim($_POST['travel_date'] ?? '');
    $show_results = true;

    if (empty($departure) || empty($destination) || empty($date)) {
        $search_message = "Lütfen kalkış, varış ve tarihi seçiniz.";
        $trips = [];
    } else {
        $departure_name = getCityNameById($departure);
        $destination_name = getCityNameById($destination);

        date_default_timezone_set('Europe/Istanbul');

        $current_datetime = date('Y-m-d H:i:s');
        $date_filter = date('Y-m-d', strtotime($date));

        // Karşılaştırma Başlangıç Tarihi/Saati belirleniyor.
        // Eğer aranan tarih bugün ise, şimdiki saati kullan. Değilse, günün başlangıcını (00:00:00) kullan.
        $comparison_start_datetime = ($date_filter === date('Y-m-d')) ? $current_datetime : $date_filter . ' 00:00:00';


        $stmt = $db->prepare("
            SELECT 
                trips.id AS trip_id,
                trips.departure_time,
                trips.arrival_time,
                trips.price,
                trips.capacity,
                companies.name AS company_name,
                companies.logo_path,
                c_dep.name AS departure_city_name,
                c_dest.name AS destination_city_name
            FROM trips
            JOIN companies ON trips.company_id = companies.id
            JOIN cities c_dep ON trips.departure_city = c_dep.id
            JOIN cities c_dest ON trips.destination_city = c_dest.id
            WHERE trips.departure_city = :departure_city 
              AND trips.destination_city = :destination_city 
              -- MANTIK DÜZELTİLDİ: Sadece aranan güne ait seferler (DATE(trips.departure_time) = :travel_date)
              -- VEYA Sadece bugünden sonraki seferler (trips.departure_time >= :comparison_datetime)
              AND DATE(trips.departure_time) = :travel_date
              AND trips.departure_time >= :comparison_datetime
              
            ORDER BY trips.departure_time ASC
        ");

        $params = [
            ':departure_city' => $departure,
            ':destination_city' => $destination,
            ':travel_date' => $date_filter,
            ':comparison_datetime' => $comparison_start_datetime // Yeni parametre
        ];

        $stmt->execute($params);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_price = 0;
        foreach ($trips as $trip) {
            $total_price += $trip['price'];
        }

        $search_message = "<strong>$departure_name</strong>'dan <strong>$destination_name</strong>'a $date_filter tarihli seferler listeleniyor. <br><br>Seferlerin toplam tutarı: <strong>" . number_format($total_price, 2) . " ₺</strong>";
    }
}

$travel_date_value = isset($_POST['travel_date']) ? htmlspecialchars($_POST['travel_date']) : date('Y-m-d');

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Box - Bilet Satın Alma Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container-fluid ms-4 me-4">
            <a class="navbar-brand fw-bold" href="index.php">TicketBox</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav ms-auto d-flex align-items-center">

                    <?php if ($is_logged_in): ?>

                        <?php if ($user_role === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-warning fw-bold" href="admin/index.php">
                                    <i class="fas fa-tools"></i> Admin Paneli
                                </a>
                            </li>
                        <?php elseif ($user_role === 'company'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-info fw-bold" href="firmadmin/index.php">
                                    <i class="fas fa-bus"></i> Firma Paneli
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($user_role === 'user' || $user_role === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="user/my_account.php">
                                    <i class="fas fa-user-circle"></i> Hesabım / Biletlerim
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white" href="coupons.php">
                                    <i class="fas fa-tags"></i> Kampanyalarım
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- <li class="nav-item">
                            <span class="nav-link text-white-50 ">Hoş geldiniz, <?= htmlspecialchars($full_name) ?></span>
                        </li> -->

                        <li class="nav-item">
                            <a class="nav-link btn btn-sm  ms-2" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a>
                        </li>

                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </a>
                        </li>

                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">

        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-dark">Güvenilir ve Hızlı Bilet</h1>
            <p class="lead text-muted">Türkiye'nin her yerine en uygun biletleri anında bulun.</p>
        </div>
        <!-- Arama Kartı -->
        <div class="card shadow-lg p-4 rounded-4 mx-auto mb-5" style="max-width: 1500px;">
            <div class="card-body">

                <form action="index.php" method="post" class="row g-3 align-items-end">
                    <input type="hidden" name="search_tickets" value="1">

                    <div class="col-md-3 col-sm-12">
                        <label for="departure_city" class="form-label fw-bold">
                            <i class="fas fa-bus-alt text-success"></i> Kalkış Şehri
                        </label>
                        <select class="form-select form-select-lg" id="departure_city" name="departure_city" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city['id']) ?>" <?= ($departure == $city['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($city['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1 col-sm-12 d-flex align-items-end justify-content-center">
                        <button id="swapCitiesBtn" type="button" class="btn btn-success"
                            title="Kalkış ve Varış Şehirlerini Değiştir" style="height: 48px;">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <label for="destination_city" class="form-label fw-bold">
                            <i class="fas fa-map-marker-alt text-danger"></i> Varış Şehri
                        </label>
                        <select class="form-select form-select-lg" id="destination_city" name="destination_city" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city['id']) ?>" <?= ($destination == $city['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($city['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6"> <label for="travel_date" class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary"></i> Tarih</label>
                        <input type="date" class="form-control form-control-lg" id="travel_date" name="travel_date" required min="<?= date('Y-m-d') ?>" value="<?= $travel_date_value ?>">
                    </div>

                    <div class="col-md-2 col-sm-6 d-grid">
                        <button type="submit" class="btn btn-success btn-lg" title="Bilet Ara" style="height: 48px; margin-top: 29px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- Dönen Sonuc  -->
        <?php if ($show_results): ?>
            <div class="mt-5">
                <?php if (!empty($trips)): ?>
                    <h5 class="text-success text-center mb-4 text-uppercase  text-decoration-underline">UYGUN SEFERLER</h5>
                    <!-- (<?= count($trips) ?> Adet) -->
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <?php foreach ($trips as $trip): ?>
                                <?php
                                $stmt_seats = $db->prepare("
                                        SELECT COUNT(bs.seat_number) 
                                        FROM booked_seats bs
                                        JOIN tickets t ON bs.ticket_id = t.id
                                        WHERE t.trip_id = ?
                                    ");
                                $stmt_seats->execute([$trip['trip_id']]);
                                $booked_count = $stmt_seats->fetchColumn();
                                $available_seats = $trip['capacity'] - $booked_count;

                                $duration = calculateDuration($trip['departure_time'], $trip['arrival_time']);

                                $buy_link = $is_logged_in ? "book.php?trip_id=" . $trip['trip_id'] : "#";
                                $buy_class = $is_logged_in ? "btn-success" : "btn-warning btn-sm btn-buy-button-loggedout";
                                $buy_text = $is_logged_in ? "Seç" : "Giriş Yapın";
                                $buy_icon = $is_logged_in ? "fa-regular fa-circle-check" : "fa-solid fa-user-lock";
                                $buy_title = $is_logged_in ? "Bilet satın almak için tıklayın" : "Bilet almak için giriş yapmalısınız";
                                ?>

                                <div class="card shadow-sm border-0 mb-4 trip-card">
                                    <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between p-3 p-md-4">

                                        <div class="d-flex align-items-center mb-3 mb-md-0 me-md-4 flex-shrink-0">
                                            <?php if (!empty($trip['logo_path'])): ?>
                                                <img src="<?= htmlspecialchars($trip['logo_path']) ?>"
                                                    alt="<?= htmlspecialchars($trip['company_name']) ?>"
                                                    class="company-logo"
                                                    onerror="this.onerror=null;this.src='https://placehold.co/100x40/198754/ffffff?text=<?= substr($trip['company_name'], 0, 1) ?>';">
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="m-0 text-dark ">
                                                <?= htmlspecialchars($trip['departure_city_name']) ?>
                                                <i class=" fas fa-arrow-right mx-1"></i>
                                                <?= htmlspecialchars($trip['destination_city_name']) ?>
                                            </p>
                                        </div>

                                        <div class="text-center mx-md-4 mb-3 mb-md-0">
                                            <div class="d-flex align-items-center">
                                                <span class="trip-time me-2"><?= date('H:i', strtotime($trip['departure_time'])) ?></span>
                                                <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                                <span class="trip-time text-muted"><?= date('H:i', strtotime($trip['arrival_time'])) ?></span>
                                            </div>
                                            <div class="trip-duration text-nowrap">
                                                <i class="far fa-clock"></i> Süre: <?= $duration ?>
                                            </div>
                                        </div>

                                        <div class="text-center mx-md-4 mb-3 mb-md-0">
                                            <p class="m-0 text-muted">Boş Koltuk: <span class="fw-bold text-primary"><?= $available_seats ?> / <?= $trip['capacity'] ?></span></p>
                                            <p class="m-0 trip-price text-danger"><?= number_format($trip['price'], 2, ',', '.') ?> ₺</p>
                                        </div>

                                        <div class="flex-shrink-0 d-grid">
                                            <a href="<?= $buy_link ?>"
                                                class="btn <?= $buy_class ?> btn-lg"
                                                data-trip-id="<?= $trip['trip_id'] ?>"
                                                title="<?= $buy_title ?>"
                                                onclick="return checkLogin(<?= $is_logged_in ? 'true' : 'false' ?>, this);">
                                                <i class="<?= $buy_icon ?>"></i>
                                                <?= $buy_text ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning text-center mt-4 p-4 rounded-3 shadow-sm mx-auto" style="max-width: 600px; min-height: 100px;">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <br>
                        <strong>Üzgünüz!</strong> Aradığınız kriterlere uygun, gelecekteki saatlere ait sefer bulunamadı.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <div class="footer text-center mt-5 p-3 text-muted border-top">
        <small>&copy; <?= date('Y') ?> TicketBox. Tüm hakları saklıdır.</small>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const swapButton = document.getElementById('swapCitiesBtn');
            const departureSelect = document.getElementById('departure_city');
            const destinationSelect = document.getElementById('destination_city');


            swapButton.addEventListener('click', function() {
                const currentDepValue = departureSelect.value;
                const currentDestValue = destinationSelect.value;
                const currentDepIndex = departureSelect.selectedIndex;
                const currentDestIndex = destinationSelect.selectedIndex;


                departureSelect.value = currentDestValue;
                destinationSelect.value = currentDepValue;


                departureSelect.selectedIndex = currentDestIndex;
                destinationSelect.selectedIndex = currentDepIndex;


                departureSelect.dispatchEvent(new Event('change'));
                destinationSelect.dispatchEvent(new Event('change'));


            });
        });

        function checkLogin(isLoggedIn, button) {
            if (!isLoggedIn) {
                const confirmBox = document.createElement('div');
                confirmBox.className = 'custom-modal-overlay';
                confirmBox.innerHTML = `
                    <div class="custom-modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-warning"><i class="fas fa-exclamation-circle me-2"></i>Giriş Gerekli</h5>
                        </div>
                        <div class="modal-body">
                            Bilet satın alma işlemine devam edebilmek için lütfen giriş yapınız.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.querySelector('.custom-modal-overlay').remove()">İptal</button>
                            <a href="login.php" class="btn btn-success">Giriş Yap</a>
                        </div>
                    </div>
                `;
                document.body.appendChild(confirmBox);

                return false;
            }
            return true;
        }
    </script>

</body>

</html>