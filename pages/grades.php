<?php
require_once __DIR__ . '/../includes/grades_controller.php';
?>

<div class="card" id="gradePage" data-can-manage-grades="<?= can('manage_grades') ? '1' : '0' ?>">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Grading Form</h6>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" onclick="printGrades()">
        <i class="bi bi-printer-fill me-1"></i> Print
      </button>
    </div>
  </div>
  <div class="card-body">

    <!-- Teaching assignment selector -->
    <div class="row g-3 mb-4">
      <div class="col-md-9">
        <label class="form-label">Teaching Assignment</label>
        <select class="form-select" id="gr_assignment" onchange="onFilterChange()">
          <option value="">Select Teaching Assignment</option>
          <?php foreach ($assignments as $a): ?>
            <option value="<?= (int)$a['assignment_id'] ?>" data-section-id="<?= (int)$a['sectionID'] ?>" data-subject-id="<?= (int)$a['sub_id'] ?>">
              <?= htmlspecialchars($a['section']) ?> &bull; <?= htmlspecialchars($a['sub_name']) ?> &bull; <?= htmlspecialchars($a['inst_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100" onclick="loadGrid()">
          <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
      </div>
      <div class="col-12 pt-0">
        <small class="text-muted">Maximum scores are saved independently for each teaching assignment, term, and component. Switch the term tab to configure a different set.</small>
      </div>
    </div>

    <!-- Term tabs -->
    <ul class="nav nav-tabs mb-0" id="termTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" href="#" data-term="Prelim"    onclick="setTerm(this);return false;">Prelim</a></li>
      <li class="nav-item"><a class="nav-link"        href="#" data-term="Midterm"   onclick="setTerm(this);return false;">Midterm</a></li>
      <li class="nav-item"><a class="nav-link"        href="#" data-term="PreFinal"  onclick="setTerm(this);return false;">Pre-Finals</a></li>
      <li class="nav-item"><a class="nav-link"        href="#" data-term="Final"     onclick="setTerm(this);return false;">Finals</a></li>
      <li class="nav-item"><a class="nav-link text-success" href="#" data-term="Summary" onclick="setTerm(this);return false;"><i class="bi bi-bar-chart-fill me-1"></i>Summary</a></li>
    </ul>

    <!-- Component sub-tabs (hidden in Summary) -->
    <div id="compTabsWrapper" class="bg-light border border-top-0 px-3 pt-2 pb-1 mb-3">
      <ul class="nav nav-pills" id="compTabs">
        <li class="nav-item"><a class="nav-link active py-1" href="#" data-comp="Participation" onclick="setComp(this);return false;">Participation</a></li>
        <li class="nav-item"><a class="nav-link py-1"        href="#" data-comp="Written"       onclick="setComp(this);return false;">Written</a></li>
        <li class="nav-item"><a class="nav-link py-1"        href="#" data-comp="Performance"   onclick="setComp(this);return false;">Performance</a></li>
        <li class="nav-item"><a class="nav-link py-1"        href="#" data-comp="Exam"          onclick="setComp(this);return false;">Exam</a></li>
      </ul>
    </div>

    <!-- Individual activity maximum scores -->
    <?php if (can('manage_grades')): ?>
    <div id="maxScorePanel" class="card border mb-3 d-none">
      <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
          <div>
            <h6 class="mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Maximum Scores</h6>
            <small class="text-muted">Set the maximum score for each individual activity.</small>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="saveMaxScores()">
            <i class="bi bi-check2-circle me-1"></i>Save Max Scores
          </button>
        </div>
        <div id="maxScoreFields" class="row g-2"></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Grid output -->
    <div id="gradeGrid">
      <div class="text-center text-muted py-5">
        <i class="bi bi-arrow-up-circle fs-1 d-block mb-2"></i>
        Select a teaching assignment above to load grades.
      </div>
    </div>

    <!-- Save button -->
    <?php if (can('manage_grades')): ?>
    <div id="saveBtnArea" class="d-none mt-3 d-flex gap-2 align-items-center">
      <button class="btn btn-success" onclick="saveGrades()">
        <i class="bi bi-floppy-fill me-1"></i> Save <span id="saveLabel"></span>
      </button>
    </div>
    <?php endif; ?>

  </div>
</div>
