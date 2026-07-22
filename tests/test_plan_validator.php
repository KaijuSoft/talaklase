<?php

require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\PlanValidator;

$validator = new PlanValidator();

$plan = [

    'operations' => [

        [
            'category'  => 'schema',
            'operation' => 'create_table',
            'target'    => 'student',
            'details'   => []
        ],

        [
            'category'  => 'schema',
            'operation' => 'add_column',
            'target'    => 'student.student_no',
            'details'   => []
        ],

        [
            'category'  => 'schema',
            'operation' => 'create_index',
            'target'    => 'student.idx_name',
            'details'   => []
        ]

    ]

];

echo "<pre>";
print_r($validator->validate($plan));
echo "</pre>";