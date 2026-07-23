<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $analysis = talaApiEngine()->analyzeSchema();
    $plan = $analysis['execution_plan'] ?? [];
    $operationCount = count($plan['operations'] ?? []);

    talaApiResponse(
        [
            'operation_count' => $operationCount,
            'duration_ms' => $analysis['duration_ms'] ?? 0,
            'status' => $plan['status'] ?? false,
        ],
        [
            // Legacy fields retained for dashboard compatibility.
            'summary' => [
                'operations_found' => $operationCount,
                'duration_ms' => $analysis['duration_ms'] ?? 0,
            ],
            'operations' => $plan['operations'] ?? [],
            'errors' => $analysis['validation']['errors'] ?? [],
        ],
        [],
        $analysis['validation']['errors'] ?? [],
        [
            'endpoint' => 'analyze',
            'contract' => 'RC3.3.2.5',
        ]
    );
} catch (Throwable $exception) {
    talaApiError($exception);
}

