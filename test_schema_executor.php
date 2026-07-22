<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/TALA/bootstrap.php';

use Tala\Engine\SchemaExecutor;



echo "<pre>";

echo "Class exists: ";
var_dump(class_exists(SchemaExecutor::class));

$source = getLocalConnection();
$destination = getOnlineConnection();

echo "Before constructor\n";

$pdo = getLocalConnection();

echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "<br>";

echo "<pre>";
print_r($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN));
echo "</pre>";

$stmt = $pdo->query("SHOW CREATE TABLE `tala_executor_test`");

echo "<pre>";
print_r($stmt->fetch(PDO::FETCH_ASSOC));
echo "</pre>";

$executor = new SchemaExecutor(
    $source,
    $destination
);

echo "After constructor\n";

echo "<h3>SHOW CREATE TABLE Test</h3>";

$stmt = $source->query("SHOW CREATE TABLE `tala_executor_test`");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = $row['Create Table'];

$result = $executor->execute([
    'operations' => [
        [
		'operation' => 'create_table',
		'target'    => 'tala_executor_test',

		'details' => [
        'table' => 'tala_executor_test'
		],

		'sql' => $sql
		]
    ]
]);

echo "<pre>";
print_r($result);
echo "</pre>";


echo "</pre>";