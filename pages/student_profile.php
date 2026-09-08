<?php
require_once __DIR__ . '/../includes/student_profile_controller.php';
$profile = loadStudentProfilePage();

if (!empty($profile['error_status'])) {
    http_response_code((int) $profile['error_status']);
    ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= htmlspecialchars($profile['error_message'] ?? 'Unable to load student profile.') ?>
    </div>
    <a href="?page=students" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to Students
    </a>
    <?php
    return;
}

extract($profile, EXTR_SKIP);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <a href="?page=students" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Back to Students</a>
    <h2 class="mb-1"><?= htmlspecialchars($student['st_lastname'] . ', ' . $student['st_name']) ?></h2>
    <div class="text-muted">
      <?= htmlspecialchars($student['course_acronym'] ?? '') ?> &bull;
      <?= htmlspecialchars((string) ($student['yearlvl'] ?? '')) ?> &bull;
      <?= htmlspecialchars($student['section'] ?? '') ?>
    </div>
  </div>
  <form method="GET" class="d-flex gap-2">
    <input type="hidden" name="page" value="student_profile">
    <input type="hidden" name="st_id" value="<?= (int) ($studentId ?? $student['st_id'] ?? 0) ?>">
    <select name="term" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="All">All Terms</option>
      <?php foreach ($terms as $term): ?>
        <option value="<?= htmlspecialchars($term) ?>" <?= $selectedTerm === $term ? 'selected' : '' ?>><?= htmlspecialchars($termLabels[$term]) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?= htmlspecialchars($student['student_no'] ?? '-') ?></div><div class="stat-label">Student Number</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?= $attTotals['Present'] ?></div><div class="stat-label">Present Records</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?= $attTotals['Late'] ?></div><div class="stat-label">Late Records</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?= number_format($totalHours, 2) ?> hrs</div><div class="stat-label">Hours Rendered</div></div></div>
</div>

<ul class="nav nav-tabs mb-3" id="studentRecordTabs">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profileOverview">Overview</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#profileGrades">Grades</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#profileAttendance">Attendance</button></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="profileOverview">
    <div class="card"><div class="card-body"><div class="row g-3">
      <div class="col-md-4"><small class="text-muted">Student Number</small><div class="fw-semibold"><?= htmlspecialchars($student['student_no'] ?? '-') ?></div></div>
      <div class="col-md-4"><small class="text-muted">Course</small><div class="fw-semibold"><?= htmlspecialchars($student['course_name'] ?? $student['course_acronym'] ?? '-') ?></div></div>
      <div class="col-md-4"><small class="text-muted">Gender</small><div class="fw-semibold"><?= htmlspecialchars($student['st_gender'] ?? '-') ?></div></div>
      <div class="col-md-4"><small class="text-muted">Section</small><div class="fw-semibold"><?= htmlspecialchars($student['section'] ?? '-') ?></div></div>
      <div class="col-md-4"><small class="text-muted">Year Level</small><div class="fw-semibold"><?= htmlspecialchars((string) ($student['yearlvl'] ?? '-')) ?></div></div>
      <div class="col-md-4"><small class="text-muted">Academic Year</small><div class="fw-semibold"><?= htmlspecialchars($student['ay_name'] ?? 'Current') ?></div></div>
    </div></div></div>
  </div>
  <div class="tab-pane fade" id="profileGrades">
    <div class="card"><div class="card-body">
      <?php if (empty($gradeGroups)): ?>
        <div class="text-center text-muted py-5">No grade records found for the selected term.</div>
      <?php else: ?>
        <?php foreach ($gradeGroups as $subject): ?>
          <div class="mb-4">
            <h5 class="mb-1"><?= htmlspecialchars($subject['sub_code'] . ' - ' . $subject['sub_name']) ?></h5>
            <div class="text-muted small mb-3"><?= htmlspecialchars($subject['inst_name'] ?? '') ?></div>
            <?php foreach ($terms as $term): ?>
              <?php if (empty($subject['terms'][$term])) continue; $grade = $subject['terms'][$term]; ?>
              <div class="border rounded p-3 mb-3">
                <h6 class="text-primary mb-3"><?= htmlspecialchars($termLabels[$term]) ?></h6>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light"><tr><th>Component</th><th>Score 1</th><th>Score 2</th><th>Score 3</th><th>Score 4</th><th>Score 5</th><th>Exam</th></tr></thead>
                    <tbody>
                      <?php foreach ([
                          'participation' => ['label' => 'Participation', 'columns' => ['par_one','par_two','par_three','par_four','par_five']],
                          'written' => ['label' => 'Written', 'columns' => ['written_one','written_two','written_three','written_four','written_five']],
                          'performance' => ['label' => 'Performance', 'columns' => ['perf_one','perf_two','perf_three','perf_four','perf_five']],
                      ] as $component => $definition): ?>
                        <tr><th><?= htmlspecialchars($definition['label']) ?></th>
                          <?php foreach ($definition['columns'] as $index => $column): ?>
                            <td><?= htmlspecialchars((string) ($grade[$column] ?? '-')) ?> / <?= htmlspecialchars((string) ($grade['max'][$component][$index] ?? '-')) ?></td>
                          <?php endforeach; ?>
                          <td>-</td>
                        </tr>
                      <?php endforeach; ?>
                      <tr><th>Major Exam</th><td colspan="5">-</td><td><?= htmlspecialchars((string) ($grade['score'] ?? '-')) ?> / <?= htmlspecialchars((string) ($grade['exam_max'] ?? '-')) ?></td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div></div>
  </div>
  <div class="tab-pane fade" id="profileAttendance">
    <div class="card"><div class="card-body">
      <?php if (empty($attByTerm)): ?>
        <div class="text-center text-muted py-5">No attendance records found for the selected term.</div>
      <?php else: ?>
        <?php foreach ($terms as $term): ?>
          <?php if (empty($attByTerm[$term])) continue; ?>
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0"><?= htmlspecialchars($termLabels[$term]) ?></h5><span class="badge bg-secondary-subtle text-secondary border"><?= count($attByTerm[$term]) ?> records</span></div>
            <div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0">
              <thead class="table-light"><tr><th>Date</th><th>Subject</th><th>Instructor</th><th>Status</th><th>Time In</th><th>Late Minutes</th><th>Hours Rendered</th></tr></thead><tbody>
              <?php foreach ($attByTerm[$term] as $record): ?>
                <?php $duration = max(0, (strtotime($record['end_time']) - strtotime($record['start_time'])) / 3600); $hours = $record['status'] === 'Present' ? $duration : ($record['status'] === 'Late' ? max(0, $duration - ((float) $record['late_minutes'] / 60)) : 0); ?>
                <tr>
                  <td><?= htmlspecialchars($record['_date']) ?></td>
                  <td><?= htmlspecialchars($record['sub_code'] . ' - ' . $record['sub_name']) ?></td>
                  <td><?= htmlspecialchars($record['inst_name'] ?? '-') ?></td>
                  <td><span class="badge <?= $record['status'] === 'Present' ? 'bg-success' : ($record['status'] === 'Late' ? 'bg-warning text-dark' : 'bg-danger') ?>"><?= htmlspecialchars($record['status']) ?></span></td>
                  <td><?= htmlspecialchars($record['time_in'] ?? '-') ?></td>
                  <td><?= $record['status'] === 'Late' ? (int) $record['late_minutes'] : '-' ?></td>
                  <td class="fw-semibold"><?= number_format($hours, 2) ?> hrs</td>
                </tr>
              <?php endforeach; ?>
              </tbody></table></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div></div>
  </div>
</div>
<style>.student-profile-table th{white-space:nowrap}.student-profile-table td{vertical-align:middle}</style>
