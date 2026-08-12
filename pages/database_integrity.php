<?php
require_once __DIR__ . '/../includes/database_integrity_controller.php';
?>
<section class="mb-4" aria-labelledby="integrity-title">
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
      <div class="text-muted small">Administration</div>
      <h1 id="integrity-title" class="h4 mb-1">Database Maintenence Center</h1>
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
     <div class="alert alert-info mb-0">
    <strong>Impact Analysis</strong><br>
    Select a student from <strong>Duplicate Students</strong> or
    <strong>Students Without Sections</strong> and click
    <strong>Analyze Impact</strong> to load this panel.
	</div>
    </div>
    <div class="card-body" id="referenceInspectorHost">
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0">Duplicate Students</h2>
      <button class="btn btn-sm btn-outline-primary" type="button" data-load-duplicates>Analyze Duplicates</button>
    </div>
    <div class="card-body" id="duplicateDetailsHost">
      <div class="text-muted">Duplicate details will load after interaction.</div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0">Students Without Sections</h2>
      <button class="btn btn-sm btn-outline-primary" type="button" data-load-orphans>Analyze Orphans</button>
    </div>
    <div class="card-body" id="orphanDetailsHost">
      <div class="text-muted">Orphan details will load after interaction.</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2 class="h6 mb-0">Discovered Student Reference Tables</h2></div>
    <div class="card-body"><div class="d-flex flex-wrap gap-2"><?php foreach ($metrics as $table): ?><span class="badge text-bg-light"><code><?= $e($table) ?></code></span><?php endforeach; ?><?php if (!$metrics): ?><span class="text-muted">No foreign-key references discovered.</span><?php endif; ?></div></div>
  </div>

<div class="modal fade" id="studentMergeModal" tabindex="-1" aria-labelledby="studentMergeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h2 class="modal-title fs-5" id="studentMergeModalLabel">Merge Duplicate Student</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-warning"><strong>Destructive operation.</strong> A local database backup is created before the merge. The operation is transactional and is rolled back if any reference update or delete fails.</div>
        <input type="hidden" id="integrityCsrf" value="<?= $e(csrf_token()) ?>">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="mergeSurvivor">Surviving record</label><select class="form-select" id="mergeSurvivor"></select><div class="form-text">This record remains. Its student fields are not overwritten.</div></div>
          <div class="col-md-6"><label class="form-label" for="mergeDuplicate">Duplicate to remove</label><select class="form-select" id="mergeDuplicate"></select></div>
        </div>
        <div id="mergePreviewHost" class="mt-3"><div class="text-muted">Choose two records to preview the impact.</div></div>
        <div id="mergeDifferenceConfirmation" class="alert alert-warning mt-3 d-none">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="confirmMergeFieldDifferences">
            <label class="form-check-label" for="confirmMergeFieldDifferences">
              <strong>I understand the field differences shown above.</strong> I confirm that the selected survivor's values will be retained and the duplicate's differing student fields will not be copied automatically.
            </label>
          </div>
        </div>
        <div class="mt-3"><label class="form-label" for="mergeReason">Merge reason</label><textarea class="form-control" id="mergeReason" rows="3" maxlength="500" required placeholder="Explain why these records are duplicates and why the selected survivor should remain."></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="executeStudentMerge" disabled>Backup &amp; Merge</button></div>
    </div>
  </div>
</div>
</section>