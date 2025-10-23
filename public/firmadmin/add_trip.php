<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['company']);

$db = getdbConnection();
date_default_timezone_set('Europe/Istanbul');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("ADD_TRIP POST GELDI: headers=" . json_encode(getallheaders()));
    error_log("ADD_TRIP POST _POST=" . print_r($_POST, true));
}

$response = ['success' => false, 'message' => 'Bilinmeyen bir hata oluştu.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $departure_city_id = filter_var($_POST['departure_city'] ?? 0, FILTER_VALIDATE_INT);
    $destination_city_id = filter_var($_POST['destination_city'] ?? 0, FILTER_VALIDATE_INT);

    $departure_time_str = str_replace('T', ' ', $_POST['departure_time'] ?? '');
    $arrival_time_str = str_replace('T', ' ', $_POST['arrival_time'] ?? '');

    $departure_timestamp = strtotime($departure_time_str);
    $arrival_timestamp = strtotime($arrival_time_str);

    $departure_time = $departure_timestamp ? date('Y-m-d H:i:s', $departure_timestamp) : false;
    $arrival_time = $arrival_timestamp ? date('Y-m-d H:i:s', $arrival_timestamp) : false;

    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $capacity = filter_var($_POST['capacity'] ?? 0, FILTER_VALIDATE_INT);
    $company_id = $_SESSION['company_id'];

    if ($departure_city_id <= 0 || $destination_city_id <= 0) {
        $response['message'] = 'Kalkış ve Varış şehirlerini seçmelisiniz.';
    } elseif (!$departure_time || !$arrival_time) {
        $response['message'] = 'Lütfen geçerli bir kalkış ve varış tarih/saati girin.';
    } elseif ($price <= 0 || $capacity <= 0) {
        $response['message'] = 'Fiyat ve Kapasite 1\'den büyük pozitif bir sayı olmalıdır.';
    } elseif ($departure_timestamp >= $arrival_timestamp) {
        $response['message'] = 'Kalkış saati (' . date('Y-m-d H:i', $departure_timestamp) . ') Varış saatinden (' . date('Y-m-d H:i', $arrival_timestamp) . ') önce olmalıdır.';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO trips (company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $company_id,
                $departure_city_id,
                $destination_city_id,
                $departure_time,
                $arrival_time,
                $price,
                $capacity
            ]);

            $response = ['success' => true, 'message' => 'Sefer başarıyla kaydedildi!'];
        } catch (PDOException $e) {
            $response['message'] = 'Veritabanı hatası: Sefer eklenirken bir sorun oluştu.';
            error_log("Sefer Ekleme PDO Hatası: " . $e->getMessage());
        }
    }

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$cities = getCities();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Yeni Sefer Ekle | TicketBox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <h2 class="mb-4">Yeni Sefer Ekle</h2>

        <div id="response-message-area"></div>

        <form id="add-trip-form" action="" method="post" class="row g-3">

            <div class="col-md-6">
                <label for="departure_city" class="form-label">Kalkış Şehri</label>
                <select class="form-control" id="departure_city" name="departure_city" required>
                    <?php foreach ($cities as $city) {
                        echo "<option value=\"{$city['id']}\">" . htmlspecialchars($city['name']) . "</option>";
                    } ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="destination_city" class="form-label">Varış Şehri</label>
                <select class="form-control" id="destination_city" name="destination_city" required>
                    <?php foreach ($cities as $city) {
                        echo "<option value=\"{$city['id']}\">" . htmlspecialchars($city['name']) . "</option>";
                    } ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="departure_time" class="form-label">Kalkış Tarih/Saat</label>
                <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" required>
            </div>

            <div class="col-md-6">
                <label for="arrival_time" class="form-label">Varış Tarih/Saat</label>
                <input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" required>
            </div>

            <div class="col-md-4">
                <label for="price" class="form-label">Fiyat (₺)</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" required min="1">
            </div>

            <div class="col-md-4">
                <label for="capacity" class="form-label">Kapasite</label>
                <input type="number" class="form-control" id="capacity" name="capacity" required min="1">
            </div>

            <div class="col-md-4 d-grid align-items-end">
                <button type="submit" class="btn btn-success">Seferi Kaydet</button>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('add-trip-form');
            const messageArea = document.getElementById('response-message-area');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);

                const url = window.location.href;

                fetch(url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        redirect: 'follow',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(async res => {
                        if (!res.ok) {
                            const txt = await res.text();
                            throw new Error('Sunucu hata: ' + res.status + ' — ' + txt.substring(0, 500));
                        }
                        const ct = res.headers.get('Content-Type') || '';
                        if (!ct.includes('application/json')) {
                            const txt = await res.text();
                            throw new Error('Beklenen JSON gelmedi: ' + txt.substring(0, 500));
                        }
                        return res.json();
                    })
                    .then(data => {
                        const alertClass = data.success ? 'alert-success' : 'alert-danger';
                        messageArea.innerHTML = `<div class="alert ${alertClass} text-center fs-5 fw-bold" role="alert">
                ${data.success ? '✅' : '❌ '}${data.message}
            </div>`;
                        if (data.success) form.reset();
                    })
                    .catch(err => {
                        console.error('Form gönderme hatası:', err);
                        messageArea.innerHTML = `<div class="alert alert-danger">Sunucuyla iletişimde hata: ${err.message}</div>`;
                    });
            });
        });
    </script>
</body>

</html>