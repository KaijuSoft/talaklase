<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/academic_year.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

require_permission('edit_students');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=students');
    exit;
}
if (!verify_csrf()) {
    http_response_code(403);
    exit('Invalid security token.');
}
if (!isset($_FILES['excel_file']) || !is_array($_FILES['excel_file'])) {
    $_SESSION['import_success'] = 'No Excel file was uploaded.';
    header('Location: ../index.php?page=students'); exit;
}
$file=$_FILES['excel_file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $_SESSION['import_success']='The Excel upload failed. Please try again.';
    header('Location: ../index.php?page=students'); exit;
}
if ((int)$file['size'] <= 0 || (int)$file['size'] > 10*1024*1024) {
    $_SESSION['import_success']='The Excel file must be larger than 0 bytes and no larger than 10 MB.';
    header('Location: ../index.php?page=students'); exit;
}
$extension=strtolower(pathinfo((string)$file['name'],PATHINFO_EXTENSION));
if ($extension !== 'xlsx') {
    $_SESSION['import_success']='Only .xlsx Excel files are supported.';
    header('Location: ../index.php?page=students'); exit;
}
try {
    $spreadsheet=IOFactory::load((string)$file['tmp_name']);
    $rows=$spreadsheet->getActiveSheet()->toArray();
    $pdo=getConnection();$ayId=current_ay_id($pdo);
    if (!$ayId) throw new RuntimeException('No active academic year is configured.');
    unset($rows[0]);$imported=0;$skipped=0;$errors=[];
    foreach($rows as $row){
        $studentNo=($tmp=trim((string)($row[0]??'')))===''?null:$tmp;
        $lastname=ucwords(strtolower(trim((string)($row[1]??''))));$firstname=ucwords(strtolower(trim((string)($row[2]??''))));$middlename=ucwords(strtolower(trim((string)($row[3]??''))));$suffix=ucwords(strtolower(trim((string)($row[4]??''))));$gender=ucfirst(strtolower(trim((string)($row[5]??''))));$course=trim((string)($row[6]??''));$yearlvl=(int)($row[7]??0);$section=trim((string)($row[8]??''));
        if($studentNo===null&&$lastname===''&&$firstname===''&&$middlename===''&&$suffix===''&&$gender===''&&$course===''&&$section==='')continue;
        if($yearlvl<1||$yearlvl>4){$errors[]="{$lastname}, {$firstname}: Invalid year level '{$yearlvl}'";$skipped++;continue;}
        $stmt=$pdo->prepare('SELECT course_id FROM course WHERE course_acronym=? LIMIT 1');$stmt->execute([$course]);$courseId=$stmt->fetchColumn();
        if(!$courseId){$errors[]="{$lastname}, {$firstname}: Course '{$course}' not found";$skipped++;continue;}
        $stmt=$pdo->prepare('SELECT sectionID FROM section WHERE section=? LIMIT 1');$stmt->execute([$section]);$sectionId=$stmt->fetchColumn();
        if(!$sectionId){$errors[]="{$lastname}, {$firstname}: Section '{$section}' not found";$skipped++;continue;}
        if($studentNo!==null){$stmt=$pdo->prepare('SELECT st_id FROM student WHERE student_no=? LIMIT 1');$stmt->execute([$studentNo]);if($stmt->fetch()){$errors[]="{$lastname}, {$firstname}: Student Number '{$studentNo}' already exists";$skipped++;continue;}}
        $stmt=$pdo->prepare('SELECT st_id FROM student WHERE st_lastname=? AND st_name=? AND st_middlename=? AND st_suffix=? AND course_id=? LIMIT 1');$stmt->execute([$lastname,$firstname,$middlename,$suffix,$courseId]);if($stmt->fetch()){$errors[]="{$lastname}, {$firstname}: Student already exists";$skipped++;continue;}
        try{$pdo->beginTransaction();$stmt=$pdo->prepare('INSERT INTO student (student_no,st_lastname,st_name,st_middlename,st_suffix,st_gender,course_id) VALUES (?,?,?,?,?,?,?)');$stmt->execute([$studentNo,$lastname,$firstname,$middlename,$suffix,$gender,$courseId]);$stId=(int)$pdo->lastInsertId();$stmt=$pdo->prepare('INSERT INTO student_section (st_id,sectionID,yearlvl,ay_id) VALUES (?,?,?,?)');$stmt->execute([$stId,$sectionId,$yearlvl,$ayId]);$pdo->commit();$imported++;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]="{$lastname}, {$firstname}: Unable to import row.";$skipped++;error_log('Student import row failed: '.$e->getMessage());}
    }
    $_SESSION['import_errors']=$errors;$_SESSION['import_success']="Imported {$imported} students. Skipped {$skipped} rows.";
} catch(Throwable $e){$_SESSION['import_errors']=[];$_SESSION['import_success']='Student import could not be completed. Please verify the workbook and try again.';error_log('Student import failed: '.$e->getMessage());}
header('Location: ../index.php?page=students');exit;