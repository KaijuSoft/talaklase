<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/TALA/bootstrap.php';
require_once __DIR__ . '/includes/db.php';

$config = require __DIR__ . '/includes/TALA/TalaKlaseConfig.php';

use Tala\Engine\TalaEngine;

$source = getLocalConnection();
$destination = getOnlineConnection();

$engine = new TalaEngine(
    $source,
    $destination,
    $config
);

$result = $engine->analyzeSchema();

echo "<pre>";

echo "========== RC2 PIPELINE ==========\n\n";

echo "Health Check: ";
echo ($result['health']['status'] ?? false) ? "PASS\n" : "FAIL\n";

echo "\nInspection\n";
print_r($result['inspection']);

echo "\nMerge Plan\n";
print_r($result['merge_plan']);

echo "\nValidation\n";
print_r($result['validation']);

echo "\nExecution\n";
print_r($result['execution']);

echo "</pre>";