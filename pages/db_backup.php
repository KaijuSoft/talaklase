<?php

require_once __DIR__ . '/../includes/auth.php';
require_permission('sync_settings');

$backupDir = __DIR__ . '/../storage/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}
if (isset($_POST['create_backup'])) {

    $pdo = getConnection();

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
                function ($value) use ($pdo) {

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

    $filename =
        'talaklase_' .
        date('Y-m-d_H-i-s') .
        '.sql';

    file_put_contents(
        $backupDir . '/' . $filename,
        $sql
    );

    $_SESSION['import_success'] =
        "Backup created successfully.";

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

       <form method="post">
    <button
        type="submit"
        name="create_backup"
        class="btn btn-primary">

        <i class="bi bi-database-add"></i>
        Create Backup

    </button>
</form>

        <hr>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Date</th>
                    <th>Size</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($files as $file): ?>

                <tr>
                    <td><?= htmlspecialchars(basename($file)) ?></td>
                    <td><?= date('Y-m-d H:i:s', filemtime($file)) ?></td>
                    <td><?= round(filesize($file)/1024,2) ?> KB</td>
						<td>
    <a
        href="download_backup.php?file=<?= urlencode(basename($file)) ?>"
        class="btn btn-success btn-sm">

        <i class="bi bi-download"></i>
        Download
		
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
			onclick="return confirm(
			'Restore this backup?\n\nCurrent database will be overwritten.'
		);">

			<i class="bi bi-arrow-counterclockwise"></i>
			Restore

				</a>
			</a>
		</td>
      </tr>
			
            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>