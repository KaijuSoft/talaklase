<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_permission('sync_settings');

$backupDir = __DIR__ . '/../storage/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

function createBackupSql(PDO $pdo): string
{
    $tables = $pdo->query("SHOW TABLES")
                  ->fetchAll(PDO::FETCH_COLUMN);

    $sql = '';

    foreach ($tables as $table) {
        $create = $pdo->query(
            "SHOW CREATE TABLE `$table`"
        )->fetch(PDO::FETCH_ASSOC);

        $sql .= "\n\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create['Create Table'] . ";\n\n";

        $rows = $pdo->query(
            "SELECT * FROM `$table`"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $values = array_map(
                static function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return $pdo->quote($value);
                },
                array_values($row)
            );

            $sql .= sprintf(
                "INSERT INTO `%s` VALUES (%s);\n",
                $table,
                implode(',', $values)
            );
        }

        $sql .= "\n";
    }

    return $sql;
}

function backupSourceLabel(string $filename): string
{
    if (preg_match('/^talaklase_(online|local)_/i', $filename, $matches) === 1) {
        return ucfirst(strtolower($matches[1]));
    }

    return 'Unknown';
}

if (isset($_POST['create_backup'])) {
    $source = strtolower(trim($_POST['backup_source'] ?? 'online'));
    $timestamp = date('Y-m-d_H-i-s');
    $created = [];

    $sources = match ($source) {
        'local' => ['local'],
        'both' => ['online', 'local'],
        default => ['online'],
    };

    foreach ($sources as $backupSource) {
        $pdo = $backupSource === 'local'
            ? getLocalConnection()
            : getOnlineConnection();

        $sql = createBackupSql($pdo);
        $filename = sprintf(
            'talaklase_%s_%s.sql',
            $backupSource,
            $timestamp
        );

        file_put_contents(
            $backupDir . '/' . $filename,
            $sql
        );

        $created[] = $filename;
    }

    $_SESSION['import_success'] =
        count($created) > 1
            ? "Backups created successfully."
            : "Backup created successfully.";

    header('Location: index.php?page=db_backup');
    exit;
}

$files = glob($backupDir . '/*.sql');
rsort($files);
?>

<div class="card">
    <div class="card-header">
        Database Backup
    </div>

    <div class="card-body">
        <form method="post" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="backup_source">Source</label>
                <select id="backup_source" name="backup_source" class="form-select">
                    <option value="online" selected>Online Database</option>
                    <option value="local">Local Database</option>
                    <option value="both">Both Databases</option>
                </select>
            </div>
            <div class="col-md-4">
                <button
                    type="submit"
                    name="create_backup"
                    class="btn btn-primary">

                    <i class="bi bi-database-add"></i>
                    Create Backup
                </button>
            </div>
        </form>

        <hr>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Filename</th>
                    <th>Date</th>
                    <th>Size</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?= htmlspecialchars(backupSourceLabel(basename($file))) ?></td>
                    <td><?= htmlspecialchars(basename($file)) ?></td>
                    <td><?= date('Y-m-d H:i:s', filemtime($file)) ?></td>
                    <td><?= round(filesize($file) / 1024, 2) ?> KB</td>
                    <td>
                        <a
                            href="download_backup.php?file=<?= urlencode(basename($file)) ?>"
                            class="btn btn-success btn-sm">

                            <i class="bi bi-download"></i>
                            Download
                        </a>

                        <a
                            href="delete_backup.php?file=<?= urlencode(basename($file)) ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete this backup?');">

                            <i class="bi bi-trash"></i>
                            Delete
                        </a>

                        <a
                            href="restore_backup.php?file=<?= urlencode(basename($file)) ?>"
                            class="btn btn-warning btn-sm"
                            onclick="return confirm('Restore this backup?\n\nCurrent database will be overwritten.');">

                            <i class="bi bi-arrow-counterclockwise"></i>
                            Restore
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
