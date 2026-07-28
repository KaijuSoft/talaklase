<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getOnlineConnection();
    $config = require __DIR__ . '/../includes/config.php';
    $cfg = $config['online'];

    echo "<h2>âœ… Connected to configured online database!</h2>";
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
