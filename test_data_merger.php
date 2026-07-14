<?php

require_once 'includes/db.php';
require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\DataSnapshot;
use Tala\Engine\DataInspector;
use Tala\Engine\DataMerger;

$local = new DataSnapshot(getLocalConnection());
$online = new DataSnapshot(getOnlineConnection());

$localStudent = $local->snapshotTable('student');
$onlineStudent = $online->snapshotTable('student');

$inspector = new DataInspector($localStudent, $onlineStudent);

$analysis = $inspector->analyze();

$merger = new DataMerger(
    'student',
    $analysis
);

echo "<pre>";
print_r($merger->buildPlan());
echo "</pre>";