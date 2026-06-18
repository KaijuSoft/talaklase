<?php
// do_update.php
// Runs git pull safely and logs the result
<<<<<<< HEAD
require_once __DIR__ . '/includes/auth.php';
require_permission('manage_updates');
=======
>>>>>>> dad965eae0886277347cae4c6fc181143c8fa104

header('Content-Type: application/json');

$project_dir = __DIR__;
$logs_dir    = __DIR__ . '/logs';
$log_file    = $logs_dir . '/update_log.txt';

function respond(array $data): void {
    echo json_encode($data);
    exit;
}

function runGit(string $projectDir, string $command): string {
    $full = 'git -C ' . escapeshellarg($projectDir) . ' ' . $command . ' 2>&1';
    $output = shell_exec($full);
    return trim((string)$output);
}

function hasGitError(string $text): bool {
    $lower = strtolower($text);
    return $text === ''
        || strpos($lower, 'fatal') !== false
        || strpos($lower, 'error') !== false
        || strpos($lower, 'conflict') !== false
        || strpos($lower, 'not recognized') !== false
        || strpos($lower, 'not found') !== false;
}

if (!function_exists('shell_exec')) {
    respond([
        'status' => 'error',
        'message' => 'shell_exec is disabled on this PHP setup.'
    ]);
}

if (!is_dir($project_dir . DIRECTORY_SEPARATOR . '.git')) {
    respond([
        'status' => 'error',
        'message' => 'This TalaKlase folder is not a Git repository.'
    ]);
}

if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');

$git_version = runGit($project_dir, '--version');
if (hasGitError($git_version)) {
    respond([
        'status' => 'error',
        'message' => 'Git is not available on this machine.',
        'details' => $git_version
    ]);
}

$branch = runGit($project_dir, 'branch --show-current');
if (hasGitError($branch)) {
    respond([
        'status' => 'error',
        'message' => 'Unable to detect the current Git branch.',
        'details' => $branch
    ]);
}

$branchSafe = preg_replace('/[^A-Za-z0-9._\/-]/', '', $branch);
if ($branchSafe === '') {
    respond([
        'status' => 'error',
        'message' => 'Detected branch name is invalid.',
        'branch' => $branch
    ]);
}

// Stop if there are local edits
$dirty = runGit($project_dir, 'status --porcelain');
if (trim($dirty) !== '') {
    $log_entry  = "[$timestamp] [FAILED]\n";
    $log_entry .= "  Branch : {$branch}\n";
    $log_entry .= "  Reason : Local changes detected. Update aborted.\n";
    $log_entry .= "  Output : " . $dirty . "\n";
    $log_entry .= str_repeat('-', 60) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

    respond([
        'status' => 'error',
        'message' => 'Local changes detected. Commit, stash, or discard them before updating.',
        'branch' => $branch,
        'output' => $dirty
    ]);
}

$hash_before = runGit($project_dir, 'rev-parse HEAD');
$fetch_out   = runGit($project_dir, 'fetch origin');
$remote_hash = runGit($project_dir, 'rev-parse origin/' . $branchSafe);

if (hasGitError($hash_before) || hasGitError($remote_hash) || ($fetch_out !== '' && hasGitError($fetch_out))) {
    $log_entry  = "[$timestamp] [FAILED]\n";
    $log_entry .= "  Branch : {$branch}\n";
    $log_entry .= "  Before : {$hash_before}\n";
    $log_entry .= "  Remote : {$remote_hash}\n";
    $log_entry .= "  Output : " . ($fetch_out !== '' ? $fetch_out : 'Failed to fetch remote state') . "\n";
    $log_entry .= str_repeat('-', 60) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

    respond([
        'status' => 'error',
        'message' => 'Failed to fetch latest update information.',
        'branch' => $branch,
        'output' => $fetch_out
    ]);
}

if ($hash_before === $remote_hash) {
    $log_entry  = "[$timestamp] [SUCCESS]\n";
    $log_entry .= "  Branch : {$branch}\n";
    $log_entry .= "  Before : " . substr($hash_before, 0, 7) . "\n";
    $log_entry .= "  After  : " . substr($hash_before, 0, 7) . "\n";
    $log_entry .= "  Output : Already up to date.\n";
    $log_entry .= str_repeat('-', 60) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

    respond([
        'status' => 'success',
        'message' => 'TalaKlase is already up to date.',
        'branch' => $branch,
        'hash_before' => substr($hash_before, 0, 7),
        'hash_after' => substr($hash_before, 0, 7),
        'output' => 'Already up to date.'
    ]);
}

$pull_output = runGit($project_dir, 'pull origin ' . $branchSafe);
$hash_after  = runGit($project_dir, 'rev-parse HEAD');

$success = !hasGitError($pull_output) && !hasGitError($hash_after);

$statusLabel = $success ? 'SUCCESS' : 'FAILED';

$log_entry  = "[$timestamp] [{$statusLabel}]\n";
$log_entry .= "  Branch : {$branch}\n";
$log_entry .= "  Before : " . substr($hash_before, 0, 7) . "\n";
$log_entry .= "  After  : " . (hasGitError($hash_after) ? $hash_after : substr($hash_after, 0, 7)) . "\n";
$log_entry .= "  Output : " . $pull_output . "\n";
$log_entry .= str_repeat('-', 60) . "\n";

file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

respond([
    'status' => $success ? 'success' : 'error',
    'message' => $success ? 'Update applied successfully.' : 'Update failed. Check logs.',
    'branch' => $branch,
    'hash_before' => hasGitError($hash_before) ? $hash_before : substr($hash_before, 0, 7),
    'hash_after' => hasGitError($hash_after) ? $hash_after : substr($hash_after, 0, 7),
    'output' => $pull_output,
    'timestamp' => $timestamp
<<<<<<< HEAD
]);
=======
]);
>>>>>>> dad965eae0886277347cae4c6fc181143c8fa104
