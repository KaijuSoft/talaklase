<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_permission('sync_settings');
require_once __DIR__ . '/Integrity/ReferenceInspector.php';
require_once __DIR__ . '/Integrity/DuplicateDetector.php';
require_once __DIR__ . '/Integrity/IntegrityReport.php';
require_once __DIR__ . '/Integrity/IntegrityChecker.php';
require_once __DIR__ . '/Integrity/MaintenanceManager.php';

$pdo = getConnection();
$checker = new IntegrityChecker($pdo);
$maintenance = new MaintenanceManager($pdo, new ReferenceInspector($pdo));
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
        <h2 class="h6 mb-0">Impact Analysis</h2>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-integrity-close-inspector>Close</button>
      </div>
      <div class="card-body">
        <p class="mb-3"><strong><?= $e($student['st_lastname'] . ', ' . $student['st_name']) ?></strong> | <?= $e($student['student_no']) ?> | <?= $e($student['course_acronym']) ?></p>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Table Name</th><th>Reference Count</th></tr></thead><tbody>
        <?php foreach ($references as $table => $count): ?><tr><td><code><?= $e($table) ?></code></td><td><?= (int) $count ?></td></tr><?php endforeach; ?>
        <?php if (!$references): ?><tr><td colspan="2" class="text-muted">No referencing tables discovered.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if (array_sum($references) === 0): ?>
          <form method="post" class="mt-3" onsubmit="return confirm('Delete this orphan student record? A backup will be created first.');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete_orphan"><input type="hidden" name="student_id" value="<?= (int) $studentId ?>"><div class="mb-2"><label class="form-label small" for="orphanReason">Deletion reason</label><input class="form-control form-control-sm" id="orphanReason" name="reason" required maxlength="500" placeholder="Why is this orphan record safe to remove?"></div>
            <button class="btn btn-outline-danger btn-sm">Delete Orphan</button>
          </form>
        <?php else: ?><div class="alert alert-warning mt-3 mb-0">Deletion blocked because this student has references in other tables.</div><?php endif; ?>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

if (($_GET['action'] ?? '') === 'merge_preview') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        echo json_encode(['success'=>true,'plan'=>$maintenance->analyzeStudentMerge((int)($_GET['survivor_id']??0),(int)($_GET['duplicate_id']??0))]);
    } catch (Throwable $exception) {
        http_response_code(422);
        echo json_encode(['success'=>false,'message'=>$exception->getMessage()]);
    }
    exit;
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
        <thead><tr><th>Record</th><th>Duplicate Match</th><th>Student ID</th><th>Student Number</th><th>Course</th><th>Gender</th><th>Status</th><th>Dependent Records</th><th>Action</th></tr></thead>
        <tbody>
        <?php $duplicateGroup = null; foreach ($rows as $row): $group = strtolower(($row['st_lastname'] ?? '') . '|' . ($row['st_name'] ?? '') . '|' . ($row['st_middlename'] ?? '')); ?>
          <tr data-integrity-merge-row data-student-id="<?= (int) $row['st_id'] ?>" data-student-label="<?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' | ' . $row['student_no']) ?>" data-duplicate-group="<?= $e($group) ?>"><td><?= $duplicateGroup === $group ? 'Duplicate Record' : 'Primary' ?></td><td><?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' ' . $row['st_middlename']) ?></td><td><?= (int) $row['st_id'] ?></td><td><?= $e($row['student_no']) ?></td><td><?= $e($row['course_acronym']) ?></td><td><?= $e($row['st_gender']) ?></td><td><?php $statusClass = $row['reference_count'] > 0 ? 'bg-warning text-dark' : 'bg-success'; ?> <span class="badge <?= $statusClass ?>"><?= $e($row['status']) ?></span></td><td><?= (int) $row['reference_count'] ?></td><td><div class="d-flex flex-wrap gap-1"><button class="btn btn-sm btn-outline-primary" type="button" data-integrity-student-id="<?= (int) $row['st_id'] ?>">Analyze Impact</button><button class="btn btn-sm btn-outline-danger" type="button" data-prepare-merge>Prepare Merge</button></div></td></tr>
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
        <?php foreach ($rows as $row): ?><tr><td><?= $e($row['student_no']) ?></td><td><?= $e($row['st_lastname'] . ', ' . $row['st_name'] . ' ' . $row['st_middlename']) ?></td><td><?= $e($row['course_acronym'] ?? '') ?></td><td><button class="btn btn-sm btn-outline-primary" type="button" data-integrity-student-id="<?= (int) $row['st_id'] ?>">Analyze Impact</button></td></tr><?php endforeach; ?>
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'merge_students') {
    header('Content-Type: application/json; charset=utf-8');
    if (!verify_csrf()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Merge blocked: invalid security token.']); exit; }
    try {
        $result=$maintenance->mergeStudents((int)($_POST['survivor_id']??0),(int)($_POST['duplicate_id']??0),(string)($_POST['reason']??''),(($_POST['confirm_field_differences']??'0')==='1'));
        echo json_encode(['success'=>true,'message'=>'Student records merged safely.','result'=>$result]);
    } catch(Throwable $exception) { http_response_code(422); echo json_encode(['success'=>false,'message'=>$exception->getMessage()]); }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_orphan') {
    if (!verify_csrf()) { $message='Deletion blocked: invalid security token.'; $messageType='danger'; }
    else {
        try { $backup=$maintenance->deleteOrphan((int)($_POST['student_id']??0),(string)($_POST['reason']??'')); header('Location: ?page=database_integrity&deleted=1&backup='.urlencode($backup)); exit; }
        catch(Throwable $exception) { $message=$exception->getMessage(); $messageType='danger'; }
    }
}

$summary = $checker->summary();
$metrics = $checker->referencingTables();
if (($_GET['deleted'] ?? '') === '1') {
    $message = 'Orphan student deleted after reference verification. Backup: ' . ($e($_GET['backup'] ?? ''));
    $messageType = 'success';
}