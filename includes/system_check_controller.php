<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_permission('sync_settings');

$checks=[];$score=0;$maxScore=0;
function systemCheckItem(string $name,bool $passed,string $message=''): void { global $checks,$score,$maxScore; $maxScore++; if($passed)$score++; $checks[]=['name'=>$name,'passed'=>$passed,'message'=>$message]; }
$pdo=null;
try{$pdo=getConnection();systemCheckItem('Database Connection',true,'Connected successfully');}catch(Throwable $e){systemCheckItem('Database Connection',false,'Database connection failed.');error_log('System check DB connection failed: '.$e->getMessage());}
foreach(['users','student','course','section','subject','attendance'] as $table){
    if(!$pdo){systemCheckItem("Table: {$table}",false,'Database unavailable');continue;}
    try{$stmt=$pdo->prepare('SHOW TABLES LIKE ?');$stmt->execute([$table]);$exists=(bool)$stmt->fetchColumn();systemCheckItem("Table: {$table}",$exists,$exists?'Found':'Missing');}catch(Throwable $e){systemCheckItem("Table: {$table}",false,'Unable to inspect table.');}
}
$adminExists=false;
if($pdo){try{$stmt=$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");$adminExists=(int)$stmt->fetchColumn()>0;}catch(Throwable $e){}}
systemCheckItem('Admin Account Exists',$adminExists,$adminExists?'OK':'No admin account found');
$displayErrors=(bool)ini_get('display_errors');systemCheckItem('display_errors Disabled',!$displayErrors,$displayErrors?'display_errors is ON':'Safe');
systemCheckItem('HTTPS Enabled',!empty($_SERVER['HTTPS']),!empty($_SERVER['HTTPS'])?'HTTPS Active':'Not using HTTPS');
$autoload=dirname(__DIR__).'/vendor/autoload.php';systemCheckItem('Composer Autoload',is_file($autoload),is_file($autoload)?'Found':'Missing');
systemCheckItem('PhpSpreadsheet Installed',class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet'),class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')?'Installed':'Not Found');
systemCheckItem('Sessions Enabled',function_exists('session_start'),'PHP Sessions');systemCheckItem('PDO Extension',extension_loaded('pdo'),extension_loaded('pdo')?'Loaded':'Missing');systemCheckItem('MySQL Extension',extension_loaded('mysqli')||extension_loaded('pdo_mysql'),'Database Driver');systemCheckItem('File Uploads Enabled',(bool)ini_get('file_uploads'),ini_get('file_uploads')?'Enabled':'Disabled');systemCheckItem('ZIP Extension',extension_loaded('zip'),extension_loaded('zip')?'Installed':'Missing');systemCheckItem('GD Extension',extension_loaded('gd'),extension_loaded('gd')?'Installed':'Missing');
$orphanStudents=0;$duplicateUsers=0;
if($pdo){try{$orphanStudents=(int)$pdo->query('SELECT COUNT(*) FROM student s LEFT JOIN student_section ss ON s.st_id=ss.st_id WHERE ss.st_id IS NULL')->fetchColumn();}catch(Throwable $e){} try{$duplicateUsers=(int)$pdo->query('SELECT COUNT(*) FROM (SELECT username FROM users GROUP BY username HAVING COUNT(*)>1) x')->fetchColumn();}catch(Throwable $e){}}
systemCheckItem('Orphan Students',$orphanStudents===0,$orphanStudents.' orphan records');systemCheckItem('Duplicate Usernames',$duplicateUsers===0,$duplicateUsers.' duplicates found');
$healthPercent=(int)round(($score/max($maxScore,1))*100);