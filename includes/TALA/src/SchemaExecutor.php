<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;


final class SchemaExecutor
{
    
    private PDO $destination;

    /**
     * Constructor.
     */
    public function __construct(PDO $destination)
    {
        $this->destination = $destination;
    }

    /**
     * Execute a validated merge plan.
     *
     * RC2.9.1:
     * No SQL is executed.
     * All operations are returned as skipped.
     */
    public function execute(array $validatedPlan): array
    {
        $results = [];

        $executed = 0;
        $skipped  = 0;
        $failed   = 0;

        $operations = $validatedPlan['operations'] ?? [];

        foreach ($operations as $operation) {

          $result = $this->executeOperation($operation);

$results[] = $result;

switch ($result['status']) {
    case 'success':
        $executed++;
        break;

    case 'failed':
        $failed++;
        break;

    default:
        $skipped++;
        break;
}
		  
        }

        return [

            'status' => true,

            'executed' => $executed,

            'skipped' => $skipped,

            'failed' => $failed,

            'operations' => $results
        ];
    }


private function executeOperation(array $operation): array
{
    return match ($operation['action'] ?? '') {

        'create_table'
            => $this->executeCreateTable($operation),

        'add_column'
            => $this->executeAddColumn($operation),

        'create_index'
            => $this->executeCreateIndex($operation),

        default
            => $this->skipOperation($operation),
    };
}

private function executeCreateTable(array $operation): array
{
    return $this->skipOperation(
        $operation,
        'CREATE TABLE execution not implemented (RC2.9.2)'
    );
}

private function executeAddColumn(array $operation): array
{
    return $this->skipOperation(
        $operation,
        'ADD COLUMN execution not implemented (RC2.9.2)'
    );
}

private function executeCreateIndex(array $operation): array
{
    return $this->skipOperation(
        $operation,
        'CREATE INDEX execution not implemented (RC2.9.2)'
    );
}

private function skipOperation(
    array $operation,
    string $reason = 'Execution not implemented (RC2.9.2)'
): array {

    $start = microtime(true);

    return [

        'action' => $operation['action'] ?? 'unknown',

        'table' => $operation['table'] ?? null,

        'column' => $operation['column'] ?? null,

        'index' => $operation['index'] ?? null,

        'sql' => $operation['sql'] ?? null,

        'status' => 'skipped',

        'reason' => $reason,

        'error' => null,

        'executed' => false,

        'started_at' => date('Y-m-d H:i:s'),

        'duration_ms' => round((microtime(true) - $start) * 1000, 3)
    ];
}
}