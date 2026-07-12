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

            $start = microtime(true);

            $results[] = [
                'action'      => $operation['action'] ?? 'unknown',
                'table'       => $operation['table'] ?? null,
                'column'      => $operation['column'] ?? null,
                'index'       => $operation['index'] ?? null,

                'status'      => 'skipped',
                'reason'      => 'Execution not implemented (RC2.9.1)',

                'started_at'  => date('Y-m-d H:i:s'),
                'duration_ms' => round((microtime(true) - $start) * 1000, 3)
            ];

            $skipped++;
        }

        return [

            'status' => true,

            'executed' => $executed,

            'skipped' => $skipped,

            'failed' => $failed,

            'operations' => $results
        ];
    }
}