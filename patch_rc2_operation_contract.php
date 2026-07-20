<?php

declare(strict_types=1);

echo "=========================================\n";
echo "TALA RC2 Operation Contract Patch\n";
echo "Version: RC2.10 Patch 001\n";
echo "=========================================\n\n";

$root = __DIR__;

$files = [
    'includes/TALA/src/MergeValidator.php',
    'includes/TALA/src/PlanValidator.php',
    'includes/TALA/src/ExecutionPlanBuilder.php',
];

$backupDir = $root . '/includes/TALA/backups/RC2.10_Patch001';

if (!is_dir($backupDir)) {

    mkdir($backupDir, 0777, true);

}

foreach ($files as $file) {

    $source = $root . '/' . $file;

    if (!file_exists($source)) {

        echo "[SKIP] {$file} not found\n";
        continue;

    }

    copy(
        $source,
        $backupDir . '/' . basename($file)
    );

    echo "[BACKUP] {$file}\n";

}

	
	function patchFile(
    string $file,
    callable $patcher
): bool {

    if (!file_exists($file)) {
        echo "[ERROR] {$file} not found.\n";
        return false;
    }

    $original = file_get_contents($file);

    $patched = $patcher($original);

    if ($patched === $original) {
        echo "[SKIP] {$file} (no changes)\n";
        return true;
    }

    if (file_put_contents($file, $patched) === false) {
        echo "[FAILED] {$file}\n";
        return false;
    }

    echo "[PATCHED] {$file}\n";
    return true;
}

function replaceOrFail(
    string &$content,
    string $search,
    string $replace,
    string $description
): void {

    $count = 0;

    $content = str_replace(
        $search,
        $replace,
        $content,
        $count
    );

    if ($count === 0) {
        throw new RuntimeException(
            "Unable to locate: {$description}"
        );
    }

    echo "[OK] {$description} ({$count} replacement(s))\n";
}

function patchMergeValidator(string $root): void
{
    $file = $root . '/includes/TALA/src/MergeValidator.php';

    if (!file_exists($file)) {
        echo "[ERROR] MergeValidator.php not found.\n";
        return;
    }

    echo "[PATCH] Rebuilding MergeValidator...\n";

    $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace Tala\Engine;

final class MergeValidator
{

}
PHP;

    file_put_contents($file, $content);

    echo "[DONE] MergeValidator rebuilt.\n";
}

patchMergeValidator($root);
