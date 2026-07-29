<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $startedAt = microtime(true);
    $stages = [];
    $stage = static function (string $name, callable $callback) use (&$stages): mixed {
        $started = microtime(true);
        try {
            $result = $callback();
            $stages[$name] = [
                'started' => true,
                'completed' => true,
                'duration_ms' => round((microtime(true) - $started) * 1000, 3),
                'success' => true,
                'warnings' => [],
                'failures' => [],
            ];
            return $result;
        } catch (Throwable $exception) {
            $stages[$name] = [
                'started' => true,
                'completed' => false,
                'duration_ms' => round((microtime(true) - $started) * 1000, 3),
                'success' => false,
                'warnings' => [],
                'failures' => [$exception->getMessage()],
            ];
            throw $exception;
        }
    };

    // Smart Sync direction is Online -> Local for both schema and data.
    $schemaEngine = talaApiEngine();
    $schemaAnalysis = $stage('analyze_schema', static fn (): array => $schemaEngine->analyzeSchema());
    $schemaExecution = $stage('apply_schema', static fn (): array => $schemaEngine->executePlan());

    // Data direction is online -> local because the reported discrepancy is
    // remote=273 and local=224. This is a separate engine stage and uses the
    // same generic data synchronization implementation.
    $online = getOnlineConnection();
    $local = getLocalConnection();
    $studentCount = static function (PDO $connection): int {
        return (int) $connection->query('SELECT COUNT(*) FROM `student`')->fetchColumn();
    };
    $dataAnalysis = $stage('analyze_data', static function () use ($studentCount, $online, $local): array {
        return [
        'remote_student_records' => $studentCount($online),
        'local_student_records' => $studentCount($local),
        ];
    });
    $remoteBefore = $dataAnalysis['remote_student_records'];
    $localBefore = $dataAnalysis['local_student_records'];

    $dataEngine = new \Tala\Engine\TalaEngine(
        $online,
        $local,
        require __DIR__ . '/../../../includes/TALA/TalaKlaseConfig.php'
    );
    $dataSession = $stage('synchronize_data', static fn (): \Tala\Engine\SyncSession => $dataEngine->syncDatabase());
    $localAfter = $studentCount($local);

    $studentReport = [
        'remote_records' => $remoteBefore,
        'local_before' => $localBefore,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
        'failure_reasons' => [],
        'local_after' => $localAfter,
        'operations' => [],
    ];
    foreach ($dataSession->tableResults() as $table) {
        if (($table['table'] ?? '') !== 'student') continue;
        $studentReport['inserted'] = (int) ($table['inserted'] ?? 0);
        $studentReport['updated'] = (int) ($table['modified'] ?? 0);
        $studentReport['skipped'] = (int) ($table['skipped'] ?? 0);
        $studentReport['failed'] = (int) ($table['failed'] ?? 0);
        $studentReport['operations'] = $table['operations'] ?? [];
        foreach ($studentReport['operations'] as $operation) {
            if (($operation['operation'] ?? '') === 'FAIL') {
                $studentReport['failure_reasons'][] = $operation['reason'] ?? 'Database exception';
            }
        }
    }

    $verification = $stage('verification', static function () use ($schemaEngine, $remoteBefore, $localAfter): array {
        $schemaVerification = $schemaEngine->analyzeSchema();
        return [
        'schema' => $schemaVerification,
        'remote_student_records' => $remoteBefore,
        'local_student_records' => $localAfter,
        ];
    });

    $execution = [
        'executed' => (int) ($schemaExecution['executed'] ?? 0),
        'skipped' => (int) ($schemaExecution['skipped'] ?? 0),
        'failed' => (int) ($schemaExecution['failed'] ?? 0),
        'operations' => $schemaExecution['operations'] ?? [],
    ];
    $remainingOperations = count($verification['schema']['execution_plan']['operations'] ?? []);
    $executed = $execution['executed'];
    $skipped = $execution['skipped'];
    $failed = $execution['failed'];

    talaApiResponse(
        [
            'execution' => [
                'executed' => $executed,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
            'operation_count' => count($execution['operations'] ?? []),
            'duration_ms' => $verification['duration_ms'] ?? 0,
        ],
        [
            'stages' => $stages,
            'schema' => [
                'tables_created' => array_values(array_filter($execution['operations'], static fn (array $operation): bool => ($operation['operation'] ?? '') === 'create_table')),
                'tables_altered' => array_values(array_filter($execution['operations'], static fn (array $operation): bool => in_array(($operation['operation'] ?? ''), ['add_column', 'modify_column', 'drop_column'], true))),
                'indexes' => [],
                'foreign_keys' => [],
            ],
            'data' => [
                'direction' => 'online_to_local',
                'students' => $studentReport,
                'session' => $dataSession->toArray(),
            ],
            // Legacy fields retained for dashboard compatibility.
            'executed' => $executed,
            'skipped' => $skipped,
            'failed' => $failed,
            'duration_ms' => $verification['duration_ms'] ?? 0,
            'remaining_operations' => $remainingOperations,
            'operations' => $execution['operations'] ?? [],
        ],
        [],
        [],
        [
            'endpoint' => 'execute',
            'contract' => 'RC3.3.2.5',
            'verification' => true,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 3),
        ]
    );
} catch (Throwable $exception) {
    talaApiError($exception);
}

