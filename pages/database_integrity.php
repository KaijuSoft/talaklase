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
$e = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

function integrity_table_rows(array $rows, callable $rowRenderer, string $emptyMessage, int $colspan): string
{
    if ($rows === []) {
        return '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-3">' . htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }

    $html = '';
    foreach ($rows as $row) {
        $html .= $rowRenderer($row);
    }

    return $html;
}

function integrity_reference_inspector_html(IntegrityChecker $checker, int $studentId, callable $e): string
{
    $statement = getConnection()->prepare("SELECT s.*, c.course_acronym FROM student s LEFT JOIN course c ON c.course_id=s.course_id WHERE s.st_id=?");
    $statement->execute([$studentId]);
    $student = $statement->fetch() ?: null;

    if (!$student) {
        return '<div class="alert alert-warning mb-0">Student record not found.</div>';
    }

    $references = $checker->referencesForStudent($studentId);
    ob_start();
    ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Reference Inspector</h2>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-integrity-close-inspector>Close</button>
      </div>
      <div class="card-body">
        <p class="mb-3"><strong><?= $e($student['st_lastname'] . ', ' . $student['st_name']) ?></strong> · <?= $e($student['student_no']) ?> · <?= $e($student['course_acronym']) ?></p>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Table Name</th><th>Reference Count</th></tr></thead><tbody>
        <?php foreach ($references as $table => $count): ?><tr><td><code><?= $e($table) ?></code></td><td><?= (int) $count ?></td></tr><?php endforeach; ?>
        <?php if (!$references): ?><tr><td colspan="2" class="text-muted">No referencing tables discovered.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if (array_sum($references) === 0): ?>
          <form method="post" class="mt-3" onsubmit="return confirm('Delete this orphan student record?');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete_orphan"><input type="hidden" name="student_id" value="<?= (int) $studentId ?>">
            <button class="btn btn-outline-danger btn-sm">Delete Orphan</button>
          </form>
        <?php else: ?><div class="alert alert-warning mt-3 mb-0">Deletion blocked because this student has references in other tables.</div><?php endif; ?>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

if (($_GET['action'] ?? '') === 'reference_inspector') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'html' => integrity_reference_inspector_html($checker, (int) ($_GET['student_id'] ?? 0), $e),
    ]);
    exit;
}

if (($_GET['action'] ?? '') === 'duplicate_details') {
    header('Content-Type: application/json; charset=utf-8');
    $rows = $checker->duplicateStudents();
    ob_start();
    ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Record</th><th>Duplicate Match</th><th>Student ID</th><th>Student Number</th><th>Course</th><th>Gender</th><th>Status</th><th>References</th></tr></thead>
        <tbody>
        <?php $duplicateGroup = null; foreach ($rows as $row): $group = strtolower(($row['st_lastname'] ?? '') . '|' . ($row['st_name'] ?? '') . '|' . ($row['st_middlename'] ?? '')); ?>
          <tr><td><?= $duplicateGroup === $group ? 'Duplicate Record' : 'Primary Record' ?></td><td><?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' ' . $row['st_middlename']) ?></td><td><?= (int) $row['st_id'] ?></td><td><?= $e($row['student_no']) ?></td><td><?= $e($row['course_acronym']) ?></td><td><?= $e($row['st_gender']) ?></td><td><?= $e($row['status']) ?></td><td><?= (int) $row['reference_count'] ?></td><td><button class="btn btn-sm btn-outline-primary" type="button" data-integrity-student-id="<?= (int) $row['st_id'] ?>">Inspect</button></td></tr>
        <?php $duplicateGroup = $group; endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-3">No potential duplicate students found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
    echo json_encode(['success' => true, 'html' => (string) ob_get_clean()]);
    exit;
}

if (($_GET['action'] ?? '') === 'orphan_details') {
    header('Content-Type: application/json; charset=utf-8');
    $rows = $checker->orphanStudents();
    ob_start();
    ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Student Number</th><th>Name</th><th>Course</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?><tr><td><?= $e($row['student_no']) ?></td><td><?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' ' . $row['st_middlename']) ?></td><td><?= $e($row['course_acronym'] ?? '') ?></td><td><button class="btn btn-sm btn-outline-primary" type="button" data-integrity-student-id="<?= (int) $row['st_id'] ?>">Inspect</button></td></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted py-3">No orphan students found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
    echo json_encode(['success' => true, 'html' => (string) ob_get_clean()]);
    exit;
}

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

$summary = $checker->summary();
$metrics = $checker->referencingTables();
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

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0">Performance Audit</h2>
      <span class="badge text-bg-light">Page load profiling</span>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">SQL Queries</div><div class="stat-value fs-4">Profiled on demand</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Execution Time</div><div class="stat-value fs-4">Profiled on demand</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Memory Usage</div><div class="stat-value fs-4"><?= $e(number_format(memory_get_usage(true) / 1024 / 1024, 2)) ?> MB</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">FK Metadata</div><div class="stat-value fs-4"><?= count($metrics) ?></div></div></div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0">Reference Inspector</h2>
      <button class="btn btn-sm btn-outline-primary" type="button" data-load-inspector>Load on demand</button>
    </div>
    <div class="card-body" id="referenceInspectorHost">
      <div class="text-muted">Select a student from a detail panel to load the inspector.</div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0">Duplicate Students</h2>
      <button class="btn btn-sm btn-outline-primary" type="button" data-load-duplicates>Load Duplicate Details</button>
    </div>
    <div class="card-body" id="duplicateDetailsHost">
      <div class="text-muted">Duplicate details will load after interaction.</div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0">Students Without Sections</h2>
      <button class="btn btn-sm btn-outline-primary" type="button" data-load-orphans>Load Orphan Details</button>
    </div>
    <div class="card-body" id="orphanDetailsHost">
      <div class="text-muted">Orphan details will load after interaction.</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2 class="h6 mb-0">Discovered Student Reference Tables</h2></div>
    <div class="card-body"><div class="d-flex flex-wrap gap-2"><?php foreach ($metrics as $table): ?><span class="badge text-bg-light"><code><?= $e($table) ?></code></span><?php endforeach; ?><?php if (!$metrics): ?><span class="text-muted">No foreign-key references discovered.</span><?php endif; ?></div></div>
  </div>
</section>

<script src="assets/js/database-integrity.js"></script>
