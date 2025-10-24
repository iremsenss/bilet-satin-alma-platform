<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * @return PDO PDO bağlantı nesnesi
 */
function getdbConnection()
{
    $db_file = '/var/www/html/db/database.sqlite';

    $dsn = "sqlite:$db_file";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Kilitlenmede 5 saniye bekle
        PDO::ATTR_TIMEOUT => 5,
    ];

    try {
        $pdo = new PDO($dsn, null, null, $options);

        $pdo->exec("PRAGMA busy_timeout = 5000;");

        $pdo->exec("PRAGMA journal_mode = WAL;");

        return $pdo;
    } catch (PDOException $e) {
        error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
        die("Veritabanı bağlantı hatası: Veritabanı dosyası bulunamıyor veya kilitli.");
    }
}

$db = getdbConnection();
