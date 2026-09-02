<?php
require_once __DIR__ . '/../includes/view_attendance_v2_controller.php';
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
            placeholder="Search full name..."
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
          <?= $filter_from ?: '...' ?> -> <?= $filter_to ?: '...' ?>
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
          <?php else:
            $lastGender = null;
            foreach ($records as $i => $r):
              $gender = strtolower(trim((string)($r['st_gender'] ?? '')));
              $genderLabel = $gender === 'male' ? 'Male Students' : ($gender === 'female' ? 'Female Students' : 'Other Students');
              if ($gender !== $lastGender):
                $lastGender = $gender;
          ?>
          <tr class="table-light">
            <td colspan="12" class="fw-semibold text-primary">
              <i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($genderLabel) ?>
            </td>
          </tr>
          <?php endif; ?>
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
                <?= $r['DateFrom'] ?> -> <?= $r['DateTo'] ?>
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
          <?php $paginationQuery = [
              'page' => 'view_attendance_v2',
              'assignment_id' => $filter_assignment,
              'term' => $filter_term,
              'date_from' => $filter_from,
              'date_to' => $filter_to,
              'name' => $filter_name,
          ]; ?>
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query($paginationQuery + ['p' => $page - 1]) ?>" aria-label="Previous">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php for ($pg = max(1, $page - 2); $pg <= min($totalPages, $page + 2); $pg++): ?>
            <li class="page-item <?= $pg === $page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query($paginationQuery + ['p' => $pg]) ?>"><?= $pg ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query($paginationQuery + ['p' => $page + 1]) ?>" aria-label="Next">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>

  </div>
</div>
