<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/TALA/bootstrap.php';

use Tala\Engine\TalaEngine;

function formatDefinition(array $definition): string
{
    $keys = [
        'type',
        'nullable',
        'default',
        'extra',
        'collation',
        'charset',
        'comment',
        'length',
        'octet_length',
        'precision',
        'scale',
        'datetime_precision',
        'ordinal_position',
    ];

    $parts = [];

    foreach ($keys as $key) {
        if (!array_key_exists($key, $definition)) {
            continue;
        }

        $value = $definition[$key];

        if ($value === null) {
            $value = 'null';
        } elseif ($value === '') {
            $value = '""';
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $parts[] = sprintf('%s=%s', $key, (string) $value);
    }

    return implode(', ', $parts);
}

function formatStatus(mixed $value): string
{
    if ($value === true) {
        return 'SUCCESS';
    }

    if ($value === false) {
        return 'FAILED';
    }

    $stringValue = strtoupper(trim((string) $value));

    return $stringValue === '' ? 'UNKNOWN' : $stringValue;
}

try {
    $sourcePDO = getLocalConnection();
    $destinationPDO = getOnlineConnection();
    $tables = require __DIR__ . '/../includes/TALA/TalaKlaseConfig.php';

    $engine = new TalaEngine($sourcePDO, $destinationPDO, $tables);

    $analysis = $engine->analyzeSchema();
    $executionPlan = $analysis['execution_plan'] ?? [];
    $analysisOperations = $executionPlan['operations'] ?? [];

    echo "========================================\n\n";
    echo "ANALYSIS\n\n";
    echo "========================================\n\n";
    echo "Returned Keys\n";
    echo implode(', ', array_keys($analysis)) . "\n\n";
    echo "Status\n";
    echo formatStatus($executionPlan['status'] ?? null) . "\n\n";
    echo "Operations\n";
    echo count($analysisOperations) . "\n\n";
    echo "----------------------------------------\n\n";

    foreach ($analysisOperations as $operation) {

    $details = $operation['details'] ?? [];

    echo "Operation\n";
    echo strtoupper($operation['operation'] ?? '-') . "\n\n";

    echo "Table\n";
    echo ($details['table'] ?? '-') . "\n\n";

    if (!empty($details['column'])) {
        echo "Column\n";
        echo $details['column'] . "\n\n";
    }

    if (!empty($details['source_definition'])) {
        echo "Source Definition\n";
        echo formatDefinition($details['source_definition']) . "\n\n";
    }

    if (!empty($details['destination_definition'])) {
        echo "Destination Definition\n";
        echo formatDefinition($details['destination_definition']) . "\n\n";
    }

    if (!empty($operation['sql'])) {
        echo "SQL\n";
        echo $operation['sql'] . "\n\n";
    }

    echo "----------------------------------------\n\n";
	

        $details = $operation['details'] ?? [];
        $sourceDefinition = is_array($details['source_definition'] ?? null) ? $details['source_definition'] : [];
        $destinationDefinition = is_array($details['destination_definition'] ?? null) ? $details['destination_definition'] : [];

        echo "MODIFIED COLUMN\n";
        echo "Table\n";
        echo ($details['table'] ?? '-') . "\n\n";
        echo "Column\n";
        echo ($details['column'] ?? '-') . "\n\n";
        echo "Source Definition\n";
        echo formatDefinition($sourceDefinition) . "\n\n";
        echo "Destination Definition\n";
        echo formatDefinition($destinationDefinition) . "\n\n";
        echo "SQL\n";
        echo ($operation['sql'] ?? '-') . "\n\n";
        echo "----------------------------------------\n\n";
    }

    $result = $engine->executePlan();
	$verification = $engine->analyzeSchema();

	$remaining =
    count($verification['execution_plan']['operations'] ?? []);

	echo "========================================\n";
	echo "POST EXECUTION VERIFICATION\n";
	echo "========================================\n";
	echo "Remaining Operations\n";
	echo $remaining . "\n";
    $resultOperations = $result['operations'] ?? [];

    echo "========================================\n\n";
    echo "EXECUTION\n\n";
    echo "========================================\n\n";
    echo "Executed\n";
    echo (string) ($result['executed'] ?? 0) . "\n\n";
    echo "Skipped\n";
    echo (string) ($result['skipped'] ?? 0) . "\n\n";
    echo "Failed\n";
    echo (string) ($result['failed'] ?? 0) . "\n\n";
    echo "Operation Count\n";
    echo count($resultOperations) . "\n\n";
    echo "----------------------------------------\n\n";

    foreach ($resultOperations as $operation) {
        echo "Operation Type\n";
        echo ($operation['operation'] ?? '-') . "\n";
        echo "Status\n";
        echo ($operation['status'] ?? '-') . "\n";

        if (!empty($operation['sql'])) {
            echo "SQL\n";
            echo $operation['sql'] . "\n";
        }

        if (!empty($operation['error'])) {
            echo "Error\n";
            echo $operation['error'] . "\n";
        }

        echo "----------------------------------------\n";
    }
} catch (Throwable $e) {
    echo "TEST FAILED\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
