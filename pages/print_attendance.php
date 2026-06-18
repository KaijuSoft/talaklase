<?php
require_once __DIR__ . '/../includes/db.php';
<<<<<<< HEAD
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_attendance');
=======
>>>>>>> dad965eae0886277347cae4c6fc181143c8fa104
$pdo = getConnection();

$sections = $pdo->query("SELECT section.sectionID, section.section, course.course_acronym FROM section INNER JOIN course ON course.course_id=section.course_id ORDER BY section.section")->fetchAll();
$terms    = ['Prelim','Midterm','Pre-Finals','Finals'];

$filter_sec  = $_GET['sec']  ?? '';
$filter_term = $_GET['term'] ?? '';
$filter_name = $_GET['name'] ?? '';

$records = [];
if ($filter_sec || $filter_term) {
    $where  = ['1=1'];
    $params = [];
    if ($filter_sec)  { $where[] = 'section.sectionID=:sec';  $params[':sec']  = $filter_sec; }
    if ($filter_term) { $where[] = 'attendance.term=:term';   $params[':term'] = $filter_term; }
    if ($filter_name) { $where[] = 'student.st_lastname LIKE :name'; $params[':name'] = "%$filter_name%"; }
    $whereStr = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT
        student.st_id,
        CONCAT(student.st_lastname,', ',student.st_name,' ',student.st_middlename,' ',student.st_suffix) AS NAME,
        section.section AS Section,
        attendance.term AS Term,
        COUNT(CASE WHEN attendance.status='Present' THEN 1 END) AS Present,
        COUNT(CASE WHEN attendance.status='Absent'  THEN 1 END) AS Absent,
        COUNT(CASE WHEN attendance.status='Late'    THEN 1 END) AS Late,
        COUNT(*) AS Total
    FROM student
    INNER JOIN attendance    ON student.st_id=attendance.st_id
    INNER JOIN section       ON section.sectionID=attendance.sectionID
    WHERE $whereStr
    GROUP BY student.st_id, Section, Term
    ORDER BY student.st_lastname");
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}
?>

<div class="card mb-3">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-printer-fill me-2 text-primary"></i>Print Attendance Summary</h6>
    <?php if ($records): ?>
      <button class="btn btn-sm btn-outline-primary" onclick="window.open('print.php?type=print_attendance&sec=<?= urlencode($filter_sec) ?>&term=<?= urlencode($filter_term) ?>&name=<?= urlencode($filter_name) ?>','_blank')">
        <i class="bi bi-printer me-1"></i> Print
      </button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="GET" class="row g-2 mb-3 no-print">
      <input type="hidden" name="page" value="print_attendance"/>
      <div class="col-md-3">
        <select class="form-select form-select-sm" name="sec">
          <option value="">All Sections</option>
          <?php foreach ($sections as $s): ?>
            <option value="<?= $s['sectionID'] ?>" <?= $filter_sec==$s['sectionID']?'selected':'' ?>><?= htmlspecialchars($s['section']) ?> (<?= $s['course_acronym'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select form-select-sm" name="term">
          <option value="">All Terms</option>
          <?php foreach ($terms as $t): ?><option <?= $filter_term===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control form-control-sm" name="name" placeholder="Search last name…" value="<?= htmlspecialchars($filter_name) ?>"/>
      </div>
      <div class="col-md-3 d-flex gap-1">
        <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search me-1"></i>Search</button>
        <a href="?page=print_attendance" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i></a>
      </div>
    </form>

    <?php if (empty($records) && ($filter_sec || $filter_term)): ?>
      <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No attendance records found for the selected filters.</div>
    <?php elseif ($records): ?>
      <!-- Print header -->
      <div class="print-header d-none d-print-block mb-3 text-center">
        <h4 class="mb-0">TalaKlase – Attendance Summary Report</h4>
        <small>
          <?php if ($filter_sec) { foreach($sections as $s) { if($s['sectionID']==$filter_sec) echo "Section: ".$s['section']." &nbsp;|&nbsp;"; } } ?>
          <?php if ($filter_term) echo "Term: $filter_term &nbsp;|&nbsp;"; ?>
          Generated: <?= date('F j, Y') ?>
        </small>
        <hr/>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>#</th>
              <th>Student Name</th>
              <th>Section</th>
              <th>Term</th>
              <th class="text-success">Present</th>
              <th class="text-danger">Absent</th>
              <th class="text-warning">Late</th>
              <th>Total Days</th>
              <th>Attendance %</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $r):
              $pct = $r['Total'] > 0 ? round(($r['Present'] / $r['Total']) * 100, 1) : 0;
              $pctClass = $pct >= 80 ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger');
            ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['NAME']) ?></td>
                <td><?= htmlspecialchars($r['Section']) ?></td>
                <td><?= htmlspecialchars($r['Term']) ?></td>
                <td class="text-center text-success fw-bold"><?= $r['Present'] ?></td>
                <td class="text-center text-danger fw-bold"><?= $r['Absent'] ?></td>
                <td class="text-center text-warning fw-bold"><?= $r['Late'] ?></td>
                <td class="text-center"><?= $r['Total'] ?></td>
                <td class="text-center fw-bold <?= $pctClass ?>"><?= $pct ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="table-secondary fw-bold">
              <td colspan="4">Totals</td>
              <td class="text-center text-success"><?= array_sum(array_column($records,'Present')) ?></td>
              <td class="text-center text-danger"><?= array_sum(array_column($records,'Absent')) ?></td>
              <td class="text-center text-warning"><?= array_sum(array_column($records,'Late')) ?></td>
              <td class="text-center"><?= array_sum(array_column($records,'Total')) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-funnel fs-1 mb-3 d-block"></i>
        Select a section or term above to generate the attendance summary.
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
@media print {
  #sidebar, .top-bar, .no-print { display: none !important; }
  #page-content { margin-left: 0 !important; }
  .card { border: none !important; box-shadow: none !important; }
  .card-header { border-bottom: 2px solid #000 !important; }
  body { font-size: 12px; }
}
</style>
