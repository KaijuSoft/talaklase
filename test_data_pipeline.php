<?php

require_once 'includes/db.php';
require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\DataSnapshot;
use Tala\Engine\DataInspector;
use Tala\Engine\DataMerger;

echo "<h1>TALA Engine RC2 - Data Pipeline Test</h1><hr>";

$localSnapshot = new DataSnapshot(getLocalConnection());
$onlineSnapshot = new DataSnapshot(getOnlineConnection());

$table = 'student';

$local = $localSnapshot->snapshotTable($table);
$online = $onlineSnapshot->snapshotTable($table);

$inspector = new DataInspector($local, $online);

$analysis = $inspector->analyze();

$merger = new DataMerger($table, $analysis);

$plan = $merger->buildPlan();

echo "<h2>Analysis</h2>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Insert</td><td>" . count($analysis['insert']) . "</td></tr>";
echo "<tr><td>Update</td><td>" . count($analysis['update']) . "</td></tr>";
echo "<tr><td>Delete</td><td>" . count($analysis['delete']) . "</td></tr>";
echo "<tr><td>Unchanged</td><td>" . count($analysis['unchanged']) . "</td></tr>";
echo "</table>";

echo "<h2>Execution Plan</h2>";

echo "<strong>Total Operations:</strong> " . count($plan);

echo "<pre>";
print_r(array_slice($plan, 0, 5));
echo "</pre>";