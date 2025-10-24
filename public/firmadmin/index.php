<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/config.php';

requireRole(['company']);

$db = getdbConnection();

if (!isLoggedIn() || $_SESSION['user_role'] !== 'company') {
    header('Location: ../login.php');
    exit;
}

$company_id = $_SESSION['company_id'];

$company_name = 'Firma Admin Paneli';
try {
    $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $result = $stmt->fetchColumn();
    if ($result) {
        $company_name = htmlspecialchars($result);
    }
} catch (PDOException $e) {
    error_log("Firma adı çekme hatası: " . $e->getMessage());
}
$company_logo = '';
try {
    $stmt = $db->prepare("SELECT logo_path FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $result = $stmt->fetchColumn();
    if ($result) {
        $company_logo = htmlspecialchars($result);
    }
} catch (PDOException $e) {
    error_log("Firma logo çekme hatası: " . $e->getMessage());
}

$stmt = $db->prepare("SELECT * FROM trips WHERE company_id = ?");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Paneli - <?= $company_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background-color: #071F4EFF;
            color: white;
            padding: 20px 10px;
            position: fixed;
            top: 0;
            left: 0;
        }

        .sidebar ul {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 10px 8px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .sidebar ul li a:hover {
            background-color: #FFFFFF;
            color: #000000;
        }

        .sidebar ul li a i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .main-content {
            margin-left: 240px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: #071F4EFF;
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .header a {
            color: white;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .footer-firmadmin {
            background-color: #071F4EFF;
            color: white;
            text-align: center;
            padding: 10px;
        }

        .fw-header {
            font-size: 1.1rem;
            color: #4CAF50;
            text-align: center;
        }

        .image {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
            width: 50%;
            max-width: 90px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <p class="text-center fw-bold border-bottom pb-3">
            <span class="fs-5" style="color: #4CAF50;">TICKETBOX</span>
            <span class="fs-5 fw-normal" style="color: #4CAF50;">Firma Admin Paneli</span><br><br><br>
        </p>

        <ul>
            <li><a href="../../public/index.php" class="border-bottom pb-3"><i class="fa-solid fa-house"></i> Ana Sayfa</a></li>
            <li><a href="trips.php" class="border-bottom pb-3"><i class="fa-solid fa-bus"></i> Seferlerim</a></li>
            <li><a href="add_trip.php" class="border-bottom pb-3"><i class="fa-solid fa-plus"></i> Sefer Ekle</a></li>
            <li><a href="coupons.php" class="border-bottom pb-3"><i class="fa-solid fa-ticket"></i> Kuponlarım</a></li>
        </ul>
    </div>


    <div class="main-content">
        <div class="header">
            <h4 class="fw-header fw-bold m-0" style="width: 90%">
                <?= $company_name ?> Firma Admin Paneli
            </h4>

            <a href=" ../logout.php" class="btn btn-outline-light">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </div>

        <div class="content" id="content-area">
            <div class="alert alert-success text-center" role="alert">
                <h4 class="alert-heading">Hoş Geldiniz, <?= $company_name ?> Yönetim Paneli!</h4>
                <p>Sol menüden seferlerinizi ve kuponlarınızı yönetebilirsiniz.</p>
                <i><img class="image" src="<?= htmlspecialchars($company_logo) ?>" class="img-fluid" alt="<?= $company_name ?> logosu"></i>

            </div>

        </div>

        <div class="footer-firmadmin">
            &copy; TicketBox. 2025 Tüm hakları saklıdır.
        </div>
    </div>

</body>

</html>
<script>
    // deleteTrip fonksiyonunu burada tutmaya devam edebilirsiniz.
    function deleteTrip(tripId) {
        if (!confirm('Seferi silmek istediğinizden emin misiniz?')) return;
        window.location.href = 'delete_trip.php?id=' + encodeURIComponent(tripId);
    }

    // Firma Admin paneli genellikle tüm sayfaları yeniden yüklediği için AJAX kodu kaldırılmıştır.
    // Eğer index.php'den sonraki sayfaları AJAX ile yüklemek istiyorsanız, o JS kodu coupons.php, trips.php gibi sayfalarda olmalıdır.
</script>