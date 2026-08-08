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
    // Search full name — last, first, middle, suffix
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
	
// Main query — grouped per student per section per term
// Present count × 3 = Total Hours
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

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-eye-fill me-2 text-primary"></i>View Attendance</h6>
  <button
    class="btn btn-sm btn-outline-secondary"
    onclick="window.open('index.php?page=print_attendance_v2&assignment_id=<?= urlencode($filter_assignment) ?>&term=<?= urlencode($filter_term) ?>&name=<?= urlencode($filter_name) ?>','_blank')"
    <i class="bi bi-printer-fill me-1"></i> Print
	</button>
  </div>

  <div class="card-body">

    <!-- Filters -->
    <form method="GET" class="mb-3">
      <input type="hidden" name="page" value="view_attendance_v2"/>
      <div class="row g-2">
	<div class="col-md-3">

	<label class="form-label">
    Teaching Load
	</label>

	<select
      class="form-select form-select-sm"
      name="assignment_id">

      <option value="">
          All Teaching Loads
      </option>

      <?php foreach ($loads as $l): ?>

      <option
          value="<?= $l['assignment_id'] ?>"
          <?= $filter_assignment == $l['assignment_id']
                ? 'selected'
                : '' ?>>

          <?= htmlspecialchars(
              $l['section']
              . ' - '
              . $l['sub_code']
              . ' ('
              . $l['inst_name']
              . ')'
          ) ?>

      </option>

      <?php endforeach; ?>

  </select>

</div>
        <div class="col-md-2">
          <label class="form-label">Term</label>
          <select class="form-select form-select-sm" name="term">
            <option value="">All Terms</option>
            <?php foreach ($terms as $t): ?>
              <option <?= $filter_term===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Date From</label>
          <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($filter_from) ?>"/>
        </div>

        <div class="col-md-2">
          <label class="form-label">Date To</label>
          <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($filter_to) ?>"/>
        </div>

        <div class="col-md-3">
          <label class="form-label">Student Name</label>
		  <?php if ($filter_assignment): ?>

		<?php foreach ($loads as $l): ?>

		<?php if ($l['assignment_id'] == $filter_assignment): ?>

		<span class="badge bg-primary-subtle text-primary border">

		<i class="bi bi-book me-1"></i>

		<?= htmlspecialchars(
        $l['section']
        . ' - '
        . $l['sub_code']
    ) ?>

	</span>

<?php endif; ?>

<?php endforeach; ?>

<?php endif; ?>
          <input type="text" class="form-control form-control-sm" name="name"
            placeholder="Search full name…"
            value="<?= htmlspecialchars($filter_name) ?>"/>
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary btn-sm">
            <i class="bi bi-search me-1"></i> Search
          </button>
          <a href="?page=view_attendance_v2" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x me-1"></i> Clear
          </a>
        </div>

      </div>
    </form>

    <!-- Active filters display -->
    <?php if ($filter_term || $filter_from || $filter_to || $filter_name): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php if ($filter_name): ?>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
          <i class="bi bi-person me-1"></i><?= htmlspecialchars($filter_name) ?>
        </span>
      <?php endif; ?>
      <?php if ($filter_term): ?>
        <span class="badge bg-info-subtle text-info border border-info-subtle">
          <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($filter_term) ?>
        </span>
      <?php endif; ?>
      <?php if ($filter_from || $filter_to): ?>
        <span class="badge bg-success-subtle text-success border border-success-subtle">
          <i class="bi bi-calendar-range me-1"></i>
          <?= $filter_from ?: '…' ?> → <?= $filter_to ?: '…' ?>
        </span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0">
        <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Student No.</th>
          <th>Section</th>
			<th>Subject</th>
			<th>Instructor</th>
            <th>Term</th>
            <th class="text-center">Present</th>
            <th class="text-center">Absent</th>
            <th class="text-center">Late</th>
            <th class="text-center">Hours Rendered</th>
            <th class="text-center">Date Range</th>
          </tr>
        </thead>
        <tbody>
         <?php if (!$hasSearch): ?>
	<tr>
		<td colspan="11"
			class="text-center py-5 text-muted">
			Select a Teaching Load and click Search.
		</td>
	</tr>
	<?php elseif (empty($records)): ?>
	<tr>
		<td colspan="11"
        class="text-center py-5 text-muted">
        No records found.
    </td>
		</tr>
            <tr>
              
              </td>
            </tr>
          <?php else: foreach ($records as $i => $r): ?>
          <tr>
              <td class="text-muted"><?= $offset + $i + 1 ?></td>
              <td><?= htmlspecialchars($r['NAME']) ?></td>
              <td><?= htmlspecialchars($r['student_no']) ?></td>
              <td><?= htmlspecialchars($r['Section']) ?></td>
			  <td><?= htmlspecialchars($r['Subject']) ?></td>
				<td><?= htmlspecialchars($r['Instructor']) ?></td>
              <td>
                <span class="badge bg-secondary-subtle text-secondary border">
                  <?= htmlspecialchars($r['Term']) ?>
                </span>
              </td>
              <td class="text-center">
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                  <?= $r['Present'] ?>
                </span>
              </td>
              <td class="text-center">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                  <?= $r['Absent'] ?>
                </span>
              </td>
              <td class="text-center">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                  <?= $r['Late'] ?>
                </span>
              </td>
              <td class="text-center fw-bold text-primary">
                <?= $r['TotalHours'] ?> hrs
              </td>
              <td class="text-center text-muted" style="font-size:0.8rem">
                <?= $r['DateFrom'] ?> → <?= $r['DateTo'] ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex align-items-center justify-content-between mt-3">
      <small class="text-muted">
        Page <?= $page ?> of <?= $totalPages ?> &mdash; <?= $total ?> records
      </small>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="?page=view_attendance_v2&p=<?= $page-1 ?>&assignment_id=<?= urlencode($filter_assignment) ?>&term=<?= urlencode($filter_term) ?>&date_from=<?= urlencode($filter_from) ?>&date_to=<?= urlencode($filter_to) ?>&name=<?= urlencode($filter_name) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
            <a class="page-link" href="?page=view_attendance_v2&p=<?= $page+1 ?>&assignment_id=<?= urlencode($filter_assignment) ?>&term=<?= urlencode($filter_term) ?>&date_from=<?= urlencode($filter_from) ?>&date_to=<?= urlencode($filter_to) ?>&name=<?= urlencode($filter_name) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>

  </div>
</div>
