<?php

require_once 'includes/TALA/bootstrap.php';

use Tala\Engine\ExecutionPlanBuilder;

$plan = [
    'operations' => [

        [
            'operation' => 'create_index',
            'category' => 'schema',
            'target' => 'student.idx_name',
            'details' => []
        ],

        [
            'operation' => 'create_table',
            'category' => 'schema',
            'target' => 'student',
            'details' => []
        ],

        [
            'operation' => 'add_column',
            'category' => 'schema',
            'target' => 'student.student_no',
            'details' => []
        ]

    ]
];

$builder = new ExecutionPlanBuilder();

echo "<pre>";
print_r($builder->build($plan));
echo "</pre>";