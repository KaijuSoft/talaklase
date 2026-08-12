<?php

require_once 'includes/auth.php';

require_permission('sync_settings');

$file = basename($_GET['file'] ?? '');

$path = __DIR__ . '/storage/backups/' . $file;

if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit('Backup not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;