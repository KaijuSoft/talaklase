<?php
$config = [
  'title'     => 'Departments',
  'icon'      => 'bi-building',
  'table'     => 'department',
  'pk'        => 'dept_id',
  'fields'    => [
    ['name'=>'dept_name','label'=>'Department Name','type'=>'text','required'=>true],
  ],
  'list_cols' => ['dept_name'=>'Department Name'],
  'order'     => 'dept_name',
];
include __DIR__ . '/../includes/crud_page.php';
