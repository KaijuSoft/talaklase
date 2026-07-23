<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $analysis = talaApiEngine()->analyzeSchema();
    $health = $analysis['health'] ?? [];
    $checks = $health['checks'] ?? [];

    $connections = [];
    foreach ($checks as $check) {
        $name = strtolower((string) ($check['name'] ?? ''));
        if (str_contains($name, 'source')) {
            $connections['source'] = $check['status'] ?? false;
        }
        if (str_contains($name, 'destination')) {
            $connections['destination'] = $check['status'] ?? false;
        }
    }

    $pendingOperations = count($analysis['execution_plan']['operations'] ?? []);

    talaApiResponse(
        [
            'status' => $health['status'] ?? false,
            'operation_count' => $pendingOperations,
            'duration_ms' => $analysis['duration_ms'] ?? 0,
            'source_connection' => $connections['source'] ?? false,
            'destination_connection' => $connections['destination'] ?? false,
        ],
        [
            // Legacy fields retained for dashboard compatibility.
            'source_connection' => $connections['source'] ?? false,
            'destination_connection' => $connections['destination'] ?? false,
            'engine_status' => $health['status'] ?? false,
            'last_synchronization' => null,
            'pending_operations' => $pendingOperations,
            'duration_ms' => $analysis['duration_ms'] ?? 0,
        ],
        [],
        [],
        [
            'endpoint' => 'status',
            'contract' => 'RC3.3.2.5',
        ]
    );
} catch (Throwable $exception) {
    talaApiError($exception);
}

