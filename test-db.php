<?php

$host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
$port = 4000;
$db   = "test";
$user = "43PYUCXNx91RQ8v.root";
$pass = "kxfO6GgjbTi7fbbH"; // Use a new password after resetting it

$ca = __DIR__ . "/includes/cert/isrgrootx1.pem"; // Download this from TiDB

$options = [
    PDO::MYSQL_ATTR_SSL_CA => $ca,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        $options
    );

    echo "Connected!";
} catch (PDOException $e) {
    die($e->getMessage());
}