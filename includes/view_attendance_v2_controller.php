<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_attendance');
$pdo = getConnection();

$currentUser = current_user();
$isInstructorScoped = in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true);
$ownedSectionIds = current_user_owned_section_ids($pdo);

function viewAttendanceSectionScope(array $ownedSectionIds, bool $isInstructorScoped): array {
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
    'sql' => ' AND section.sectionID IN (' . implode(',', $placeholders) . ')',
    'params' => $params,
  ];
}

$sectionScope = viewAttendanceSectionScope($ownedSectionIds, $isInstructorScoped);
$sectionsSql = "
SELECT
    section.sectionID,
    section.section,
    course.course_acronym
FROM section
INNER JOIN course
    ON course.course_id = section.course_id
";

if ($isInstructorScoped) {
  if (empty($ownedSectionIds)) {
    $sectionsSql .= " WHERE 1=0";
  } else {
    $sectionsSql .= " WHERE section.sectionID IN (" . implode(',', array_fill(0, count($ownedSectionIds), '?')) . ")";
  }
}
$sectionsSql .= " ORDER BY section.section";
$sectionsStmt = $pdo->prepare($sectionsSql);
if ($isInstructorScoped && !empty($ownedSectionIds)) {
  $sectionsStmt->execute($ownedSectionIds);
} else {
  $sectionsStmt->execute();
}
$sections = $sectionsStmt->fetchAll();
$terms = ['Prelim','Midterm','Pre-Finals','Finals'];

$loads = $pdo->query("
    SELECT
        ta.assignment_id,
        sec.section,
        subj.sub_code,
        i.inst_name

    FROM teaching_assignments ta

    INNER JOIN section sec
        ON sec.sectionID = ta.sectionID

    INNER JOIN subject subj
        ON subj.sub_id = ta.sub_id

    INNER JOIN instructor i
    ON i.inst_id = ta.inst_id

    WHERE ta.is_active = 1

    ORDER BY
        sec.section,
        subj.sub_code
")->fetchAll();


$filter_term  = $_GET['term']      ?? '';
$filter_from  = $_GET['date_from'] ?? '';
$filter_to    = $_GET['date_to']   ?? '';
$filter_name  = trim($_GET['name'] ?? '');
$filter_assignment = $_GET['assignment_id'] ?? '';

$page   = max(1, intval($_GET['p'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
/*
if ($filter_sec)  { $where[] = 'section.sectionID=:sec';   $params[':sec']  = $filter_sec; }*/
if ($filter_assignment) {
    $where[] =
        'attendance.assignment_id = :assignment_id';
    $params[':assignment_id'] =
        $filter_assignment;
}
if ($filter_term) { $where[] = 'attendance.term=:term';    $params[':term'] = $filter_term; }
if ($filter_from) { $where[] = 'attendance._date>=:dfrom'; $params[':dfrom'] = $filter_from; }
if ($filter_to)   { $where[] = 'attendance._date<=:dto';   $params[':dto']   = $filter_to; }
if ($filter_name) {
    // Search full name - last, first, middle, suffix
    $where[] = "(CONCAT(student.st_lastname,' ',student.st_name,' ',student.st_middlename,' ',student.st_suffix) LIKE :name
             OR CONCAT(student.st_name,' ',student.st_middlename,' ',student.st_lastname) LIKE :name2
             OR CONCAT(student.st_lastname,', ',student.st_name) LIKE :name3)";
    $params[':name']  = "%$filter_name%";
    $params[':name2'] = "%$filter_name%";
    $params[':name3'] = "%$filter_name%";
}

  if ($sectionScope['sql'] !== '') {
    $where[] = ltrim($sectionScope['sql'], ' AND');
    $params = array_merge($params, $sectionScope['params']);
  }

$whereStr = implode(' AND ', $where);

// Count
	$records = [];
	$total = 0;
	$totalPages = 1;
	$hasSearch = !empty($filter_assignment);
	
    if ($hasSearch) {
	$countStmt = $pdo->prepare("SELECT COUNT(DISTINCT CONCAT(student.st_id,'_',section.sectionID,'_',attendance.term))
    FROM student
    INNER JOIN attendance ON student.st_id=attendance.st_id
    INNER JOIN section ON section.sectionID=attendance.sectionID
    WHERE $whereStr");
	$countStmt->execute($params);
	$total      = $countStmt->fetchColumn();
	$totalPages = max(1, ceil($total / $limit));
	$page       = min($page, $totalPages);
	
// Main query - grouped per student per section per term
// Present count x 3 = total hours
$stmt = $pdo->prepare("SELECT
    student.st_id,
    student.student_no,
    section.sectionID,
    CONCAT(student.st_lastname,', ',student.st_name,' ',student.st_middlename,' ',IF(student.st_suffix='','',student.st_suffix)) AS NAME,
    section.section AS Section,
	subj.sub_code AS Subject,
	i.inst_name AS Instructor,
    attendance.term AS Term,
    COUNT(CASE WHEN attendance.status='Present' THEN 1 END) AS Present,
    COUNT(CASE WHEN attendance.status='Absent'  THEN 1 END) AS Absent,
    COUNT(CASE WHEN attendance.status='Late' THEN 1 END) AS Late,
    ROUND(SUM(CASE
        WHEN attendance.status = 'Present' THEN
            TIME_TO_SEC(TIMEDIFF(ta.end_time, ta.start_time)) / 3600.0
        WHEN attendance.status = 'Late' THEN
            GREATEST(
                TIME_TO_SEC(TIMEDIFF(ta.end_time, ta.start_time)) / 60.0
                - COALESCE(
                    attendance.late_minutes,
                    TIMESTAMPDIFF(MINUTE, CONCAT(attendance._date, ' ', ta.start_time), attendance.time_in)
                ),
                0
            ) / 60.0
        ELSE 0
    END), 2) AS TotalHours,
    MIN(attendance._date) AS DateFrom,
    MAX(attendance._date) AS DateTo
FROM student
INNER JOIN attendance ON student.st_id=attendance.st_id
INNER JOIN section ON section.sectionID=attendance.sectionID
INNER JOIN teaching_assignments ta
    ON ta.assignment_id =
       attendance.assignment_id
INNER JOIN subject subj
    ON subj.sub_id =
       ta.sub_id
INNER JOIN instructor i
    ON i.inst_id = ta.inst_id
WHERE $whereStr
GROUP BY student.st_id, section.sectionID, attendance.term
ORDER BY student.st_lastname ASC, attendance.term ASC
LIMIT :limit OFFSET :offset");

	foreach ($params as $k => $v) $stmt->bindValue($k, $v);
	$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
	$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
	$stmt->execute();
	$records = $stmt->fetchAll();
}
?>
