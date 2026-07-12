<?php
// TalaKlase - Database Connection
// Tries online DB first, falls back to local

function getConnection(): PDO {
    $onlineDSN = "mysql:host=sql12.freesqldatabase.com;port=3306;dbname=sql12817970;charset=utf8";
    $onlineUser = "sql12817970";
    $onlinePass = "N9dIfCwPRj";

    $localDSN = "mysql:host=127.0.0.1;port=3306;dbname=talaklasedb;charset=utf8";
    $localUser = "root";
    $localPass = "";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
        $pdo = new PDO($onlineDSN, $onlineUser, $onlinePass, $options);
        $_SESSION['db_source'] = 'Online Database';
        return $pdo;
    } catch (PDOException $e) {
        try {
            $pdo = new PDO($localDSN, $localUser, $localPass, $options);
            $_SESSION['db_source'] = 'Local Database';
            return $pdo;
        } catch (PDOException $e2) {
            die(json_encode(['error' => 'Both databases unavailable: ' . $e2->getMessage()]));
        }
    }
}


function getLocalConnection(): PDO
{
    $dsn = "mysql:host=127.0.0.1;port=3306;dbname=talaklasedb;charset=utf8";

    return new PDO(
        $dsn,
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function getOnlineConnection(): PDO
{
    $dsn = "mysql:host=sql12.freesqldatabase.com;port=3306;dbname=sql12817970;charset=utf8";

    return new PDO(
        $dsn,
        "sql12817970",
        "N9dIfCwPRj",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

