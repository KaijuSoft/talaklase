<?php
$config = [
  'title'     => 'Sections',
  'icon'      => 'bi-grid-fill',
  'table'     => 'section',
  'pk'        => 'sectionID',
  'fields'    => [
    ['name'=>'section',   'label'=>'Section Name', 'type'=>'text', 'required'=>true],
    ['name'=>'course_id', 'label'=>'Course',       'fk'=>['table'=>'course','id'=>'course_id','label'=>'course_acronym']],
  ],
  'list_cols' => ['section'=>'Section Name'],
  'order'     => 'section',
];
include __DIR__ . '/../includes/crud_page.php';
