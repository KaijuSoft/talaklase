<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $engine = talaApiEngine();
    $engine->analyzeSchema();
    $execution = $engine->executePlan();
    $verification = $engine->analyzeSchema();

    talaApiResponse([
        'executed' => $execution['executed'] ?? 0,
        'skipped' => $execution['skipped'] ?? 0,
        'failed' => $execution['failed'] ?? 0,
        'duration_ms' => $verification['duration_ms'] ?? 0,
        'remaining_operations' => count($verification['execution_plan']['operations'] ?? []),
        'operations' => $execution['operations'] ?? [],
    ]);
} catch (Throwable $exception) {
    talaApiError($exception);
}

