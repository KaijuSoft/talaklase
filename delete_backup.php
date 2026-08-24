<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_permission('sync_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(403);
    exit('Invalid request.');
}

$file = basename((string)($_POST['file'] ?? ''));
$path = __DIR__ . '/storage/backups/' . $file;
if ($file !== '' && is_file($path)) unlink($path);

header('Location: index.php?page=db_backup');
exit;