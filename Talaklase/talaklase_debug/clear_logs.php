<?php
// clear_logs.php
require_once __DIR__ . '/includes/auth.php';
require_permission('manage_updates');
header('Content-Type: application/json');

$log_file = __DIR__ . '/logs/update_log.txt';

if (file_exists($log_file)) {
    file_put_contents($log_file, '');
}

echo json_encode(['status' => 'ok']);
