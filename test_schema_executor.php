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

$stmt = $pdo->query("SHOW CREATE TABLE `student`");

echo "<pre>";
print_r($stmt->fetch(PDO::FETCH_ASSOC));
echo "</pre>";

$executor = new SchemaExecutor(
    $source,
    $destination
);

echo "After constructor\n";

echo "<h3>SHOW CREATE TABLE Test</h3>";

$reflection = new ReflectionClass($executor);

$method = $reflection->getMethod('getCreateTableStatement');

$method->setAccessible(true);

$sql = $method->invoke($executor, 'student');

$reflection = new ReflectionClass($executor);

$normalize = $reflection->getMethod('normalizeCreateTableSql');
$normalize->setAccessible(true);

$sql = $normalize->invoke($executor, $sql);

echo "<pre>";
echo $sql;
echo "</pre>";

print_r(
    $executor->execute([
        'operations' => [
            [
                'action' => 'create_table',
                'table'  => 'student'
            ]
        ]
    ])
);

echo "</pre>";