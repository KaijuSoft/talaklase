<?php
$config = [
  'title'     => 'Courses',
  'icon'      => 'bi-book-fill',
  'table'     => 'course',
  'pk'        => 'course_id',
  'fields'    => [
    ['name'=>'course_name',    'label'=>'Course Name',    'type'=>'text', 'required'=>true],
    ['name'=>'course_acronym', 'label'=>'Acronym',        'type'=>'text', 'required'=>true],
    ['name'=>'dept_id',        'label'=>'Department',     'fk'=>['table'=>'department','id'=>'dept_id','label'=>'dept_name']],
  ],
  'list_cols' => ['course_name'=>'Course Name','course_acronym'=>'Acronym'],
  'order'     => 'course_name',
];
include __DIR__ . '/../includes/crud_page.php';
