<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    $role = getUserRole();

    if ($role === 'admin') {
        header('Location: admin/index.php');
        exit();
    } elseif ($role === 'company') {
        header('Location: firmadmin/index.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}



$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = 'Lütfen e-posta ve şifrenizi giriniz.';
    } elseif (loginUser($email, $password)) {
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: admin/index.php');
            exit();
        } elseif ($_SESSION['user_role'] === 'company') {
            header('Location: firmadmin/index.php');
            exit();
        } else {
            header('Location: index.php');
            exit();
        }
    } else {
        $message = 'E-posta veya şifre hatalı. Lütfen tekrar deneyiniz.';
    }
}

$email = htmlspecialchars($email);

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

    <div class="container">
        <div class="row">

            <div class="col-md-5 mx-auto">

                <?php if ($message) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php } ?>

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header text-center bg-gray text-success">
                        <h3>Giriş Yap</h3>
                    </div>

                    <div class="card-body p-4">
                        <form action="login.php" method="post">

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta:</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="E-posta" required value="">
                            </div>


                            <div class="mb-4">
                                <label for="password" class="form-label">Şifre:</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                            </div>

                            <div class="d-flex justify-content-end mb-1">
                                <a href="forgot_password.php" class="text-decoration-none small text-muted">Şifremi unuttum</a>
                            </div>

                            <div class="text-center mb-3">
                                <button type="submit" class="btn-outline-success btn-lg">Giriş Yap</button>
                            </div>

                            <p class="text-center mt-3 mb-2">
                                <a href="register.php" class="text-decoration-none ">Kayıt Ol</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>