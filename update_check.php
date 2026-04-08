<?php
// update_check.php
// Returns JSON: { status, message, branch, local_hash, remote_hash }

header('Content-Type: application/json');

$project_dir = __DIR__;

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

// Fetch latest refs
$fetch = runGit($project_dir, 'fetch origin');
if ($fetch !== '' && hasGitError($fetch)) {
    respond([
        'status' => 'error',
        'message' => 'Git fetch failed.',
        'branch' => $branch,
        'details' => $fetch
    ]);
}

$local = runGit($project_dir, 'rev-parse HEAD');
$remote = runGit($project_dir, 'rev-parse origin/' . $branchSafe);
$dirty = runGit($project_dir, 'status --porcelain');

if (hasGitError($local) || hasGitError($remote)) {
    respond([
        'status' => 'error',
        'message' => 'Unable to compare local and remote versions.',
        'branch' => $branch,
        'local_hash' => $local,
        'remote_hash' => $remote
    ]);
}

$has_update = ($local !== $remote);
$has_local_changes = trim($dirty) !== '';

respond([
    'status' => $has_update ? 'update_available' : 'up_to_date',
    'message' => $has_update ? 'A new update is available.' : 'TalaKlase is up to date.',
    'branch' => $branch,
    'local_hash' => substr($local, 0, 7),
    'remote_hash' => substr($remote, 0, 7),
    'dirty' => $has_local_changes,
    'dirty_message' => $has_local_changes ? 'Local file changes detected.' : 'Working tree is clean.'
]);