<?php

require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    logoutUser();
    header('Location: index.php');
    exit;
}

header('Location: login.php');
exit;

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çıkış Yap</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

    <div class="container">
        <div class="row">

            <div class="col-md-5 mx-auto">

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header text-center bg-gray text-success">
                        <h3>Çıkış Yap</h3>
                    </div>

                    <div class="card-body p-4">
                        <form action="logout.php" method="post">
                            <div class="text-center mb-3"> <button type="submit" class="btn-outline-success btn-lg">Çıkış Yap</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>