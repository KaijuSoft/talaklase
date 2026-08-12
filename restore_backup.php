<?php

require_once 'includes/auth.php';
require_once 'includes/db.php';

require_permission('sync_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(403);
    exit('Invalid request.');
}

$file = basename($_POST['file'] ?? '');

$backupFile =
    __DIR__ . '/storage/backups/' . $file;

if ($file === '' || !is_file($backupFile)) {
    http_response_code(404);
    exit('Backup not found.');
}

echo '
<!DOCTYPE html>
<html>
<head>
<title>Restoring Database</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
</head>
<body>

<div class="container py-5">

<div class="card shadow">

<div class="card-body text-center">

<div class="spinner-border text-primary mb-3"></div>

<h3>Database Restore In Progress</h3>

<p>Please wait while TalaKlase restores the database.</p>

<p><strong>Do not close this page.</strong></p>

</div>

</div>

</div>



';

if (ob_get_level()) {
    ob_flush();
}
flush();

$pdo = getConnection();

echo "<p class='text-center text-primary'> Connected to database...</p>";
flush();

$backupDir =
    __DIR__ . '/storage/backups';

$safetyFile =
    $backupDir .
    '/pre_restore_' .
    date('Y-m-d_H-i-s') .
    '.sql';

echo "<p class='text-center'>Creating emergency backup...</p>";
	flush();

$tables =
    $pdo->query("SHOW TABLES")
        ->fetchAll(PDO::FETCH_COLUMN);

$sql = '';

foreach ($tables as $table) {

    $create =
        $pdo->query(
            "SHOW CREATE TABLE `$table`"
        )->fetch(PDO::FETCH_ASSOC);

    $sql .=
        "DROP TABLE IF EXISTS `$table`;\n";

    $sql .=
        $create['Create Table'] .
        ";\n\n";

    $rows =
        $pdo->query(
            "SELECT * FROM `$table`"
        )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {

        $values = array_map(
            fn($v) =>
                $v === null
                    ? 'NULL'
                    : $pdo->quote($v),
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

	file_put_contents(
		$safetyFile,
		$sql
	);
	
	echo "<p class='text-center text-success'> Emergency backup created</p>";
	flush();

	echo "<p class='text-center'>Reading backup file...</p>";
	flush();

	$sql =
		file_get_contents($backupFile);

	$pdo->exec(
		"SET FOREIGN_KEY_CHECKS = 0"
	);
	
	echo "<p class='text-center'>Preparing SQL restore...</p>";
	flush();
	
	$statements =
    preg_split(
        '/;\s*[\r\n]+/',
        $sql
    );
		
		echo "<p class='text-center'>Applying database changes...</p>";
		flush();
		
		$totalStatements = count($statements);
		$current = 0;

	foreach ($statements as $statement) {
		
		$current++;
		
		$statement = trim($statement);
		
		if ($current % 100 == 0) {

    echo "<p class='text-muted text-center'>
            Restored {$current} of {$totalStatements} SQL statements...
          </p>";

    flush();
}

		if ($statement === '') {
			continue;
		}

		$pdo->exec($statement);
	}
	
		$pdo->exec(
			"SET FOREIGN_KEY_CHECKS = 1"
	);
	
echo "

<hr>

<div class='alert alert-success mt-4'>

<h4> Database restored successfully</h4>

<p>
The database has been restored from:
<strong>{$file}</strong>
</p>

<p>
You will be redirected automatically.
</p>

</div>

<script>

setTimeout(function(){

    window.location =
        'index.php?page=db_backup';

}, 3000);

</script>

";

exit;