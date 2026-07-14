<?php

require_once 'includes/db.php';
require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\DataSnapshot;
use Tala\Engine\DataInspector;

echo "<h1>TALA Engine - Data Inspector Diagnostics</h1>";
echo "<hr>";

// --------------------------------------------------
// CONNECTION INFORMATION
// --------------------------------------------------

$localPDO = getLocalConnection();
$onlinePDO = getOnlineConnection();

echo "<h2>Connection Information</h2>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
        <th></th>
        <th>Local</th>
        <th>Online</th>
      </tr>";

echo "<tr>";
echo "<td><strong>Server</strong></td>";
echo "<td>" . $localPDO->query("SELECT @@hostname")->fetchColumn() . "</td>";
echo "<td>" . $onlinePDO->query("SELECT @@hostname")->fetchColumn() . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Database</strong></td>";
echo "<td>" . $localPDO->query("SELECT DATABASE()")->fetchColumn() . "</td>";
echo "<td>" . $onlinePDO->query("SELECT DATABASE()")->fetchColumn() . "</td>";
echo "</tr>";

echo "</table>";

echo "<hr>";

// --------------------------------------------------
// SNAPSHOTS
// --------------------------------------------------

$localSnapshot = new DataSnapshot($localPDO);
$onlineSnapshot = new DataSnapshot($onlinePDO);

$localStudent = $localSnapshot->snapshotTable('student');
$onlineStudent = $onlineSnapshot->snapshotTable('student');

echo "<h2>Snapshot Summary</h2>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
        <th></th>
        <th>Local</th>
        <th>Online</th>
      </tr>";

echo "<tr>";
echo "<td><strong>Rows</strong></td>";
echo "<td>{$localStudent['row_count']}</td>";
echo "<td>{$onlineStudent['row_count']}</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Primary Key</strong></td>";
echo "<td>{$localStudent['primary_key']}</td>";
echo "<td>{$onlineStudent['primary_key']}</td>";
echo "</tr>";

echo "</table>";

echo "<hr>";

// --------------------------------------------------
// ANALYZE
// --------------------------------------------------

$inspector = new DataInspector($localStudent, $onlineStudent);

$result = $inspector->analyze();

echo "<h2>Analysis Summary</h2>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";

echo "<tr><td><strong>Insert</strong></td><td>" . count($result['insert']) . "</td></tr>";
echo "<tr><td><strong>Update</strong></td><td>" . count($result['update']) . "</td></tr>";
echo "<tr><td><strong>Delete</strong></td><td>" . count($result['delete']) . "</td></tr>";
echo "<tr><td><strong>Unchanged</strong></td><td>" . count($result['unchanged']) . "</td></tr>";

echo "</table>";

echo "<hr>";

// --------------------------------------------------
// SAMPLE DATA
// --------------------------------------------------

function showSample($title, $data)
{
    echo "<h3>{$title}</h3>";

    if (empty($data)) {
        echo "<p><em>None</em></p>";
        return;
    }

    echo "<pre>";
    print_r(array_slice($data, 0, 3, true));
    echo "</pre>";
}

showSample("Sample Inserts", $result['insert']);
showSample("Sample Updates", $result['update']);
showSample("Sample Deletes", $result['delete']);
showSample("Sample Unchanged", $result['unchanged']);