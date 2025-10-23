<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['company']);

$db = getdbConnection();

$company_id = $_SESSION['company_id'];

try {
    $stmt = $db->prepare("
        SELECT 
            t.id, 
            t.departure_time, 
            t.arrival_time, 
            t.price, 
            t.capacity,
            c_dep.name AS departure_city_name,
            c_dest.name AS destination_city_name
        FROM trips t
        JOIN cities c_dep ON t.departure_city = c_dep.id
        JOIN cities c_dest ON t.destination_city = c_dest.id
        WHERE t.company_id = ?
        ORDER BY t.departure_time DESC
    ");
    $stmt->execute([$company_id]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Veritabanı hatası: " . $e->getMessage();
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Seferlerim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <h2 class="mb-4">Seferlerim</h2>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Kalkış Şehri</th>
                    <th scope="col">Varış Şehri</th>
                    <th scope="col">Kalkış Tarihi</th>
                    <th scope="col">Varış Tarihi</th>
                    <th scope="col">Fiyat</th>
                    <th scope="col">Kapasite</th>
                    <th scope="col">Eylemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $trip): ?>
                    <tr>
                        <td><?= $trip['id'] ?></td>
                        <td><?= htmlspecialchars($trip['departure_city_name']) ?></td>
                        <td><?= htmlspecialchars($trip['destination_city_name']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($trip['departure_time'])) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($trip['arrival_time'])) ?></td>
                        <td><?= $trip['price'] ?>₺</td>
                        <td><?= $trip['capacity'] ?></td>
                        <td>
                            <a href="edit_trip.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-primary">Düzenle</a>
                            <button class="btn btn-danger btn-sm" onclick="deleteTrip(<?= $trip['id'] ?>)">Sil</button>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <script>
        function deleteTrip(tripId) {
            if (confirm("Bu seferi silmek istediğinizden emin misiniz?")) {
                fetch('delete_trip.php?id=' + tripId, {
                        method: 'GET'
                    })
                    .then(response => response.text())
                    .then(result => {
                        const status = result.trim();

                        if (status === 'success') {
                            alert('Sefer başarıyla silindi.');
                            window.location.reload();
                        } else if (status === 'notfound') {
                            alert('Hata: Sefer bulunamadı veya bu sefere silme yetkiniz yok.');
                        } else if (status === 'invalid') {
                            alert('Hata: Geçersiz Sefer ID.');
                        } else if (status === 'error') {
                            alert('Veritabanı hatası oluştu. Silme işlemi başarısız.');
                        } else {
                            alert('Beklenmeyen bir hata oluştu: ' + status);
                        }
                    })
                    .catch(error => {
                        console.error('İletişim hatası:', error);
                        alert('Silme işleminde bir ağ hatası oluştu.');
                    });
            }
        }
    </script>

</body>

</html>

</body>

</html>