<?php

require_once __DIR__ . '/includes/TALA/bootstrap.php';
require_once __DIR__ . '/includes/db.php';

use Tala\Engine\TalaEngine;
use Tala\Engine\SchemaInspector;
use Tala\Engine\SchemaMerger;
use Tala\Engine\MergeValidator;
use Tala\Engine\SchemaExecutor;

$inspector = new SchemaInspector(
    getLocalConnection(),
    getOnlineConnection()
);

$inspection = [

    'status' => false,

    'tables' => [

        $inspector->compareTable('tala_executor_test')

    ]

];

$merger = new SchemaMerger();

$plan = $merger->buildPlan($inspection);

$validator = new MergeValidator();

$validation = $validator->validate($plan);

if (!$validation['valid']) {

    die("Validation failed");

}

$executor = new SchemaExecutor(
    getLocalConnection(),
    getOnlineConnection()
);

$result = $executor->execute($plan);

echo "<pre>";
print_r($result);
echo "</pre>";

