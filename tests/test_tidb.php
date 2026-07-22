<?php

$config = require __DIR__ . '/includes/config/database.php';

$cfg = $config['online'];

$dsn = sprintf(
    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
    $cfg['host'],
    $cfg['port'],
    $cfg['database'],
    $cfg['charset']
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

if (!empty($cfg['ssl']) && !empty($cfg['ssl_ca'])) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $cfg['ssl_ca'];
}

try {

    $pdo = new PDO(
        $dsn,
        $cfg['username'],
        $cfg['password'],
        $options
    );

    echo "<h2>✅ Connected to TiDB Cloud!</h2>";

    echo "<pre>";

    echo "Server Version : " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;

    echo "Driver         : " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;

    echo "Database       : " . $cfg['database'] . PHP_EOL;

    echo "</pre>";

    $stmt = $pdo->query("SHOW TABLES");

    echo "<h3>Tables</h3>";

    echo "<pre>";

    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

    echo "</pre>";

} catch (Throwable $e) {

    echo "<pre>";

    echo $e->getMessage();

    echo "</pre>";

}