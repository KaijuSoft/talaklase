<?php

declare(strict_types=1);

use Tala\Engine\TalaEngine;

return [

    // =========================================================
    // REFERENCE TABLES
    // =========================================================

    'department' => [
        'primary_key' => 'dept_id',
        'type' => TalaEngine::TYPE_REFERENCE,
    ],

    'course' => [
        'primary_key' => 'course_id',
        'depends' => [
            'department'
        ],
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

    // =========================================================
    // MASTER TABLES
    // =========================================================

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

    'instructor' => [

        'primary_key' => 'inst_id',
		
		'business_key' => [
			'inst_name'
		],

        'ignore' => [
            'inst_id'
        ],

        'depends' => [
            'department'
        ],

        'type' => TalaEngine::TYPE_MASTER,

    ],

    'users' => [

        'primary_key' => 'id',

        'business_key' => [
            'username'
        ],

        'ignore' => [
            'id'
        ],

        'depends' => [
            'instructor'
        ],

        'type' => TalaEngine::TYPE_MASTER,

    ],

    // =========================================================
    // ACADEMIC STRUCTURE
    // =========================================================

   'section_subjects' => [

    'primary_key' => 'id',

    'business_key' => [
        'sectionID',
        'sub_id'
    ],

    'ignore' => [
        'id'
    ],

    'depends' => [
        'subject'
    ],

    'type' => TalaEngine::TYPE_MASTER,

	],

    'teaching_assignments' => [

        'primary_key' => 'assignment_id',
		
		'business_key' => [
			'sub_id',
			'inst_id',
			'sectionID',
			'ay_id'
		],

        'ignore' => [
            'assignment_id'
        ],

        'depends' => [
            'subject',
            'instructor',
            'academic_year'
        ],

        'type' => TalaEngine::TYPE_MASTER,

    ],

    'student_section' => [

        'primary_key' => 'stsec_id',
		
		'business_key' => [
			'st_id',
			'sectionID',
			'yearlvl',
			'ay_id'
		],

        'ignore' => [
            'stsec_id'
        ],

        'depends' => [
            'student',
            'academic_year'
        ],

        'type' => TalaEngine::TYPE_MASTER,

    ],

    'student_subject' => [

        'primary_key' => 'id',
		
		'business_key' => [
			'st_id',
			'assign_id'
		],

        'ignore' => [
            'id'
        ],

        'depends' => [
            'student',
            'teaching_assignments'
        ],

        'type' => TalaEngine::TYPE_MASTER,

    ],

    'student_assignments' => [

        'primary_key' => 'enrollment_id',
		
		'business_key' => [
			'st_id',
			'assignment_id',
			'ay_id'
		],

        'ignore' => [
            'enrollment_id'
        ],

        'depends' => [
            'student',
            'teaching_assignments',
            'academic_year'
        ],

        'type' => TalaEngine::TYPE_MASTER,

    ],
	
	'attendance' => [

    'primary_key' => 'Att_ID',
	
	'business_key' => [
		'st_id',
		'assignment_id',
		'_date',
		'term'
	],

    'ignore' => [
        'Att_ID'
    ],

    'depends' => [
        'student',
        'teaching_assignments',
        'academic_year'
    ],

    'type' => TalaEngine::TYPE_TRANSACTION,

	],

	'exam' => [

    'primary_key' => 'exam_id',

    'business_key' => [
        'term',
        'date_created'
    ],

    'ignore' => [
        'exam_id'
    ],

    'type' => TalaEngine::TYPE_REFERENCE,

	],
	
	'written' => [

    'primary_key' => 'written_id',

    'business_key' => [
        'term'
    ],

    'ignore' => [
        'written_id'
    ],

    'type' => TalaEngine::TYPE_REFERENCE,

	],
	
	'participation' => [

    'primary_key' => 'par_id',

    'business_key' => [
        'term'
    ],

    'ignore' => [
        'par_id'
    ],

    'type' => TalaEngine::TYPE_REFERENCE,

	],
	
	'performance' => [

    'primary_key' => 'perf_id',

    'business_key' => [
        'term'
    ],

    'ignore' => [
        'perf_id'
    ],

    'type' => TalaEngine::TYPE_REFERENCE,

	],
	
	'student_grades' => [

    'primary_key' => 'id',

    'business_key' => [
        'student_id',
        'subject_id',
        'section_id',
        'quarter'
    ],

    'ignore' => [
        'id'
    ],

    'depends' => [
        'student',
        'subject'
    ],

    'type' => TalaEngine::TYPE_TRANSACTION,
	
	],
	
	'tala_executor_test' => [

    'type' => TalaEngine::TYPE_REFERENCE,

    'primary_key' => 'id'

],

],
];