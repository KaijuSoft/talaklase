<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_students');
$pdo = getConnection();

// ---------- ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    if ($action === 'add') {
        require_permission('edit_students');
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
            $sec = $pdo->prepare("INSERT INTO student_section (st_id,sectionID,yearlvl) VALUES (?,?,?)");
            $sec->execute([$stId, $_POST['section_id'], $_POST['year_level']]);
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

$where = $search ? "WHERE (student.st_lastname LIKE :q OR student.st_name LIKE :q OR student.st_middlename LIKE :q)" : "";
$params = $search ? [':q' => "%$search%"] : [];

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
$sections = $pdo->query("SELECT sectionID, section FROM section ORDER BY section")->fetchAll();
$years    = ['1st Year','2nd Year','3rd Year','4th Year'];
$genders  = ['Male','Female'];
?>

<!-- Stats row -->
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
  <?php
  $male   = $pdo->query("SELECT COUNT(*) FROM student WHERE st_gender='Male'")->fetchColumn();
  $female = $pdo->query("SELECT COUNT(*) FROM student WHERE st_gender='Female'")->fetchColumn();
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
          <div class="stat-value"><?= $pdo->query("SELECT COUNT(*) FROM section")->fetchColumn() ?></div>
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
