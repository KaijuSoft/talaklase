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

echo '<pre>';
print_r($engine->healthCheck());
echo '</pre>';