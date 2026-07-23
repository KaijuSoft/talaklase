<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $analysis = talaApiEngine()->analyzeSchema();
    $plan = $analysis['execution_plan'] ?? [];

    talaApiResponse([
        'operations' => $plan['operations'] ?? [],
        'status' => $plan['status'] ?? false,
    ]);
} catch (Throwable $exception) {
    talaApiError($exception);
}

