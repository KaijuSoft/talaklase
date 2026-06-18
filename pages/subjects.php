<?php
$config = [
  'title'     => 'Subjects',
  'icon'      => 'bi-file-earmark-text-fill',
  'table'     => 'subject',
  'pk'        => 'sub_id',
  'fields'    => [
    ['name'=>'sub_code', 'label'=>'Subject Code', 'type'=>'text', 'required'=>true],
    ['name'=>'sub_name', 'label'=>'Subject Name', 'type'=>'text', 'required'=>true],
  ],
  'list_cols' => ['sub_code'=>'Code','sub_name'=>'Subject Name'],
  'order'     => 'sub_name',
];
include __DIR__ . '/../includes/crud_page.php';
