<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['company']);

$db = getdbConnection();
$company_id = $_SESSION['company_id'] ?? null;

$cities = getCities();

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    header('Location: trips.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $company_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    echo "Sefer bulunamadı veya yetkiniz yok.";
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = $_POST['departure_city'];
    $destination_city = $_POST['destination_city'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];

    $stmt = $db->prepare("UPDATE trips SET departure_city=?, destination_city=?, departure_time=?, arrival_time=?, price=?, capacity=? WHERE id=? AND company_id=?");
    $stmt->execute([$departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity, $trip_id, $company_id]);

    $message = "Sefer başarıyla güncellendi!";
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Sefer Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h2>Sefer Düzenle</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <form method="post" class="mt-4">
            <div class="mb-3">
                <label for="departure_city" class="form-label">Kalkış Şehri</label>
                <select name="departure_city" id="departure_city" class="form-select" required>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= $city['id'] ?>" <?= ($trip['departure_city'] == $city['id']) ? 'selected' : '' ?>><?= htmlspecialchars($city['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="destination_city" class="form-label">Varış Şehri</label>
                <select name="destination_city" id="destination_city" class="form-select" required>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= $city['id'] ?>" <?= ($trip['destination_city'] == $city['id']) ? 'selected' : '' ?>><?= htmlspecialchars($city['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="departure_time" class="form-label">Kalkış Tarihi & Saati</label>
                <input type="datetime-local" name="departure_time" id="departure_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>" required>
            </div>

            <div class="mb-3">
                <label for="arrival_time" class="form-label">Varış Tarihi & Saati</label>
                <input type="datetime-local" name="arrival_time" id="arrival_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($trip['arrival_time'])) ?>" required>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Fiyat (₺)</label>
                <input type="number" name="price" id="price" class="form-control" value="<?= $trip['price'] ?>" min="0" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="capacity" class="form-label">Kapasite</label>
                <input type="number" name="capacity" id="capacity" class="form-control" value="<?= $trip['capacity'] ?>" min="1" required>
            </div>

            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="trips.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
</body>

</html>