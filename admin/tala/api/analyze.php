<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $analysis = talaApiEngine()->analyzeSchema();
    $plan = $analysis['execution_plan'] ?? [];

    talaApiResponse([
        'summary' => [
            'operations_found' => count($plan['operations'] ?? []),
            'duration_ms' => $analysis['duration_ms'] ?? 0,
        ],
        'operations' => $plan['operations'] ?? [],
        'errors' => $analysis['validation']['errors'] ?? [],
    ]);
} catch (Throwable $exception) {
    talaApiError($exception);
}

