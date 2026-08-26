<?php
require_once __DIR__ . '/../includes/student_controller.php';
$pageData = handleStudentPageRequest();
extract($pageData, EXTR_SKIP);
?>


<!-- Success import -->
<?php if (!empty($_SESSION['import_success'])): ?>

<div class="alert alert-success">
    <?= htmlspecialchars($_SESSION['import_success']) ?>
</div>

<?php unset($_SESSION['import_success']); ?>

<?php endif; ?>

<?php if (!empty($_SESSION['import_errors'])): ?>

<div class="alert alert-warning">

    <strong>Import Issues:</strong>

    <ul class="mb-0">

        <?php foreach ($_SESSION['import_errors'] as $error): ?>

            <li><?= htmlspecialchars($error) ?></li>

        <?php endforeach; ?>

    </ul>

</div>

<!-- Error Import -->

<?php unset($_SESSION['import_errors']); ?>

<?php endif; ?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
        <div>
          <div class="stat-value"><?= $totalRecords ?></div>
          <div class="stat-label">Total Students</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-person-check-fill"></i></div>
        <div>
          <div class="stat-value"><?= $assignedStudents ?></div>
          <div class="stat-label">Students Assigned to Sections</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-person-dash-fill"></i></div>
        <div>
          <div class="stat-value"><?= $unassignedStudents ?></div>
          <div class="stat-label">Students Without Sections</div>
        </div>
      </div>
    </div>
  </div>

<!-- Stats row -->
<div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-gender-male"></i></div>
        <div>
          <div class="stat-value"><?= $male ?></div>
          <div class="stat-label">Male</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-pink-subtle" style="background:#fce7f3;color:#9d174d"><i class="bi bi-gender-female"></i></div>
        <div>
          <div class="stat-value"><?= $female ?></div>
          <div class="stat-label">Female</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-grid-fill"></i></div>
        <div>
          <div class="stat-value"><?= count($sections) ?></div>
          <div class="stat-label">Sections</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Table Card -->
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <form method="GET" class="d-flex gap-2 align-items-center">
      <input type="hidden" name="page" value="students"/>      <script type="application/json" id="studentPageConfig">{"canManageStudents":<?= can('edit_students') ? 'true' : 'false' ?>,"cacheScope":<?= json_encode((string)($currentUser['id'] ?? 'user')) ?>}</script>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>" style="width:220px"/>
      <select name="section_id" class="form-select form-select-sm" style="width:190px">
        <option value="0">All Sections</option>
        <?php foreach ($sections as $sectionOption): ?>
          <option value="<?= $sectionOption['sectionID'] ?>" <?= $filterSection === (int)$sectionOption['sectionID'] ? 'selected' : '' ?>><?= htmlspecialchars($sectionOption['section']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" onclick="printStudents()">
        <i class="bi bi-printer-fill me-1"></i> Print
      </button>
      <?php if (can('edit_students')): ?>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add Student
      </button>
	  <button
    class="btn btn-success"
    data-bs-toggle="modal"
    data-bs-target="#importStudentsModal">

    <i class="bi bi-file-earmark-excel"></i>
    Import Excel
</button>

	<a href="./assets/templates/student_import_template.xlsx"
				download
					class="btn btn-success">
						<i class="bi bi-download"></i>
							Download Template
						</a>
		
      <?php endif; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Student No.</th>
          <th>Last Name</th>
          <th>First Name</th>
          <th>Middle Name</th>
          <th>Suffix</th>
          <th>Course</th>
          <th>Year</th>
          <th>Gender</th>
          <th>Section</th>
          <?php if (can('edit_students')): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody class="students-table-body">
        <?php if (empty($students)): ?>
          <tr><td colspan="<?= can('edit_students') ? 11 : 10 ?>" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No students found.</td></tr>
        <?php else:
          $lastSection = null;
          foreach ($students as $i => $s):
            if ($lastSection !== $s['section']):
              $lastSection = $s['section'];
        ?>
          <tr class="table-primary"><td colspan="<?= can('edit_students') ? 11 : 10 ?>" class="fw-semibold py-2"><i class="bi bi-diagram-3-fill me-2"></i><?= htmlspecialchars($s['section']) ?></td></tr>
        <?php endif; ?>
          <tr>
            <td class="text-muted"><?= $offset + $i + 1 ?></td>
            <td><?= htmlspecialchars($s['student_no']) ?></td>
            <td colspan="2"><a class="fw-semibold text-decoration-none" href="?page=student_profile&st_id=<?= (int)$s['st_id'] ?>"><?= htmlspecialchars($s['st_lastname']) ?>, <?= htmlspecialchars($s['st_name']) ?></a></td>
            <td><?= htmlspecialchars($s['st_middlename']) ?></td>
            <td><?= htmlspecialchars($s['st_suffix']) ?></td>
            <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= htmlspecialchars($s['course_acronym']) ?></span></td>
            <td><?= htmlspecialchars($s['yearlvl']) ?></td>
            <td>
              <span class="badge <?= $s['st_gender']==='Male'?'badge-gender-male':'badge-gender-female' ?>">
                <?= htmlspecialchars($s['st_gender']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($s['section']) ?></td>
            <?php if (can('edit_students')): ?>
            <td>
              <button class="btn btn-xs btn-outline-primary btn-sm py-0 px-1"
                onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button class="btn btn-xs btn-outline-danger btn-sm py-0 px-1 ms-1"
                onclick="deleteStudent(<?= $s['st_id'] ?>, '<?= htmlspecialchars($s['st_lastname'].', '.$s['st_name']) ?>')">
                <i class="bi bi-trash-fill"></i>
              </button>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer d-flex align-items-center justify-content-between">
    <small class="text-muted">Page <?= $currentPage ?> of <?= $totalPages ?> &mdash; <?= $totalRecords ?> records</small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $currentPage<=1?'disabled':'' ?>">
          <a class="page-link" href="?page=students&p=<?= $currentPage-1 ?>&q=<?= urlencode($search) ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
        <?php for ($pg=max(1,$currentPage-2); $pg<=min($totalPages,$currentPage+2); $pg++): ?>
          <li class="page-item <?= $pg==$currentPage?'active':'' ?>">
            <a class="page-link" href="?page=students&p=<?= $pg ?>&q=<?= urlencode($search) ?>"><?= $pg ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage>=$totalPages?'disabled':'' ?>">
          <a class="page-link" href="?page=students&p=<?= $currentPage+1 ?>&q=<?= urlencode($search) ?>"><i class="bi bi-chevron-right"></i></a>
        </li>
      </ul>
    </nav>
  </div>
</div>

<?php if (can('edit_students')): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Add Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Student Number</label><input type="text" class="form-control" id="add_student_no"/></div>
          <div class="col-md-4"><label class="form-label">Last Name *</label><input type="text" class="form-control" id="add_lastname" required/></div>
          <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" class="form-control" id="add_firstname" required/></div>
          <div class="col-md-3"><label class="form-label">Middle Name</label><input type="text" class="form-control" id="add_middlename"/></div>
          <div class="col-md-1"><label class="form-label">Suffix</label><input type="text" class="form-control" id="add_suffix"/></div>
          <div class="col-md-3">
            <label class="form-label">Gender *</label>
            <select class="form-select" id="add_gender">
              <option value="">Select...</option>
              <?php foreach ($genders as $g): ?><option><?= $g ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Course *</label>
            <select class="form-select" id="add_course">
              <option value="">Select...</option>
              <?php foreach ($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_acronym']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Section *</label>
            <select class="form-select" id="add_section">
              <option value="">Select...</option>
              <?php foreach ($sections as $s): ?><option value="<?= $s['sectionID'] ?>"><?= htmlspecialchars($s['section']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Year Level *</label>
            <select class="form-select" id="add_year">
              <option value="">Select...</option>
              <?php foreach ($years as $y): ?><option><?= $y ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveStudent()"><i class="bi bi-check-lg me-1"></i>Save Student</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2 text-warning"></i>Edit Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id"/>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Student Number</label><input type="text" class="form-control" id="edit_student_no"/></div>
          <div class="col-md-4"><label class="form-label">Last Name *</label><input type="text" class="form-control" id="edit_lastname"/></div>
          <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" class="form-control" id="edit_firstname"/></div>
          <div class="col-md-3"><label class="form-label">Middle Name</label><input type="text" class="form-control" id="edit_middlename"/></div>
          <div class="col-md-1"><label class="form-label">Suffix</label><input type="text" class="form-control" id="edit_suffix"/></div>
          <div class="col-md-3">
            <label class="form-label">Gender *</label>
            <select class="form-select" id="edit_gender">
              <?php foreach ($genders as $g): ?><option><?= $g ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Course *</label>
            <select class="form-select" id="edit_course">
              <?php foreach ($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_acronym']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Section *</label>
            <select class="form-select" id="edit_section">
              <?php foreach ($sections as $s): ?><option value="<?= $s['sectionID'] ?>"><?= htmlspecialchars($s['section']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Year Level *</label>
            <select class="form-select" id="edit_year">
              <?php foreach ($years as $y): ?><option><?= $y ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-white" onclick="updateStudent()"><i class="bi bi-check-lg me-1"></i>Update</button>
      </div>
    </div>
  </div>
</div>
<?php if (can('edit_students')): ?>
<div class="modal fade" id="importStudentsModal">
    <div class="modal-dialog">
        <div class="modal-content">
						<div class="alert alert-info mt-2">
    <strong>Excel Format:</strong><br>

    Student Number | Last Name | First Name |
    Middle Name | Suffix | Gender |
    Course | Year Level | Section

    <hr>

    Example:<br>

    2024-0001 | Dela Cruz | Juan |
    Santos | | Male |
    BSIT | 1 | Xiaomi
</div>
            <form
                method="post"
                enctype="multipart/form-data"
                action="pages/import_students.php">
                <?= csrf_field() ?>

                <div class="modal-header">
                    <h5 class="modal-title">
                        Import Students from Excel
                    </h5>
                </div>
		
	
                <div class="modal-body">

                    <input
                        type="file"
                        name="excel_file"
                        class="form-control"
                        accept=".xlsx"
                        required>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        Import
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>
<?php endif; ?>

