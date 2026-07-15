<?php

require_once 'includes/db.php';
require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\DataExecutor;

echo "Before constructor<br>";

$plan = [

    [

        'operation' => 'insert',

        'table' => 'student',

        'primary_key' => 1000,

        'data' => [

            'st_id' => 1000,

            'student_no' => 'TEST-1000',

            'st_lastname' => 'Rollback',

            'st_name' => 'First',

            'st_middlename' => '',

            'st_suffix' => '',

            'st_gender' => 'Male',

            'course_id' => 1,

            'student_status' => 'Active'

        ]

    ],

	 [
        'operation' => 'insert',
        'table' => 'student',
        'primary_key' => 1001,
        'data' => [
            'st_id' => 1001,
            'student_no' => 'TEST-1001',
            'st_lastname' => 'Rollback',
            'st_name' => 'Second',
            'st_middlename' => '',
            'st_suffix' => '',
            'st_gender' => 'Male',
            'course_id' => 1,
            'student_status' => 'Active'
        ]
    ], 
	
	[
        'operation' => 'insert',
        'table' => 'student',
        'primary_key' => 999,
        'data' => [
            'st_id' => 999,
            'student_no' => 'TEST-0001',
            'st_lastname' => 'Executor',
            'st_name' => 'Test',
            'st_middlename' => '',
            'st_suffix' => '',
            'st_gender' => 'Male',
            'course_id' => 1,
            'student_status' => 'Active'
        ]
    ]

];

$executor = new DataExecutor(
    getOnlineConnection(),
    $plan,
	false
);

echo "After constructor<br>";

echo "<pre>";
print_r($executor->execute());
echo "</pre>";