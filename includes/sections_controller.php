<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_sections');

$pdo = getConnection();
$currentUser = current_user();
$canManageAllSections = can('manage_users');
$currentInstId = (int)($currentUser['inst_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!verify_csrf()) {
        echo json_encode(['success' => false, 'message' => 'Invalid session token. Refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sectionName = trim($_POST['section'] ?? '');
        $courseId = trim($_POST['course_id'] ?? '');

        if ($sectionName === '' || $courseId === '') {
            echo json_encode(['success' => false, 'message' => 'Section name and course are required.']);
            exit;
        }

        $ownerInstId = null;
        if ($canManageAllSections) {
            $ownerInstId = trim($_POST['inst_id'] ?? '');
            $ownerInstId = $ownerInstId === '' ? null : (int)$ownerInstId;
        } elseif (in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true)) {
            $ownerInstId = $currentInstId ?: null;
        }

        try {
			$ayId = current_ay_id($pdo);
            $stmt = $pdo->prepare('INSERT INTO section (section, course_id, inst_id, ay_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$sectionName, $courseId, $ownerInstId, $ayId]);
			$sectionId = $pdo->lastInsertId();

$instructors = $_POST['instructors'] ?? [];

if (!is_array($instructors)) {
    $instructors = [$instructors];
}

foreach ($instructors as $instId) {
    $stmt = $pdo->prepare("
        INSERT INTO section_instructors
        (sectionID, inst_id)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $sectionId,
        $instId
    ]);
}

			$subjects = $_POST['subjects'] ?? [];

				if (!is_array($subjects)) {
					$subjects = [$subjects];
}

			$subjects = array_filter($subjects);
			$subjects = array_unique($subjects);
			


				foreach ($subjects as $subId) {

					$stmt = $pdo->prepare("
					INSERT INTO section_subjects
						(sectionID, sub_id)
						VALUES (?, ?)
					");

    $stmt->execute([
        $sectionId,
        $subId
    ]);
}
			
            echo json_encode(['success' => true, 'message' => 'Section created.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $sectionId = (int)($_POST['sectionID'] ?? 0);
        $sectionName = trim($_POST['section'] ?? '');
        $courseId = trim($_POST['course_id'] ?? '');

        if ($sectionId <= 0 || $sectionName === '' || $courseId === '') {
            echo json_encode(['success' => false, 'message' => 'Section name and course are required.']);
            exit;
        }

        $rowStmt = $pdo->prepare('SELECT sectionID, inst_id FROM section WHERE sectionID = ? LIMIT 1');
        $rowStmt->execute([$sectionId]);
        $row = $rowStmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Section not found.']);
            exit;
        }

        if (!$canManageAllSections && (int)($row['inst_id'] ?? 0) !== $currentInstId) {
            echo json_encode(['success' => false, 'message' => 'You can only edit sections you created.']);
            exit;
        }

        $ownerInstId = (int)($row['inst_id'] ?? 0) ?: null;
        if ($canManageAllSections) {
            $ownerRaw = trim($_POST['inst_id'] ?? '');
            $ownerInstId = $ownerRaw === '' ? null : (int)$ownerRaw;
        } elseif (in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true)) {
            $ownerInstId = $currentInstId ?: null;
        }

        try {
            $stmt = $pdo->prepare('UPDATE section SET section = ?, course_id = ?, inst_id = ? WHERE sectionID = ?');
            $stmt->execute([$sectionName, $courseId, $ownerInstId, $sectionId]);
			
// Remove old instructor assignments
$pdo->prepare(
    "DELETE FROM section_instructors
     WHERE sectionID = ?"
)->execute([$sectionId]);

// Remove old subject assignments
$pdo->prepare(
    "DELETE FROM section_subjects
     WHERE sectionID = ?"
)->execute([$sectionId]);

// Add new subject assignments
$subjects = $_POST['subjects'] ?? [];

if (!is_array($subjects)) {
    $subjects = [$subjects];
}

$subjects = array_filter($subjects);
$subjects = array_unique($subjects);



foreach ($subjects as $subId) {

    $stmt = $pdo->prepare(
        "INSERT INTO section_subjects
         (sectionID, sub_id)
         VALUES (?, ?)"
    );

    $stmt->execute([
        $sectionId,
        $subId
    ]);
}

// Add new instructor assignments
$instructors = $_POST['instructors'] ?? [];

if (!is_array($instructors)) {
    $instructors = [$instructors];
}

foreach ($instructors as $instId) {

    $stmt = $pdo->prepare(
        "INSERT INTO section_instructors
         (sectionID, inst_id)
         VALUES (?, ?)"
    );

    $stmt->execute([
        $sectionId,
        $instId
    ]);
}
            echo json_encode(['success' => true, 'message' => 'Section updated.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $sectionId = (int)($_POST['sectionID'] ?? 0);
        if ($sectionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid section.']);
            exit;
        }

        $rowStmt = $pdo->prepare('SELECT sectionID, inst_id FROM section WHERE sectionID = ? LIMIT 1');
        $rowStmt->execute([$sectionId]);
        $row = $rowStmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Section not found.']);
            exit;
        }

        if (!$canManageAllSections && (int)($row['inst_id'] ?? 0) !== $currentInstId) {
            echo json_encode(['success' => false, 'message' => 'You can only delete sections you created.']);
            exit;
        }

      try {

    $pdo->prepare(
        "DELETE FROM section_instructors
         WHERE sectionID = ?"
    )->execute([$sectionId]);

    $pdo->prepare(
        'DELETE FROM section
         WHERE sectionID = ?'
    )->execute([$sectionId]);

    echo json_encode([
        'success' => true,
        'message' => 'Section deleted.'
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

$courses = $pdo->query('SELECT course_id, course_acronym FROM course ORDER BY course_acronym')->fetchAll();
$instructors = $pdo->query(
    'SELECT i.inst_id, i.inst_name
     FROM instructor i
     INNER JOIN users u
        ON u.inst_id = i.inst_id
       AND u.is_active = 1
     ORDER BY i.inst_name'
)->fetchAll();
$subjects = $pdo->query(
    'SELECT sub_id, sub_code, sub_name
     FROM subject
     WHERE is_active = 1
     ORDER BY sub_code'
)->fetchAll();

$sectionSubjects = [];

$sectionInstructors = [];
$stmt = $pdo->query("SELECT sectionID, inst_id FROM section_instructors");
foreach ($stmt->fetchAll() as $row) {
    $sectionInstructors[$row["sectionID"]][] = (int)$row["inst_id"];
}

$stmt = $pdo->query("
    SELECT sectionID, sub_id
    FROM section_subjects
");

foreach ($stmt->fetchAll() as $row) {

    $sectionSubjects[$row['sectionID']][] =
        (int)$row['sub_id'];
}

if ($canManageAllSections) {
    $sectionsStmt = $pdo->query(
    "SELECT
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym,

        GROUP_CONCAT(
            i.inst_name
            ORDER BY i.inst_name
            SEPARATOR ', '
        ) AS inst_name

     FROM section s

     LEFT JOIN course c
        ON c.course_id = s.course_id

     LEFT JOIN section_instructors si
        ON si.sectionID = s.sectionID

     LEFT JOIN instructor i
        ON i.inst_id = si.inst_id

     GROUP BY
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym

     ORDER BY s.section"
);

$sections = $sectionsStmt->fetchAll();
} else {
    $ownedIds = current_user_owned_section_ids($pdo);
    if (empty($ownedIds)) {
        $sections = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($ownedIds), '?'));
        $sectionsStmt = $pdo->prepare(
    "SELECT
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym,

        GROUP_CONCAT(
            i.inst_name
            ORDER BY i.inst_name
            SEPARATOR ', '
        ) AS inst_name

     FROM section s

     LEFT JOIN course c
        ON c.course_id = s.course_id

     LEFT JOIN section_instructors si
        ON si.sectionID = s.sectionID

     LEFT JOIN instructor i
        ON i.inst_id = si.inst_id

     WHERE s.sectionID IN ($placeholders)

     GROUP BY
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym

     ORDER BY s.section"
);
	}
        $sectionsStmt->execute($ownedIds);
        $sections = $sectionsStmt->fetchAll();
    }

?>
