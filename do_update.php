<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_permission('manage_updates');
require_once __DIR__ . '/ReleaseChecker.php';

header('Content-Type: application/json; charset=utf-8');

const RELEASE_PRESERVED_PATHS = [
    'config.php',
    '.env',
    'uploads',
    'storage',
    'logs',
    'backups',
    '.git',
];

function respond(array $data): never
{
    echo json_encode($data);
    exit;
}

function releaseFailure(string $message, string $stage): never
{
    throw new RuntimeException($stage . ': ' . $message);
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($directory);
}

function addDirectoryToZip(ZipArchive $zip, string $directory, string $root): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $relative = substr($file->getPathname(), strlen($root) + 1);
        $zip->addFile($file->getPathname(), str_replace(DIRECTORY_SEPARATOR, '/', $relative));
    }
}

function copyReleaseFiles(string $source, string $destination, array $preserved): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        $firstPart = explode('/', $relative, 2)[0];

        if (in_array($firstPart, $preserved, true)) {
            continue;
        }

        $target = $destination . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                throw new RuntimeException('Unable to create release directory.');
            }
            continue;
        }

        $parent = dirname($target);
        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new RuntimeException('Unable to create release file directory.');
        }

        if (!copy($item->getPathname(), $target)) {
            throw new RuntimeException('Unable to install release files.');
        }
    }
}

$root = __DIR__;
$manifestUrl = getenv('TALAKLASE_RELEASE_MANIFEST_URL')
    ?: 'https://github.com/SunriseRaven/talaklase/releases/latest/download/manifest.json';
$temporary = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'talaklase_release_' . bin2hex(random_bytes(8));
$zipPath = $temporary . '.zip';
$extractPath = $temporary . DIRECTORY_SEPARATOR . 'package';
$backupDirectory = $root . DIRECTORY_SEPARATOR . 'backups';
$timestamp = date('Ymd_His');

try {
    $manifestChecker = new ReleaseChecker($root . '/version.json', $manifestUrl);
    $manifest = $manifestChecker->readReleaseManifest();
    $downloadUrl = $manifest['download_url'] ?? '';

    if (!is_string($downloadUrl) || !filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
        releaseFailure('Release manifest is missing a valid download URL.', 'manifest');
    }

    if (!mkdir($temporary, 0755, true) && !is_dir($temporary)) {
        releaseFailure('Unable to create a temporary release directory.', 'prepare');
    }

    $package = @file_get_contents($downloadUrl, false, stream_context_create([
        'http' => ['timeout' => 60],
        'https' => ['timeout' => 60],
    ]));
    if ($package === false || file_put_contents($zipPath, $package) === false) {
        releaseFailure('Unable to download the release package.', 'download');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        releaseFailure('The release package is not a valid ZIP archive.', 'package');
    }
    if (!$zip->extractTo($extractPath) || $zip->close() !== true) {
        releaseFailure('Unable to extract the release package.', 'extract');
    }

    $entries = array_values(array_diff(scandir($extractPath) ?: [], ['.', '..']));
    $packageRoot = count($entries) === 1 && is_dir($extractPath . DIRECTORY_SEPARATOR . $entries[0])
        ? $extractPath . DIRECTORY_SEPARATOR . $entries[0]
        : $extractPath;
    if (!is_file($packageRoot . DIRECTORY_SEPARATOR . 'index.php')) {
        releaseFailure('The release package is missing the application entry point.', 'validate');
    }

    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true) && !is_dir($backupDirectory)) {
        releaseFailure('Unable to create the release backup directory.', 'backup');
    }
    $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . 'talaklase_backup_' . $timestamp . '.zip';
    $backup = new ZipArchive();
    if ($backup->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        releaseFailure('Unable to create the release backup.', 'backup');
    }
    addDirectoryToZip($backup, $root, $root);
    $backup->close();

    copyReleaseFiles($packageRoot, $root, RELEASE_PRESERVED_PATHS);
    if (is_file($packageRoot . DIRECTORY_SEPARATOR . 'version.json')) {
        copy($packageRoot . DIRECTORY_SEPARATOR . 'version.json', $root . DIRECTORY_SEPARATOR . 'version.json');
    }

    respond([
        'status' => 'success',
        'message' => 'Release installed successfully.',
        'version' => $manifest['version'],
        'backup' => str_replace($root . DIRECTORY_SEPARATOR, '', $backupPath),
    ]);
} catch (Throwable $error) {
    $message = $error->getMessage();
    $parts = explode(': ', $message, 2);

    respond([
        'status' => 'error',
        'message' => $parts[1] ?? 'Release installation failed.',
        'stage' => $parts[1] === null ? 'install' : $parts[0],
    ]);
} finally {
    if (is_dir($temporary)) {
        removeDirectory($temporary);
    }
    if (is_file($zipPath)) {
        unlink($zipPath);
    }
}
