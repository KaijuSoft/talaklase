<?php

require_once 'includes/TALA/bootstrap.php';
require_once 'includes/db.php';   // Source DB
require_once 'includes/db2.php';  // Destination DB (adjust if yours has a different name)

use Tala\Engine\TalaEngine;

// Use the same configuration you use for syncDatabase()
$tables = require 'includes/TALA/config/tables.php'; // adjust if needed

$engine = new TalaEngine(
    $sourceConnection,
    $destinationConnection,
    $tables
);

$result = $engine->analyzeSchema();

echo "<pre>";
print_r($result);
echo "</pre>";