<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/TALA/src/SchemaExecutor.php';

$pdo = getConnection();

$executor = new \Tala\Engine\SchemaExecutor($pdo);

echo "<pre>";
print_r($executor->execute([
    'operations' => [
        [
            
    "action"=>"create_table",

    "table"=>"student",

    "status"=>"skipped",

    "reason"=>"Execution not implemented",

    "sql"=>null

        ]
    ]
]));
echo "</pre>";