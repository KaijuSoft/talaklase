<?php

require_once __DIR__ . '/includes/TALA/bootstrap.php';
require_once __DIR__ . '/includes/db.php';

use Tala\Engine\TalaEngine;

$tables = require __DIR__ . '/includes/TALA/TalaKlaseConfig.php';

$engine = new TalaEngine(
    getLocalConnection(),
    getOnlineConnection(),
    $tables
);

echo "<pre>";

$result = $engine->analyzeSchema();

print_r($result);

echo "</pre>";

$session->setInspection($inspection);

echo "<pre>";
print_r($inspection);
exit;