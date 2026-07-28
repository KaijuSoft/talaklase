<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getOnlineConnection();
    echo "Connected!";
} catch (PDOException $e) {
    die($e->getMessage());
}
