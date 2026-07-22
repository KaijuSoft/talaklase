<?php

require_once 'includes/db.php';
require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\DataExecutor;

$operation = [

    'operation' => 'update',

    'table' => 'student',

    'primary_key' => 'st_id',

    'data' => [

        'st_id' => 999,

        'student_no' => '2026-9999',

        'st_lastname' => 'Updated',

        'st_name' => 'Atlas',

        'st_middlename' => '',

        'st_suffix' => '',

        'st_gender' => 'Male',

        'course_id' => 1,

        'student_status' => 'Active'

    ]

];

$executor = new DataExecutor(
    getOnlineConnection(),
    [],
    true
);

echo "<pre>";

print_r(
    $executor->buildUpdateSQL($operation)
);

echo "</pre>";