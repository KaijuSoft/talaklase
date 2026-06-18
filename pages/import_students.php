<?php
session_start();
require_once '../includes/db.php';
require_once '../vendor/autoload.php';

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

foreach ($rows as $row) {

    $lastname   = trim($row[0] ?? '');
    $firstname  = trim($row[1] ?? '');
    $middlename = trim($row[2] ?? '');
    $suffix     = trim($row[3] ?? '');
    $gender     = trim($row[4] ?? '');
    $course     = trim($row[5] ?? '');
	$section 	= trim($row[6] ?? '');
	$yearlvl 	= (int)($row[7] ?? 0);

    if ($lastname === '' || $firstname === '') {
        $skipped++;
        continue;
    }
	
if ($yearlvl < 1 || $yearlvl > 4) {
    //die("Invalid year level: [" . $yearlvl . "]");
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
    die("Section not found: " . $section);
}

	//if (!$sectionId) {
   // $skipped++;
    //continue;
//}
	
    // Duplicate check
    $check = $pdo->prepare("
        SELECT st_id
        FROM student
        WHERE st_lastname = ?
        AND st_name = ?
        AND COALESCE(st_middlename,'') = ?
        LIMIT 1
    ");

    $check->execute([
        $lastname,
        $firstname,
        $middlename
    ]);

    if ($check->fetch()) {
        $skipped++;
        continue;
    }
$pdo->beginTransaction();

	try{
    // Insert student
    $insert = $pdo->prepare("
        INSERT INTO student (
            st_lastname,
            st_name,
            st_middlename,
            st_suffix,
            st_gender,
            course_id
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([
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
        yearlvl
    )
    VALUES (?, ?, ?)
");

$secInsert->execute([
    $stId,
    $sectionId,
    $yearlvl
]);
	
    $imported++;
	$pdo->commit();
	
	}catch (Excemption $e) {
		$pdo->rollBack();
		$skipped++;
	}
}

$_SESSION['import_success'] =
    "Imported {$imported} students. Skipped {$skipped} rows.";

header('Location: ../index.php?page=students');
exit;