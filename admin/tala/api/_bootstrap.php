<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/TALA/bootstrap.php';

use Tala\Engine\TalaEngine;

header('Content-Type: application/json; charset=utf-8');
send_no_cache_headers();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !can('sync_settings')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied.',
    ]);
    exit;
}

function talaApiEngine(): TalaEngine
{
    $config = require __DIR__ . '/../../../includes/TALA/TalaKlaseConfig.php';

    return new TalaEngine(
        getLocalConnection(),
        getOnlineConnection(),
        $config
    );
}

/**
 * Standard TALA API envelope.
 *
 * Legacy dashboard-facing fields may remain inside `data` during RC3.3.2.5
 * for backward compatibility, but new consumers should prefer `summary`,
 * `data`, `warnings`, `errors`, and `meta`.
 *
 * @param array<string, mixed> $summary
 * @param array<string, mixed> $data
 * @param array<int, string> $warnings
 * @param array<int, string> $errors
 * @param array<string, mixed> $meta
 */
function talaApiEnvelope(
    array $summary = [],
    array $data = [],
    array $warnings = [],
    array $errors = [],
    array $meta = []
): array {
    return [
        'success' => true,
        'summary' => $summary,
        'data' => $data,
        'warnings' => $warnings,
        'errors' => $errors,
        'meta' => $meta,
    ];
}

/**
 * @param array<string, mixed> $summary
 * @param array<string, mixed> $data
 * @param array<int, string> $warnings
 * @param array<int, string> $errors
 * @param array<string, mixed> $meta
 */
function talaApiResponse(
    array $summary = [],
    array $data = [],
    array $warnings = [],
    array $errors = [],
    array $meta = []
): never
{
    echo json_encode(
        talaApiEnvelope($summary, $data, $warnings, $errors, $meta),
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

function talaApiError(Throwable $exception, int $status = 500): never
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'summary' => [
            'status' => 'error',
        ],
        'data' => [],
        'warnings' => [],
        'errors' => [],
        'meta' => [
            'exception' => get_class($exception),
        ],
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

