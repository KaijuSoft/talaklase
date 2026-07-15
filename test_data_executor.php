<?php

require_once 'includes/db.php';
require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\DataExecutor;

echo "Before constructor<br>";

$plan = [

    [
        'operation' => 'delete',

        'table' => 'student',

        'primary_key' => 'st_id',

        'data' => [

            'st_id' => 999,

            'student_no' => 'TEST-0001',

            'st_lastname' => 'Vendivil',

            'st_name' => 'Julius',

            'st_middlename' => '',

            'st_suffix' => '',

            'st_gender' => 'Male',

            'course_id' => 999999,

            'student_status' => 'Active'

        ]

    ]

];

$executor = new DataExecutor(
    getOnlineConnection(),
    $plan,
	false
);

$result = $executor->execute();

print_r($result);
echo "After constructor<br>";

echo "<pre>";
print_r($executor->execute());
echo "</pre>";