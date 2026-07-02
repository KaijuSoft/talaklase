<?php

declare(strict_types=1);

use Tala\Engine\TalaEngine;

return [

    'department' => [
        'primary_key' => 'dept_id',
        'type' => TalaEngine::TYPE_REFERENCE,
    ],

    'course' => [
        'primary_key' => 'course_id',
        'depends' => ['department'],
        'type' => TalaEngine::TYPE_REFERENCE,
    ],

    'subject' => [
        'primary_key' => 'sub_id',
        'type' => TalaEngine::TYPE_REFERENCE,
    ],

    'academic_year' => [
        'primary_key' => 'ay_id',
        'type' => TalaEngine::TYPE_REFERENCE,
    ],
	
	'student' => [

    'primary_key' => 'st_id',

    'business_key' => [
        'student_no'
    ],

    'ignore' => [
        'st_id'
    ],

    'depends' => [
        'course'
    ],

    'type' => TalaEngine::TYPE_MASTER,

],
];