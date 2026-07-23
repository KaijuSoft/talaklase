<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $engine = talaApiEngine();
    $engine->analyzeSchema();
    $execution = $engine->executePlan();

    // Verification step: re-run analysis after execution so the dashboard
    // can report whether any operations remain in the current plan.
    $verification = $engine->analyzeSchema();
    $remainingOperations = count($verification['execution_plan']['operations'] ?? []);
    $executed = (int) ($execution['executed'] ?? 0);
    $skipped = (int) ($execution['skipped'] ?? 0);
    $failed = (int) ($execution['failed'] ?? 0);

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
        ]
    );
} catch (Throwable $exception) {
    talaApiError($exception);
}

