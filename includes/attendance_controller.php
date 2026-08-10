<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('edit_attendance');
$pdo = getConnection();

$currentUser = current_user();
$ownedSectionIds = current_user_owned_section_ids($pdo);
$isInstructorScoped = in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true);

function attendanceSectionScope(array $ownedSectionIds, bool $isInstructorScoped): array {
  if (!$isInstructorScoped) {
    return ['sql' => '', 'params' => []];
  }

  if (empty($ownedSectionIds)) {
    return ['sql' => ' AND 1=0', 'params' => []];
  }

  $placeholders = [];
  $params = [];
  foreach ($ownedSectionIds as $index => $sectionId) {
    $key = ':sec' . $index;
    $placeholders[] = $key;
    $params[$key] = $sectionId;
  }

  return [
    'sql' => ' AND ss.sectionID IN (' . implode(',', $placeholders) . ')',
    'params' => $params,
  ];
}

$sectionScope = attendanceSectionScope($ownedSectionIds, $isInstructorScoped);

function getTeachingAssignmentStartTime(PDO $pdo, int $assignmentId): ?string {
  $stmt = $pdo->prepare("
    SELECT start_time
    FROM teaching_assignments
    WHERE assignment_id = ?
    LIMIT 1
  ");
  $stmt->execute([$assignmentId]);
  $startTime = $stmt->fetchColumn();
  return $startTime !== false ? $startTime : null;
}

function resolveAttendanceTimeIn(PDO $pdo, int $assignmentId, string $status, ?string $timeIn, string $date): array {
  $startTime = getTeachingAssignmentStartTime($pdo, $assignmentId);

  if ($status === 'Absent') {
    return ['time_in' => null, 'time_override' => 0, 'late_minutes' => null];
  }

  if ($status === 'Late') {
    $time = trim((string)$timeIn);
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
      throw new InvalidArgumentException('Late attendance requires a valid time.');
    }

    $entered = new DateTime($date . ' ' . $time . ':00');
    $lateMinutes = 0;

    if ($startTime !== null && trim((string)$startTime) !== '') {
      $scheduled = new DateTime($date . ' ' . $startTime);
      $diffSeconds = $entered->getTimestamp() - $scheduled->getTimestamp();
      $lateMinutes = max(0, (int)floor($diffSeconds / 60));
    }

    return [
      'time_in' => $entered->format('Y-m-d H:i:s'),
      'time_override' => 1,
      'late_minutes' => $lateMinutes,
    ];
  }

  return [
    'time_in' => date('Y-m-d H:i:s'),
    'time_override' => 0,
    'late_minutes' => 0,
  ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // Load all students
    if ($action === 'load_all') {
      $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename) AS FullName,
            sec.section
            FROM student s
            INNER JOIN student_section ss ON ss.st_id=s.st_id
            INNER JOIN section sec ON sec.sectionID=ss.sectionID
        WHERE 1=1{$sectionScope['sql']}
        ORDER BY sec.section, s.st_lastname");
      $stmt->execute($sectionScope['params']);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Search by section
    if ($action === 'search_section') {
        $q = trim($_POST['q'] ?? '');
        $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_lastname,', ',IFNULL(s.st_name,''),' ',s.st_middlename) AS FullName,
            sec.section
            FROM student s
            INNER JOIN student_section ss ON s.st_id=ss.st_id
            INNER JOIN section sec ON ss.sectionID=sec.sectionID
          WHERE (:empty='' OR sec.section LIKE :prefix)
          {$sectionScope['sql']}
            ORDER BY sec.section, s.st_lastname");
        $stmt->execute(array_merge([':empty'=>$q, ':prefix'=>$q.'%'], $sectionScope['params']));
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Search by name
    if ($action === 'search_name') {
        $q = trim($_POST['q'] ?? '');
        $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_name,' ',s.st_lastname) AS FullName,
            sec.section
            FROM student s
            INNER JOIN student_section ss ON s.st_id=ss.st_id
            INNER JOIN section sec ON ss.sectionID=sec.sectionID
          WHERE (:empty='' OR s.st_name LIKE :pat OR s.st_lastname LIKE :pat2)
          {$sectionScope['sql']}
            ORDER BY s.st_lastname");
        $stmt->execute(array_merge([':empty'=>$q, ':pat'=>"%$q%", ':pat2'=>"%$q%"], $sectionScope['params']));
        echo json_encode($stmt->fetchAll());
        exit;
    }
// Load teaching loads
if ($action === 'load_teaching_loads') {

    $ayId = current_ay_id($pdo);

    $stmt = $pdo->prepare("
        SELECT
            ta.assignment_id,
            ta.sectionID,
            sec.section,
            subj.sub_code,
            subj.sub_name,
            i.inst_name

        FROM teaching_assignments ta

        INNER JOIN section sec
            ON sec.sectionID = ta.sectionID

        INNER JOIN subject subj
            ON subj.sub_id = ta.sub_id

        INNER JOIN instructor i
			ON i.inst_id = ta.inst_id

        WHERE ta.is_active = 1
          AND ta.ay_id = ?

        ORDER BY
            sec.section,
            subj.sub_code
    ");

    $stmt->execute([$ayId]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}

if ($action === 'load_students_by_assignment') {

    $assignmentId =
        (int)($_POST['assignment_id'] ?? 0);

    $ayId =
        current_ay_id($pdo);

    $stmt = $pdo->prepare("
        SELECT
            s.st_id AS ID,
            s.student_no,

            ta.sectionID AS SecID,

            ta.assignment_id,

            CONCAT(
                s.st_lastname,
                ', ',
                s.st_name,
                ' ',
                IFNULL(
                    s.st_middlename,
                    ''
                )
            ) AS FullName,

            sec.section

        FROM teaching_assignments ta

        INNER JOIN section sec
            ON sec.sectionID = ta.sectionID

        INNER JOIN student_assignments sa
            ON sa.assignment_id = ta.assignment_id

        INNER JOIN student s
            ON s.st_id = sa.st_id

        WHERE
            ta.assignment_id = ?
            AND sa.ay_id = ?

        ORDER BY
            s.st_lastname,
            s.st_name
    ");

    $stmt->execute([
        $assignmentId,
        $ayId
    ]);

    echo json_encode(
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        )
    );

    exit;
}

if ($action === 'load_existing') {

    $assignmentId = (int)$_POST['assignment_id'];
    $date         = $_POST['date'];
    $term         = $_POST['term'];

    $stmt = $pdo->prepare("
        SELECT
            attendance.Att_ID,
            attendance.st_id,
            attendance.status,
			attendance.assignment_id,
            attendance.time_in,
            student.st_lastname,
            student.st_name,
            student.st_middlename,
            student.st_suffix
        FROM attendance
        INNER JOIN student
            ON student.st_id = attendance.st_id
        WHERE attendance.assignment_id = ?
        AND attendance._date = ?
        AND attendance.term = ?
        ORDER BY student.st_lastname
    ");

    $stmt->execute([
        $assignmentId,
        $date,
        $term
    ]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}
    // Save attendance
   if ($action === 'save') {

    $records = json_decode($_POST['records'], true);
    $date    = $_POST['date'];
    $term    = $_POST['term'];
    $ayId    = current_ay_id($pdo);

    // Prevent duplicate attendance for same load/date/term
    if (!empty($records)) {

        $chk = $pdo->prepare("
            SELECT COUNT(*)
            FROM attendance
            WHERE assignment_id = ?
              AND _date = ?
              AND term = ?
        ");

        $chk->execute([
            $records[0]['assignment_id'],
            $date,
            $term
        ]);

        if ($chk->fetchColumn() > 0) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Attendance already exists for this date and term. Use Update instead.'
            ]);

            exit;
        }
    }

    try {

        $pdo->beginTransaction();

        $ins = $pdo->prepare("
            INSERT INTO attendance
            (
                st_id,
                sectionID,
                assignment_id,
                _date,
                status,
                term,
                ay_id,
                time_in,
                time_override,
                late_minutes
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        foreach ($records as $r) {
            $attendanceTime = resolveAttendanceTimeIn(
                $pdo,
                (int)$r['assignment_id'],
                $r['status'],
                $r['time_in'] ?? null,
                $date
            );

            $ins->execute([
                $r['st_id'],
                $r['sectionID'],
                $r['assignment_id'],
                $date,
                $r['status'],
                $term,
                $ayId,
                $attendanceTime['time_in'],
                $attendanceTime['time_override'],
                $attendanceTime['late_minutes']
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Attendance saved successfully!'
        ]);

    } catch (Exception $e) {

        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' =>
                'Error saving attendance: ' .
                $e->getMessage()
        ]);
    }

    exit;
}


    // Update attendance
   if ($action === 'update') {
    $records = json_decode($_POST['records'], true);
    $date = $_POST['date'] ?? '';
    $term = $_POST['term'] ?? '';

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare("
    // Update attendance
            SET status = ?,
                time_in = ?,
                time_override = ?,
                late_minutes = ?
            WHERE st_id = ?
              AND assignment_id = ?
              AND _date = ?
              AND term = ?
        ");

        foreach ($records as $r) {
            $attendanceTime = resolveAttendanceTimeIn(
                $pdo,
                (int)$r['assignment_id'],
                $r['status'],
                $r['time_in'] ?? null,
                $date
            );

            $upd->execute([
                $r['status'],
                $attendanceTime['time_in'],
                $attendanceTime['time_override'],
                $attendanceTime['late_minutes'],
                $r['st_id'],
                $r['assignment_id'],
                $date,
                $term
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Attendance updated successfully!'
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error updating attendance: ' . $e->getMessage()
        ]);
	 }
			exit;
  }
}

// Request handling and attendance write operations are kept in this controller.
