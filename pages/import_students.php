<?php
session_start();
require_once '../includes/db.php';
require_once '../vendor/autoload.php';
require_once '../includes/academic_year.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getConnection();

if (!isset($_FILES['excel_file'])) {
    die('No file uploaded.');
}

$spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);

$rows = $spreadsheet
    ->getActiveSheet()
    ->toArray();

unset($rows[0]); // Remove header
$imported = 0;
$skipped = 0;
$errors = [];

$ayId = current_ay_id($pdo);

foreach ($rows as $row) {



   $studentNo  = ($tmp = trim($row[0] ?? '')) === '' ? null : $tmp;

	$lastname   = ucwords(strtolower(trim($row[1] ?? '')));
	$firstname  = ucwords(strtolower(trim($row[2] ?? '')));
	$middlename = ucwords(strtolower(trim($row[3] ?? '')));
	$suffix     = ucwords(strtolower(trim($row[4] ?? '')));

	$gender = ucfirst(strtolower(trim($row[5] ?? '')));
	
	$course     = trim($row[6] ?? '');
	$yearlvl    = (int)($row[7] ?? 0);
	$section    = trim($row[8] ?? '');

    if (
    $studentNo === null &&
    $lastname === '' &&
    $firstname === '' &&
    $middlename === '' &&
    $suffix === '' &&
    $gender === '' &&
    $course === '' &&
    $section === ''
	) {
    continue;
	}
	
if ($yearlvl < 1 || $yearlvl > 4) {

    $errors[] =
        "{$lastname}, {$firstname}: Invalid year level '{$yearlvl}'";

    $skipped++;
    continue;
	
}
	
	//if (!in_array($yearlvl, [1,2,3,4], true)) {
    //$skipped++;
    //continue;
//}

    // Find course_id from acronym
    $courseStmt = $pdo->prepare("
        SELECT course_id
        FROM course
        WHERE course_acronym = ?
        LIMIT 1
    ");

    $courseStmt->execute([$course]);

    $courseId = $courseStmt->fetchColumn();
	


	
  if (!$courseId) {

    $errors[] =
        "{$lastname}, {$firstname}: Course '{$course}' not found";

    $skipped++;
    continue;
	
}
	
	$sectionStmt = $pdo->prepare("
    SELECT sectionID
    FROM section
    WHERE section = ?
    LIMIT 1
	");

	$sectionStmt->execute([$section]);

	$sectionId = $sectionStmt->fetchColumn();

	if (!$sectionId) {

    $errors[] =
        "{$lastname}, {$firstname}: Section '{$section}' not found";

    $skipped++;
    continue;
}

	//if (!$sectionId) {
   // $skipped++;
    //continue;
//}
	
    // Duplicate check
    if ($studentNo !== null) {
        $studentNoCheck = $pdo->prepare("
            SELECT st_id
            FROM student
            WHERE student_no = ?
            LIMIT 1
        ");
        $studentNoCheck->execute([$studentNo]);
        if ($studentNoCheck->fetch()) {
            $errors[] =
                "{$lastname}, {$firstname}: Student Number '{$studentNo}' already exists";

            $skipped++;
            continue;
        }
    }

    $check = $pdo->prepare("
    SELECT st_id
    FROM student
    WHERE
        st_lastname = ?
        AND st_name = ?
        AND st_middlename = ?
        AND st_suffix = ?
        AND course_id = ?
    LIMIT 1
");

   $check->execute([
    $lastname,
    $firstname,
    $middlename,
    $suffix,
    $courseId
]);

   if ($check->fetch()) {

    $errors[] =
        "{$lastname}, {$firstname}: Student already exists";

    $skipped++;
    continue;
}
$pdo->beginTransaction();

	try{
    // Insert student
    $insert = $pdo->prepare("
        INSERT INTO student (
            student_no,
            st_lastname,
            st_name,
            st_middlename,
            st_suffix,
            st_gender,
            course_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $studentNo,
        $lastname,
        $firstname,
        $middlename,
        $suffix,
        $gender,
        $courseId
    ]);
	$stId = $pdo->lastInsertId();



$secInsert = $pdo->prepare("
    INSERT INTO student_section
    (
        st_id,
        sectionID,
        yearlvl,
        ay_id
    )
    VALUES (?, ?, ?, ?)
");

$secInsert->execute([
    $stId,
    $sectionId,
    $yearlvl,
    $ayId
]);
	
    $imported++;
	$pdo->commit();
	
	} catch (Throwable $e) {
		$pdo->rollBack();
		
		$errors[] =
    "{$lastname}, {$firstname}: " . $e->getMessage();
		
		$skipped++;
	}
}

$_SESSION['import_errors'] = $errors;

$_SESSION['import_success'] =
    "Imported {$imported} students. Skipped {$skipped} rows.";

header('Location: ../index.php?page=students');
exit;
