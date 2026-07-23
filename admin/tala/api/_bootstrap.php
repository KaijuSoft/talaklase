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

function talaApiResponse(array $data): never
{
    echo json_encode([
        'success' => true,
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

function talaApiError(Throwable $exception, int $status = 500): never
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
        'errors' => [],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

