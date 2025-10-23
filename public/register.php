<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// if (isLoggedIn()) {
//     header('Location: index.php');
//     exit();
// }


$message = '';
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullName) || empty($email) || empty($password)) {
        $message = 'Lütfen tüm alanları doldurunuz.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'E-posta adresiniz geçerli değil.LÜtfen geçerli bir e-posta adresi giriniz.';
    } elseif (strlen($password) < 6) {
        $message = 'Şifreniz en az 6 karakter uzunluğunda olmalıdır.';
    } elseif (userExists($email)) {
        $message = 'Bu e-posta adresi zaten kayıtlı. Lütfen başka bir e-posta adresi kullanın.';
    } elseif (registerUser($fullName, $email, $password)) {
        header('Location: login.php?success=register');
        exit;
    } else {
        $message = 'Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.';
    }
}

$fullName = htmlspecialchars($fullName);
$email = htmlspecialchars($email);

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
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
                        <h3>Kayıt Ol</h3>
                    </div>

                    <div class="card-body p-4">
                        <form action="register.php" method="post">

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Ad Soyad:</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Ad Soyad" required value="" value="<?= $fullName ?>>
                            </div>

                            <div class=" mb-3">
                                <label for="email" class="form-label">E-posta:</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="E-posta" required value="" value="<?= $email ?>">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Şifre:</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                            </div>

                            <div class="text-center mb-3"> <button type="submit" class="btn-outline-success btn-lg">Kayıt Ol</button>
                            </div>

                            <p class="text-center mt-3 mb-2">
                                <a href="login.php" class="text-decoration-none">Zaten hesabınız var mı? Giriş yapın.</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>