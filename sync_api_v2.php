<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/auth.php';
require_permission('sync_settings');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { http_response_code(403); exit('Invalid request.'); }

require_once __DIR__ . '/includes/TALA/bootstrap.php';
require_once __DIR__ . '/includes/db.php';

use Tala\Engine\TalaEngine;

$config = require __DIR__ . '/includes/TALA/TalaKlaseConfig.php';
$source = getLocalConnection();
$destination = getOnlineConnection();

$engine = new TalaEngine(
    $source,
    $destination,
    $config
);

$engine->onProgress(

    function (

        string $table,

        array $result,

        int $current,

        int $total

    ) {

        echo "<p>";

        echo "{$current}/{$total} - {$table} : ";

        echo "Inserted {$result['inserted']} | ";

        echo "Skipped {$result['skipped']}";

        echo "</p>";

        flush();

    }

);

echo "<h2>Starting Engine Session...</h2>";

$session = $engine->syncDatabase();

echo "<h2>Synchronization Finished</h2>";

echo "<pre>";

echo $session->summary();

echo "</pre>";