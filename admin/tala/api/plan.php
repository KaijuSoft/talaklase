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
            'status' => $plan['status'] ?? false,
        ],
        [
            // Legacy fields retained for dashboard compatibility.
            'operations' => $plan['operations'] ?? [],
            'status' => $plan['status'] ?? false,
        ],
        [],
        [],
        [
            'endpoint' => 'plan',
            'contract' => 'RC3.3.2.5',
        ]
    );
} catch (Throwable $exception) {
    talaApiError($exception);
}

