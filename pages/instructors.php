<?php
$config = [
  'title'     => 'Instructors',
  'icon'      => 'bi-person-badge-fill',
  'table'     => 'instructor',
  'pk'        => 'inst_id',
  'fields'    => [
    ['name'=>'inst_name', 'label'=>'Instructor Name', 'type'=>'text', 'required'=>true],
    ['name'=>'dept_id',   'label'=>'Department',      'fk'=>['table'=>'department','id'=>'dept_id','label'=>'dept_name']],
  ],
  'list_cols' => ['inst_name'=>'Instructor Name'],
  'order'     => 'inst_name',
];
include __DIR__ . '/../includes/crud_page.php';
