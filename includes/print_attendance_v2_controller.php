<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_attendance');
$pdo = getConnection();

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

$terms             = ['Prelim', 'Midterm', 'Pre-Finals', 'Finals'];
$filter_assignment = $_GET['assignment_id'] ?? '';
$filter_term       = $_GET['term']          ?? '';
$filter_name       = $_GET['name']          ?? '';

$records = [];
if ($filter_assignment) {
    $where  = ['attendance.assignment_id = :assignment_id'];
    $params = [':assignment_id' => $filter_assignment];

    if ($filter_term) { $where[] = 'attendance.term = :term';             $params[':term'] = $filter_term; }
    if ($filter_name) { $where[] = 'student.st_lastname LIKE :name';      $params[':name'] = "%$filter_name%"; }

    $whereStr = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT
            student.st_id,
            student.student_no,
            CONCAT(student.st_lastname, ', ', student.st_name, ' ', student.st_middlename, ' ', student.st_suffix) AS NAME,
            section.section AS Section,
            attendance.term AS Term,
            COUNT(CASE WHEN attendance.status = 'Present' THEN 1 END) AS Present,
            COUNT(CASE WHEN attendance.status = 'Absent'  THEN 1 END) AS Absent,
            COUNT(CASE WHEN attendance.status = 'Late'    THEN 1 END) AS Late,
            COUNT(*) AS Total
        FROM student
        INNER JOIN attendance ON student.st_id = attendance.st_id
        INNER JOIN section    ON section.sectionID = attendance.sectionID
        WHERE $whereStr
        GROUP BY student.st_id, Section, Term
        ORDER BY student.st_lastname
    ");
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>