<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/TALA/bootstrap.php';
require_once __DIR__ . '/includes/db.php';   // <-- use your actual DB connection file if different

$config = require __DIR__ . '/includes/TALA/TalaKlaseConfig.php';

$source = getLocalConnection();
$destination = getOnlineConnection();

$engine = new \Tala\Engine\TalaEngine(
    $source,
    $destination,
    $config
);

echo "<h2>TALA Engine Instance Created Successfully</h2>";
echo "<pre>";
print_r($engine->about());
echo "<hr>";
echo "<h3>Resolved Synchronization Order</h3>";
echo "<pre>";
print_r($engine->getSyncOrder());
echo "</pre>";