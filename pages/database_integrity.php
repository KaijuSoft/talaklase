<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('sync_settings');
require_once __DIR__ . '/../includes/Integrity/ReferenceInspector.php';
require_once __DIR__ . '/../includes/Integrity/DuplicateDetector.php';
require_once __DIR__ . '/../includes/Integrity/IntegrityReport.php';
require_once __DIR__ . '/../includes/Integrity/IntegrityChecker.php';

$pdo = getConnection();
$checker = new IntegrityChecker($pdo);
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_orphan') {
    if (!verify_csrf()) {
        $message = 'Deletion blocked: invalid security token.';
        $messageType = 'danger';
    } else {
        try {
            $checker->deleteOrphan((int) ($_POST['student_id'] ?? 0));
            $message = 'Orphan student deleted after reference verification.';
            $messageType = 'success';
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $messageType = 'danger';
        }
    }
}

$report = $checker->inspect();
$summary = $report->summary();
$selectedStudent = null;
$selectedReferences = [];
$selectedId = (int) ($_GET['student_id'] ?? 0);
if ($selectedId > 0) {
    $statement = $pdo->prepare("SELECT s.*, c.course_acronym FROM student s LEFT JOIN course c ON c.course_id=s.course_id WHERE s.st_id=?");
    $statement->execute([$selectedId]);
    $selectedStudent = $statement->fetch() ?: null;
    if ($selectedStudent) {
        $selectedReferences = $checker->referencesForStudent($selectedId);
    }
}

$e = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>

<section class="mb-4" aria-labelledby="integrity-title">
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
      <div class="text-muted small">Administration</div>
      <h1 id="integrity-title" class="h4 mb-1">Database Integrity</h1>
      <p class="text-muted mb-0">Detect duplicate, orphaned, and unassigned student records safely.</p>
    </div>
    <span class="badge text-bg-light align-self-center">Analysis only by default</span>
  </div>

  <?php if ($message !== null): ?><div class="alert alert-<?= $e($messageType) ?>" role="alert"><?= $e($message) ?></div><?php endif; ?>

  <div class="row g-3 mb-4">
    <?php foreach ([
      ['Duplicate Students', $summary['duplicate_students'], 'bi-people', 'primary'],
      ['Students Without Sections', $summary['students_without_sections'], 'bi-person-dash', 'warning'],
      ['Orphan Students', $summary['orphan_students'], 'bi-person-x', 'danger'],
      ['Duplicate Student Numbers', $summary['duplicate_student_numbers'], 'bi-card-list', 'info'],
      ['Broken References', $summary['broken_references'], 'bi-link-45deg', 'success'],
    ] as [$label, $value, $icon, $color]): ?>
    <div class="col-6 col-xl">
      <div class="stat-card h-100"><div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
        <div><div class="stat-value fs-4"><?= (int) $value ?></div><div class="stat-label"><?= $e($label) ?></div></div>
      </div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($selectedStudent): ?>
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between"><h2 class="h6 mb-0">Reference Inspector</h2><a class="btn btn-sm btn-outline-secondary" href="?page=database_integrity">Close</a></div>
    <div class="card-body">
      <p class="mb-3"><strong><?= $e($selectedStudent['st_lastname'] . ', ' . $selectedStudent['st_name']) ?></strong> · <?= $e($selectedStudent['student_no']) ?> · <?= $e($selectedStudent['course_acronym']) ?></p>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Table Name</th><th>Reference Count</th></tr></thead><tbody>
      <?php foreach ($selectedReferences as $table => $count): ?><tr><td><code><?= $e($table) ?></code></td><td><?= (int) $count ?></td></tr><?php endforeach; ?>
      <?php if (!$selectedReferences): ?><tr><td colspan="2" class="text-muted">No referencing tables discovered.</td></tr><?php endif; ?>
      </tbody></table></div>
      <?php if (array_sum($selectedReferences) === 0): ?>
        <form method="post" class="mt-3" onsubmit="return confirm('Delete this orphan student record?');">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_orphan"><input type="hidden" name="student_id" value="<?= (int) $selectedId ?>">
          <button class="btn btn-outline-danger btn-sm">Delete Orphan</button>
        </form>
      <?php else: ?><div class="alert alert-warning mt-3 mb-0">Deletion blocked because this student has references in other tables.</div><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card mb-3"><div class="card-header"><h2 class="h6 mb-0">Duplicate Students</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Record</th><th>Duplicate Match</th><th>Student ID</th><th>Student Number</th><th>Course</th><th>Gender</th><th>Status</th><th>References</th><th>Actions</th></tr></thead><tbody>
  <?php $duplicateGroup = null; foreach ($report->duplicates as $row): $group = strtolower(($row['st_lastname'] ?? '') . '|' . ($row['st_name'] ?? '') . '|' . ($row['st_middlename'] ?? '')); ?>
    <tr><td><?= $duplicateGroup === $group ? 'Duplicate Record' : 'Primary Record' ?></td><td><?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' ' . $row['st_middlename']) ?></td><td><?= (int) $row['st_id'] ?></td><td><?= $e($row['student_no']) ?></td><td><?= $e($row['course_acronym']) ?></td><td><?= $e($row['st_gender']) ?></td><td><?= $e($row['status']) ?></td><td><?= (int) $row['reference_count'] ?></td><td><a class="btn btn-sm btn-outline-primary" href="?page=database_integrity&student_id=<?= (int) $row['st_id'] ?>">View Details</a> <button class="btn btn-sm btn-outline-secondary" disabled>Merge</button> <button class="btn btn-sm btn-outline-secondary" disabled>Ignore</button></td></tr>
  <?php $duplicateGroup = $group; endforeach; ?>
  <?php if (!$report->duplicates): ?><tr><td colspan="9" class="text-center text-muted py-3">No potential duplicate students found.</td></tr><?php endif; ?></tbody></table></div></div>

  <div class="card mb-3"><div class="card-header"><h2 class="h6 mb-0">Students Without Sections</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Student Number</th><th>Name</th><th>Course</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($report->withoutSections as $row): ?><tr><td><?= $e($row['student_no']) ?></td><td><?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' ' . $row['st_middlename']) ?></td><td><?= $e($row['course_acronym']) ?></td><td>Without section</td><td><button class="btn btn-sm btn-outline-secondary" disabled>Assign Section</button></td></tr><?php endforeach; ?>
  <?php if (!$report->withoutSections): ?><tr><td colspan="5" class="text-center text-muted py-3">All students have at least one section.</td></tr><?php endif; ?></tbody></table></div></div>

  <div class="card mb-3"><div class="card-header"><h2 class="h6 mb-0">Duplicate Student Numbers</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Student Number</th><th>Record Count</th></tr></thead><tbody>
  <?php foreach ($report->duplicateNumbers as $row): ?><tr><td><?= $e($row['student_no']) ?></td><td><?= (int) $row['record_count'] ?></td></tr><?php endforeach; ?>
  <?php if (!$report->duplicateNumbers): ?><tr><td colspan="2" class="text-center text-muted py-3">No duplicate non-null student numbers found.</td></tr><?php endif; ?></tbody></table></div></div>

  <div class="card"><div class="card-header"><h2 class="h6 mb-0">Discovered Student Reference Tables</h2></div><div class="card-body"><div class="d-flex flex-wrap gap-2"><?php foreach ($report->referenceTables as $table): ?><span class="badge text-bg-light"><code><?= $e($table) ?></code></span><?php endforeach; ?><?php if (!$report->referenceTables): ?><span class="text-muted">No foreign-key references discovered.</span><?php endif; ?></div></div></div>
</section>
