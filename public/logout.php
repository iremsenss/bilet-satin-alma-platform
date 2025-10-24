<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header('Location: index.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Çıkış Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modal-content {
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
        }

        .btn-lg-custom {
            min-width: 180px;
            padding: 0.8rem 1.5rem;
            font-size: 1.2rem;
        }
    </style>
</head>

<body class="bg-light">

    <div class="modal fade show" id="logoutModal" tabindex="-1" style="display:block;" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow-sm">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</h5>
                </div>
                <div class="modal-body">
                    <p>Hesabınızdan çıkış yapmak istediğinize emin misiniz?</p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="confirm" value="yes">
                        <button type="submit" class="btn btn-danger btn-lg btn-lg-custom">Evet, Çıkış Yap</button>
                    </form>
                    <a href="index.php" class="btn btn-secondary btn-lg btn-lg-custom">İptal</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js"></script>
</body>

</html>