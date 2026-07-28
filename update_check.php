<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_permission('manage_updates');
require_once __DIR__ . '/ReleaseChecker.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data): never
{
    echo json_encode($data);
    exit;
}

function releaseErrorType(RuntimeException $error): string
{
    $message = strtolower($error->getMessage());

    return match (true) {
        str_contains($message, 'url is invalid') => 'configuration_error',
        str_contains($message, 'no published github release') => 'release_not_found',
        str_contains($message, 'unable to download') => 'network_error',
        str_contains($message, 'release manifest') => 'invalid_manifest',
        default => 'unknown',
    };
}

$manifestUrl = getenv('TALAKLASE_RELEASE_MANIFEST_URL')
    ?: 'https://github.com/KaijuSoft/TalaKlase/releases/latest/download/manifest.json';

try {
    $release = (new ReleaseChecker(__DIR__ . '/version.json', $manifestUrl))->check();

    respond($release + [
        // Retain legacy fields for existing update-banner integrations.
        'branch' => 'release',
        'local_hash' => $release['current_version'],
        'remote_hash' => $release['latest_version'],
        'dirty' => false,
        'dirty_message' => '',
    ]);
} catch (RuntimeException $error) {
    respond([
        'status' => 'error',
        'error_type' => releaseErrorType($error),
        'message' => $error->getMessage(),
        'update_available' => false,
    ]);
}
