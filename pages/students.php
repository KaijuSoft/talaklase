<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_students');
$pdo = getConnection();

$currentUser = current_user();
$isInstructorScoped = in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true);
$ownedSectionIds = current_user_owned_section_ids($pdo);
$sectionScope = [
  'clause' => '',
  'params' => [],
  'section_ids' => $ownedSectionIds,
];

if ($isInstructorScoped) {
  if (empty($ownedSectionIds)) {
    $sectionScope['clause'] = '1=0';
  } else {
    $placeholders = [];
    foreach ($ownedSectionIds as $index => $sectionId) {
      $key = ':sec' . $index;
      $placeholders[] = $key;
      $sectionScope['params'][$key] = $sectionId;
    }
    $sectionScope['clause'] = 'student_section.sectionID IN (' . implode(',', $placeholders) . ')';
  }
}

function instructorCanUseSection(array $sectionScope, int $sectionId): bool {
    return !empty($sectionScope['section_ids']) && in_array($sectionId, $sectionScope['section_ids'], true);
}

// ---------- ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    if ($action === 'add') {
        require_permission('edit_students');
        if ($isInstructorScoped && !instructorCanUseSection($sectionScope, (int)($_POST['section_id'] ?? 0))) {
            echo json_encode(['success'=>false,'message'=>'You can only add students to sections you created.']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            // Duplicate check
            $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE st_lastname=? AND st_name=? AND st_middlename=? AND st_suffix=? AND course_id=?");
            $chk->execute([
                ucwords(strtolower($_POST['lastname'])),
                ucwords(strtolower($_POST['firstname'])),
                ucwords(strtolower($_POST['middlename'])),
                ucwords(strtolower($_POST['suffix'])),
                $_POST['course_id']
            ]);
            if ($chk->fetchColumn() > 0) {
                $pdo->rollBack();
                echo json_encode(['success'=>false,'message'=>'Duplicate student record found.']);
                exit;
            }
            $ins = $pdo->prepare("INSERT INTO student (st_lastname,st_name,st_middlename,st_suffix,st_gender,course_id) VALUES (?,?,?,?,?,?)");
            $ins->execute([
                ucwords(strtolower($_POST['lastname'])),
                ucwords(strtolower($_POST['firstname'])),
                ucwords(strtolower($_POST['middlename'])),
                ucwords(strtolower($_POST['suffix'])),
                $_POST['gender'],
                $_POST['course_id']
            ]);
            $stId = $pdo->lastInsertId();
			$ayId = current_ay_id($pdo);

            $sec = $pdo->prepare("INSERT INTO student_section (st_id,sectionID,yearlvl,ay_id) VALUES (?,?,?,?)");
            $sec->execute([$stId, $_POST['section_id'], $_POST['year_level'], $ayId]);
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'Student added successfully.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        require_permission('edit_students');
        if ($isInstructorScoped && !instructorCanUseSection($sectionScope, (int)($_POST['section_id'] ?? 0))) {
            echo json_encode(['success'=>false,'message'=>'You can only update students in sections you created.']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE student SET st_lastname=?,st_name=?,st_middlename=?,st_suffix=?,st_gender=?,course_id=? WHERE st_id=?");
            $upd->execute([$_POST['lastname'],$_POST['firstname'],$_POST['middlename'],$_POST['suffix'],$_POST['gender'],$_POST['course_id'],$_POST['st_id']]);
            $updSec = $pdo->prepare("UPDATE student_section SET sectionID=?,yearlvl=? WHERE st_id=?");
            $updSec->execute([$_POST['section_id'],$_POST['year_level'],$_POST['st_id']]);
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'Student updated.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        require_permission('edit_students');
        try {
            if ($isInstructorScoped) {
                $sectionCheck = $pdo->prepare("SELECT ss.sectionID FROM student_section ss WHERE ss.st_id=? LIMIT 1");
                $sectionCheck->execute([$_POST['st_id']]);
                $currentSectionId = (int)$sectionCheck->fetchColumn();
                if (!instructorCanUseSection($sectionScope, $currentSectionId)) {
                    echo json_encode(['success'=>false,'message'=>'You can only delete students from sections you created.']);
                    exit;
                }
            }
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM student_section WHERE st_id=?")->execute([$_POST['st_id']]);
            $pdo->prepare("DELETE FROM student WHERE st_id=?")->execute([$_POST['st_id']]);
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'Student deleted.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }
}

// ---------- LOAD DATA ----------
$page     = max(1, intval($_GET['p'] ?? 1));
$pageSize = 15;
$search   = trim($_GET['q'] ?? '');
$offset   = ($page - 1) * $pageSize;

$whereParts = [];
$params = [];

if ($search !== '') {
  $whereParts[] = "(student.st_lastname LIKE :q OR student.st_name LIKE :q OR student.st_middlename LIKE :q)";
  $params[':q'] = "%$search%";
}

if ($sectionScope['clause'] !== '') {
  $whereParts[] = $sectionScope['clause'];
  $params = array_merge($params, $sectionScope['params']);
}

$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$countSql = "SELECT COUNT(*) FROM student INNER JOIN student_section ON student.st_id=student_section.st_id $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalRecords / $pageSize));
$page         = min($page, $totalPages);

$sql = "SELECT student.st_id, student.st_lastname, student.st_name, student.st_middlename,
               student.st_suffix, student.st_gender, course.course_acronym,
               student_section.yearlvl, section.section
        FROM student
        INNER JOIN course ON student.course_id = course.course_id
        INNER JOIN student_section ON student.st_id = student_section.st_id
        INNER JOIN section ON section.sectionID = student_section.sectionID
        $where
        ORDER BY student.st_lastname ASC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $pageSize, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

// Dropdowns
$courses  = $pdo->query("SELECT course_id, course_acronym FROM course ORDER BY course_acronym")->fetchAll();
$sectionsSql = "SELECT sectionID, section FROM section";
$sectionsParams = [];
if ($isInstructorScoped) {
  if (empty($sectionScope['section_ids'])) {
    $sectionsSql .= " WHERE 1=0";
  } else {
    $sectionsSql .= " WHERE sectionID IN (" . implode(',', array_fill(0, count($sectionScope['section_ids']), '?')) . ")";
    $sectionsParams = $sectionScope['section_ids'];
  }
}
$sectionsSql .= " ORDER BY section";
$sectionsStmt = $pdo->prepare($sectionsSql);
$sectionsStmt->execute($sectionsParams);
$sections = $sectionsStmt->fetchAll();
$years    = ['1st Year','2nd Year','3rd Year','4th Year'];
$genders  = ['Male','Female'];
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

<!-- Stats row -->

  <?php
  $maleWhere = $where ? $where . " AND student.st_gender='Male'" : "WHERE student.st_gender='Male'";
  $femaleWhere = $where ? $where . " AND student.st_gender='Female'" : "WHERE student.st_gender='Female'";
  $maleSql = "SELECT COUNT(*) FROM student INNER JOIN student_section ON student.st_id=student_section.st_id $maleWhere";
  $femaleSql = "SELECT COUNT(*) FROM student INNER JOIN student_section ON student.st_id=student_section.st_id $femaleWhere";
  $maleStmt = $pdo->prepare($maleSql);
  $femaleStmt = $pdo->prepare($femaleSql);
  $maleStmt->execute($params);
  $femaleStmt->execute($params);
  $male   = $maleStmt->fetchColumn();
  $female = $femaleStmt->fetchColumn();
  ?>
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
      <input type="hidden" name="page" value="students"/>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search students…" value="<?= htmlspecialchars($search) ?>" style="width:220px"/>
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
      <tbody>
        <?php if (empty($students)): ?>
          <tr><td colspan="<?= can('edit_students') ? 10 : 9 ?>" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No students found.</td></tr>
        <?php else: foreach ($students as $i => $s): ?>
          <tr>
            <td class="text-muted"><?= $offset + $i + 1 ?></td>
            <td><?= htmlspecialchars($s['st_lastname']) ?></td>
            <td><?= htmlspecialchars($s['st_name']) ?></td>
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
    <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?> &mdash; <?= $totalRecords ?> records</small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="?page=students&p=<?= $page-1 ?>&q=<?= urlencode($search) ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
        <?php for ($pg=max(1,$page-2); $pg<=min($totalPages,$page+2); $pg++): ?>
          <li class="page-item <?= $pg==$page?'active':'' ?>">
            <a class="page-link" href="?page=students&p=<?= $pg ?>&q=<?= urlencode($search) ?>"><?= $pg ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
          <a class="page-link" href="?page=students&p=<?= $page+1 ?>&q=<?= urlencode($search) ?>"><i class="bi bi-chevron-right"></i></a>
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
          <div class="col-md-4"><label class="form-label">Last Name *</label><input type="text" class="form-control" id="add_lastname" required/></div>
          <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" class="form-control" id="add_firstname" required/></div>
          <div class="col-md-3"><label class="form-label">Middle Name</label><input type="text" class="form-control" id="add_middlename"/></div>
          <div class="col-md-1"><label class="form-label">Suffix</label><input type="text" class="form-control" id="add_suffix"/></div>
          <div class="col-md-3">
            <label class="form-label">Gender *</label>
            <select class="form-select" id="add_gender">
              <option value="">Select…</option>
              <?php foreach ($genders as $g): ?><option><?= $g ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Course *</label>
            <select class="form-select" id="add_course">
              <option value="">Select…</option>
              <?php foreach ($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_acronym']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Section *</label>
            <select class="form-select" id="add_section">
              <option value="">Select…</option>
              <?php foreach ($sections as $s): ?><option value="<?= $s['sectionID'] ?>"><?= htmlspecialchars($s['section']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Year Level *</label>
            <select class="form-select" id="add_year">
              <option value="">Select…</option>
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
<div class="modal fade" id="importStudentsModal">
    <div class="modal-dialog">
        <div class="modal-content">
						<div class="alert alert-info mt-2">
    <strong>Excel Format:</strong><br>

    Last Name | First Name | Middle Name |
    Suffix | Gender | Course |
    Section | Year Level

    <hr>

    Example:<br>

    Dela Cruz | Juan | Santos |
    | Male | BSIT |
    Xiaomi | 1
</div>
            <form
                method="post"
                enctype="multipart/form-data"
                action="pages/import_students.php">

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

<script>
const canManageStudents = <?= can('edit_students') ? 'true' : 'false' ?>;
function saveStudent() {
  if (!canManageStudents) { showToast('You do not have permission to manage students.','danger'); return; }
  const data = {
    action:'add',
    lastname:  document.getElementById('add_lastname').value.trim(),
    firstname: document.getElementById('add_firstname').value.trim(),
    middlename:document.getElementById('add_middlename').value.trim(),
    suffix:    document.getElementById('add_suffix').value.trim(),
    gender:    document.getElementById('add_gender').value,
    course_id: document.getElementById('add_course').value,
    section_id:document.getElementById('add_section').value,
    year_level: document.getElementById('add_year').value,
  };
  if (!data.lastname||!data.firstname||!data.gender||!data.course_id||!data.section_id||!data.year_level) {
    showToast('Please fill in all required fields.','warning'); return;
  }
  fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data)})
  .then(r=>r.json()).then(res=>{
    if(res.success){showToast(res.message);bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();setTimeout(()=>location.reload(),800);}
    else showToast(res.message,'danger');
  });
}

function openEdit(s) {
  document.getElementById('edit_id').value=s.st_id;
  document.getElementById('edit_lastname').value=s.st_lastname;
  document.getElementById('edit_firstname').value=s.st_name;
  document.getElementById('edit_middlename').value=s.st_middlename;
  document.getElementById('edit_suffix').value=s.st_suffix;
  document.getElementById('edit_gender').value=s.st_gender;
  // Course & section selects — match by text
  [...document.getElementById('edit_course').options].forEach(o=>{if(o.text===s.course_acronym)o.selected=true;});
  [...document.getElementById('edit_section').options].forEach(o=>{if(o.text===s.section)o.selected=true;});
  document.getElementById('edit_year').value=s.yearlvl;
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateStudent() {
  if (!canManageStudents) { showToast('You do not have permission to manage students.','danger'); return; }
  const data = {
    action:'update',
    st_id:     document.getElementById('edit_id').value,
    lastname:  document.getElementById('edit_lastname').value.trim(),
    firstname: document.getElementById('edit_firstname').value.trim(),
    middlename:document.getElementById('edit_middlename').value.trim(),
    suffix:    document.getElementById('edit_suffix').value.trim(),
    gender:    document.getElementById('edit_gender').value,
    course_id: document.getElementById('edit_course').value,
    section_id:document.getElementById('edit_section').value,
    year_level: document.getElementById('edit_year').value,
  };
  fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data)})
  .then(r=>r.json()).then(res=>{
    if(res.success){showToast(res.message);bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();setTimeout(()=>location.reload(),800);}
    else showToast(res.message,'danger');
  });
}

function deleteStudent(id, name) {
  if (!canManageStudents) { showToast('You do not have permission to manage students.','danger'); return; }
  if (!confirm(`Delete student "${name}"? This cannot be undone.`)) return;
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({action:'delete',st_id:id})})
  .then(r=>r.json()).then(res=>{
    showToast(res.message, res.success?'success':'danger');
    if(res.success) setTimeout(()=>location.reload(),800);
  });
}

function printStudents() {
  const q = '<?= urlencode($search) ?>';
  window.open('print.php?type=students&q=' + q, '_blank');
}
</script>
