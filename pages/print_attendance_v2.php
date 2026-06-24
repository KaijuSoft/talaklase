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

<div class="card mb-3">
  <div class="card-header d-flex align-items-center justify-content-between no-print">
    <h6 class="mb-0"><i class="bi bi-printer-fill me-2 text-primary"></i>Print Attendance Summary</h6>
    <?php if ($records): ?>
      <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print
      </button>
    <?php endif; ?>
  </div>

  <div class="card-body">

    <!-- Filter Form -->
    <form method="GET" class="row g-2 mb-3 no-print">
      <?php if (isset($_GET['page'])): ?>
        <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
      <?php endif; ?>

      <div class="col-md-5">
        <label class="form-label small fw-semibold">Teaching Load</label>
        <select name="assignment_id" class="form-select form-select-sm" required>
          <option value="">-- Select Load --</option>
          <?php foreach ($loads as $l): ?>
            <option value="<?= $l['assignment_id'] ?>"
              <?= $filter_assignment == $l['assignment_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($l['section'] . ' - ' . $l['sub_code'] . ' (' . $l['inst_name'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-semibold">Term</label>
        <select name="term" class="form-select form-select-sm">
          <option value="">-- All Terms --</option>
          <?php foreach ($terms as $t): ?>
            <option value="<?= $t ?>" <?= $filter_term === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-semibold">Student Name</label>
        <input type="text" name="name" class="form-control form-control-sm"
               placeholder="Search by last name…"
               value="<?= htmlspecialchars($filter_name) ?>">
      </div>

      <div class="col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary w-100">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </form>
    <!-- /Filter Form -->

    <?php if (empty($records) && ($filter_assignment || $filter_term)): ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>No attendance records found for the selected filters.
      </div>

    <?php elseif ($records): ?>

      <!-- Print-only header -->
      <div class="print-header d-none d-print-block mb-3 text-center">
        <h4 class="mb-0">TalaKlase – Attendance Summary Report</h4>
        <div class="small">
          <?php
          foreach ($loads as $l) {
              if ($l['assignment_id'] == $filter_assignment) {
                  echo '<strong>Teaching Load:</strong> '
                     . htmlspecialchars(
                           $l['section'] . ' - ' . $l['sub_code'] . ' (' . $l['inst_name'] . ')'
                       );
                  break;
              }
          }
          ?>
          <br>
          <strong>Term:</strong> <?= htmlspecialchars($filter_term ?: 'All Terms') ?>
          <br>
          <strong>Generated:</strong> <?= date('F j, Y') ?>
        </div>
        <hr>
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
              $pct      = $r['Total'] > 0 ? round(($r['Present'] / $r['Total']) * 100, 1) : 0;
              $pctClass = $pct >= 80 ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger');
            ?>
              <tr>
                <td><?= $i + 1 ?></td>
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
              <td class="text-center text-success"><?= array_sum(array_column($records, 'Present')) ?></td>
              <td class="text-center text-danger"><?= array_sum(array_column($records, 'Absent')) ?></td>
              <td class="text-center text-warning"><?= array_sum(array_column($records, 'Late')) ?></td>
              <td class="text-center"><?= array_sum(array_column($records, 'Total')) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

    <?php else: ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-funnel fs-1 mb-3 d-block"></i>
        Select a teaching load above to generate the attendance summary.
      </div>
    <?php endif; ?>

  </div>
</div>

<style>
@media print {
  #sidebar,
  .top-bar,
  .bottom-nav,
  .no-print,
  .btn,
  button,
  .card-header {
    display: none !important;
  }

  #page-content {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }

  .content-area {
    margin: 0 !important;
    padding: 0 !important;
  }

  .card {
    border: none !important;
    box-shadow: none !important;
  }

  body {
    background: #fff !important;
    font-size: 12px;
  }

  .pagination,
  .dataTables_paginate,
  .page-link {
    display: none !important;
  }
}
</style>
