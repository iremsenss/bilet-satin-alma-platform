<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $isError = true;
        $message = 'Lütfen e-posta adresinizi giriniz.';
    } elseif (userExists($email)) {
        $message = 'Şifre sıfırlama bağlantısı e-posta adresinize gönderilmiştir (Simülasyon).';
        $isError = false;
    } else {
        $isError = true;
        $message = 'Bu e-posta adresi sistemimizde kayıtlı değildir.';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Şifremi Unuttum</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mx-auto">

                <?php if ($message) { ?>
                    <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php } ?>

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header text-center bg-gray text-success">
                        <h3>Şifre Sıfırlama</h3>
                    </div>

                    <div class="card-body p-4">
                        <p class="text-muted text-center">Hesabınıza kayıtlı e-posta adresinizi giriniz.</p>
                        <form action="forgot_password.php" method="post">

                            <div class="mb-4">
                                <label for="email" class="form-label">E-posta Adresi:</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="E-posta" required>
                            </div>

                            <div class="text-center mb-3">
                                <button type="submit" class="btn btn-success btn-lg">Sıfırlama Bağlantısı Gönder</button>
                            </div>

                            <p class="text-center mt-3 mb-2">
                                <a href="login.php" class="text-decoration-none">Giriş sayfasına geri dön</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>