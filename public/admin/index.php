<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        .header {
            background-color: #071F4EFF;
            color: white;
            padding: 23px 20px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .sidebar {
            position: fixed;
            top: 35px;
            left: 0;
            width: 240px;
            height: 100vh;
            background-color: #071F4EFF;
            color: white;
            padding-top: 66px;
            padding-bottom: 20px;
            padding-left: 10px;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            margin-left: 240px;
            padding-top: 66px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar ul {
            list-style: none;
            padding-left: 0;
            margin-top: 20px;
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

        .content {
            flex-grow: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .footer-admin {
            background-color: #071F4EFF;
            border-style: solid;
            border-color: #000000FF;
            border-width: 2px;
            color: white;
            text-align: center;
            padding: 10px;
        }

        .sidebar p.pt-5 {
            padding-top: 0 !important;
        }
    </style>
</head>

<body>

    <div class="header">
        <h4 class="fw-bold m-0" style="color: #4CAF50;">
            <i class="fas fa-ticket me-2 colo"></i> TICKETBOX <span style="font-weight: normal; font-size: 0.8em;">ADMIN</span>
        </h4> <a href="../logout.php" class="btn btn-outline-light">
            <i class="fas fa-sign-out-alt"></i> Çıkış Yap
        </a>
    </div>

    <div class="sidebar">
        <p class="text-center fw-bold border-bottom pb-3 pt-1">Menü</p>
        <ul>
            <li><a href="/index.php" class="border-bottom pb-3"><i class="fa-solid fa-house "></i> Ana Sayfa</a></li>
            <li><a href="cities.php" class="border-bottom pb-3"><i class="fa-solid fa-city"></i> Şehir Yönetimi</a></li>
            <li><a href="firms.php" class="border-bottom pb-3"><i class="fa-solid fa-building"></i> Firma Yönetimi</a></li>
            <li><a href="firmadmins.php" class="border-bottom pb-3"><i class="fa-solid fa-user-tie"></i> Firma Admin Yönetimi</a></li>
            <li><a href="coupons.php"><i class="fa-solid fa-ticket-alt"></i> Kupon Yönetimi</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content mt-5" id="content-area">

        </div>

        <div class="footer-admin">
            &copy; 2025 TicketBox. Tüm hakları saklıdır.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // NOTE: AJAX yükleme mantığı çalıştırılmayacaktır (Çalışmama sorununuz nedeniyle kaldırılmıştı).
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', function(e) {

            });
        });
    </script>
</body>

</html>