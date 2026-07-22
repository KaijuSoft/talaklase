<?php

require_once __DIR__ . '/includes/TALA/bootstrap.php';
require_once __DIR__ . '/includes/db.php'; // Adjust if needed

use Tala\Engine\TalaEngine;

echo "=========================================\n";
echo "        TALA ENGINE RC3.1 TEST\n";
echo "=========================================\n\n";

$sourcePDO = getLocalConnection();
$destinationPDO = getOnlineConnection();
$tables = require __DIR__ . '/includes/TALA/TalaKlaseConfig.php';

$engine = new TalaEngine($sourcePDO, $destinationPDO, $tables);


echo "Source:      " . get_class($sourcePDO) . PHP_EOL;
echo "Destination: " . get_class($destinationPDO) . PHP_EOL;


echo "[1] ANALYZING SCHEMA...\n\n";

$analysis = $engine->analyzeSchema();

echo "Execution Plan:\n";
print_r($analysis['execution_plan']);

echo "\n=========================================\n";

echo "[2] EXECUTING PLAN...\n\n";

try {

    $result = $engine->executePlan();

    echo "Execution Result:\n";
    print_r($result);

} catch (Throwable $e) {

    echo "Execution Failed!\n";
    echo get_class($e) . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
}
