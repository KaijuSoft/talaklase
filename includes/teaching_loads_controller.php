<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('manage_teaching_loads');

$pdo = getConnection();

function normalizeTeachingLoadTime(?string $value): ?string {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function formatTeachingLoadTime(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $time = strtotime($value);
    return $time !== false ? date('h:i A', $time) : '';
}

function validateTeachingLoadSchedule(?string $startTime, ?string $endTime): ?string {
    if ($startTime === null || $endTime === null) {
        return 'Please enter both a start time and an end time.';
    }

    if (strtotime($endTime) <= strtotime($startTime)) {
        return 'End Time must be later than Start Time.';
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ]);

        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {

        $sectionID = (int)($_POST['sectionID'] ?? 0);
        $sub_id    = (int)($_POST['sub_id'] ?? 0);
        $inst_id   = (int)($_POST['inst_id'] ?? 0);
        $startTime = normalizeTeachingLoadTime($_POST['start_time'] ?? null);
        $endTime   = normalizeTeachingLoadTime($_POST['end_time'] ?? null);
		$ayId = current_ay_id($pdo);

        $validationMessage = validateTeachingLoadSchedule($startTime, $endTime);
        if ($validationMessage !== null) {
            echo json_encode([
                'success' => false,
                'message' => $validationMessage
            ]);
            exit;
        }

        try {

            $stmt = $pdo->prepare("
                INSERT INTO teaching_assignments
                (
                    sectionID,
                    sub_id,
                    inst_id,
                    start_time,
                    end_time,
					ay_Id
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $sectionID,
                $sub_id,
                $inst_id,
                $startTime,
                $endTime,
				$ayId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Teaching load assigned.'
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }
	
	if ($action === 'update') {

    $assignment_id =
        (int)($_POST['assignment_id'] ?? 0);

    $sectionID =
        (int)($_POST['sectionID'] ?? 0);

    $sub_id =
        (int)($_POST['sub_id'] ?? 0);

    $inst_id =
        (int)($_POST['inst_id'] ?? 0);
    $startTime = normalizeTeachingLoadTime($_POST['start_time'] ?? null);
    $endTime   = normalizeTeachingLoadTime($_POST['end_time'] ?? null);

    $validationMessage = validateTeachingLoadSchedule($startTime, $endTime);
    if ($validationMessage !== null) {
        echo json_encode([
            'success' => false,
            'message' => $validationMessage
        ]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            UPDATE teaching_assignments
            SET
                sectionID = ?,
                sub_id    = ?,
                inst_id   = ?,
                start_time = ?,
                end_time = ?
            WHERE assignment_id = ?
        ");

        $stmt->execute([
            $sectionID,
            $sub_id,
            $inst_id,
            $startTime,
            $endTime,
            $assignment_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Teaching load updated.'
        ]);

    } catch (Throwable $e) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

	if ($action === 'import_section_students') {

    $assignment_id = (int)($_POST['assignment_id'] ?? 0);
    $ayId = current_ay_id($pdo);

    try {

        $rosterStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM student_section ss
            INNER JOIN teaching_assignments ta
                ON ta.sectionID = ss.sectionID
            WHERE ta.assignment_id = ?
              AND ss.ay_id = ?
        ");
        $rosterStmt->execute([$assignment_id, $ayId]);
        $eligibleStudents = (int)$rosterStmt->fetchColumn();

        $importStmt = $pdo->prepare("
            INSERT INTO student_assignments
            (
                st_id,
                assignment_id,
                ay_id,
                enrolled_at
            )
            SELECT
                ss.st_id,
                ta.assignment_id,
                ?,
                NOW()
            FROM student_section ss
            INNER JOIN teaching_assignments ta
                ON ta.sectionID = ss.sectionID
            LEFT JOIN student_assignments sa
                ON sa.st_id = ss.st_id
               AND sa.assignment_id = ta.assignment_id
               AND sa.ay_id = ?
            WHERE ta.assignment_id = ?
              AND ss.ay_id = ?
              AND sa.enrollment_id IS NULL
        ");

        $importStmt->execute([
            $ayId,
            $ayId,
            $assignment_id,
            $ayId
        ]);

        $imported = $importStmt->rowCount();
        $skipped = max(0, $eligibleStudents - $imported);

        echo json_encode([
            'success' => true,
            'message' => "Imported {$imported} students. Skipped {$skipped} already enrolled."
        ]);

    } catch (Throwable $e) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}
	
	if ($action === 'get_subjects') {

    $sectionID = (int)($_POST['sectionID'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT
            s.sub_id,
            s.sub_code,
            s.sub_name
        FROM section_subjects ss
        JOIN subject s
            ON s.sub_id = ss.sub_id
        WHERE ss.sectionID = ?
          AND s.is_active = 1
        ORDER BY s.sub_code
    ");

    $stmt->execute([$sectionID]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}

	if ($action === 'get_instructors') {

    $sectionID = (int)($_POST['sectionID'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT
            i.inst_id,
            i.inst_name
        FROM section_instructors si
        JOIN instructor i
            ON i.inst_id = si.inst_id
        INNER JOIN users u
            ON u.inst_id = i.inst_id
           AND u.is_active = 1
        WHERE si.sectionID = ?
        ORDER BY i.inst_name
    ");

    $stmt->execute([$sectionID]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}

}

$loads = $pdo->query("
		SELECT
        ta.assignment_id,
        ta.sectionID,
        ta.sub_id,
        ta.inst_id,
        ta.start_time,
        ta.end_time,
        ta.is_active,

        s.section,

        sub.sub_code,
        sub.sub_name,

        i.inst_name

    FROM teaching_assignments ta

    JOIN section s
        ON s.sectionID = ta.sectionID

    JOIN subject sub
        ON sub.sub_id = ta.sub_id

    JOIN instructor i
        ON i.inst_id = ta.inst_id

    ORDER BY
        s.section,
        sub.sub_code
	")->fetchAll();

	$sections = $pdo->query("
    SELECT sectionID, section
    FROM section
    ORDER BY section
	")->fetchAll();

	$subjects = $pdo->query("
    SELECT sub_id, sub_code, sub_name
    FROM subject
    WHERE is_active = 1
    ORDER BY sub_code
	")->fetchAll();

	$instructors = $pdo->query("
    SELECT i.inst_id, i.inst_name
    FROM instructor i
    INNER JOIN users u
        ON u.inst_id = i.inst_id
       AND u.is_active = 1
    ORDER BY i.inst_name
	")->fetchAll();



?>