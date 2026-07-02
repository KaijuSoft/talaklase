<?php

declare(strict_types=1);

session_start();

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

        sendEvent(

            "Inserted {$result['inserted']} | Skipped {$result['skipped']}",

            $table,

            $current,

            $total,

            'progress'

        );

    }

);

echo "<h2>TALA Engine Ready</h2>";

echo "<pre>";

print_r($engine->about());

echo "</pre>";

echo "<hr>";

echo "<h3>Synchronization Order</h3>";

echo "<pre>";

print_r($engine->getSyncOrder());

echo "</pre>";

echo "<hr>";
echo "<h2>Starting Engine Session...</h2>";

$session = $engine->syncDatabase();

echo "<h2>Synchronization Finished</h2>";

echo "<pre>";

echo $session->summary();

echo "</pre>";