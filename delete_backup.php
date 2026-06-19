<?php

require_once 'includes/auth.php';

require_permission('sync_settings');

$file = basename($_GET['file'] ?? '');

$path = __DIR__ . '/storage/backups/' . $file;

if (is_file($path)) {
    unlink($path);
}

header('Location: index.php?page=db_backup');
exit;