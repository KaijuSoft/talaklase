<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('edit_attendance');
$pdo = getConnection();

$currentUser = current_user();
$ownedSectionIds = current_user_owned_section_ids($pdo);
$isInstructorScoped = in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true);

function attendanceSectionScope(array $ownedSectionIds, bool $isInstructorScoped): array {
  if (!$isInstructorScoped) {
    return ['sql' => '', 'params' => []];
  }

  if (empty($ownedSectionIds)) {
    return ['sql' => ' AND 1=0', 'params' => []];
  }

  $placeholders = [];
  $params = [];
  foreach ($ownedSectionIds as $index => $sectionId) {
    $key = ':sec' . $index;
    $placeholders[] = $key;
    $params[$key] = $sectionId;
  }

  return [
    'sql' => ' AND ss.sectionID IN (' . implode(',', $placeholders) . ')',
    'params' => $params,
  ];
}

$sectionScope = attendanceSectionScope($ownedSectionIds, $isInstructorScoped);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ── Load all students (default on load, matches Student_Section() query) ──
    if ($action === 'load_all') {
      $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename) AS FullName,
            sec.section
            FROM student s
            INNER JOIN student_section ss ON ss.st_id=s.st_id
            INNER JOIN section sec ON sec.sectionID=ss.sectionID
        WHERE 1=1{$sectionScope['sql']}
        ORDER BY sec.section, s.st_lastname");
      $stmt->execute($sectionScope['params']);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ── Search by Section ─────────────────────────────────────────────────────
    if ($action === 'search_section') {
        $q = trim($_POST['q'] ?? '');
        $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_lastname,', ',IFNULL(s.st_name,''),' ',s.st_middlename) AS FullName,
            sec.section
            FROM student s
            INNER JOIN student_section ss ON s.st_id=ss.st_id
            INNER JOIN section sec ON ss.sectionID=sec.sectionID
          WHERE (:empty='' OR sec.section LIKE :prefix)
          {$sectionScope['sql']}
            ORDER BY sec.section, s.st_lastname");
        $stmt->execute(array_merge([':empty'=>$q, ':prefix'=>$q.'%'], $sectionScope['params']));
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ── Search by Name ────────────────────────────────────────────────────────
    if ($action === 'search_name') {
        $q = trim($_POST['q'] ?? '');
        $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_name,' ',s.st_lastname) AS FullName,
            sec.section
            FROM student s
            INNER JOIN student_section ss ON s.st_id=ss.st_id
            INNER JOIN section sec ON ss.sectionID=sec.sectionID
          WHERE (:empty='' OR s.st_name LIKE :pat OR s.st_lastname LIKE :pat2)
          {$sectionScope['sql']}
            ORDER BY s.st_lastname");
        $stmt->execute(array_merge([':empty'=>$q, ':pat'=>"%$q%", ':pat2'=>"%$q%"], $sectionScope['params']));
        echo json_encode($stmt->fetchAll());
        exit;
    }
// ── Load Teaching Loads ─────────────────────────────────────
if ($action === 'load_teaching_loads') {

    $ayId = current_ay_id($pdo);

    $stmt = $pdo->prepare("
        SELECT
            ta.assignment_id,
            ta.sectionID,
            sec.section,
            subj.sub_code,
            subj.sub_name,
            i.inst_name

        FROM teaching_assignments ta

        INNER JOIN section sec
            ON sec.sectionID = ta.sectionID

        INNER JOIN subject subj
            ON subj.sub_id = ta.sub_id

        INNER JOIN instructor i
            ON i.inst_id = ta.inst_id

        WHERE ta.is_active = 1
          AND ta.ay_id = ?

        ORDER BY
            sec.section,
            subj.sub_code
    ");

    $stmt->execute([$ayId]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}

if ($action === 'load_students_by_assignment') {

    $assignmentId =
        (int)($_POST['assignment_id'] ?? 0);

    $ayId =
        current_ay_id($pdo);

    $stmt = $pdo->prepare("
        SELECT
            s.st_id AS ID,

            ss.sectionID AS SecID,

            ta.assignment_id,

            CONCAT(
                s.st_lastname,
                ', ',
                s.st_name,
                ' ',
                IFNULL(
                    s.st_middlename,
                    ''
                )
            ) AS FullName,

            sec.section

        FROM teaching_assignments ta

        INNER JOIN section sec
            ON sec.sectionID = ta.sectionID

        INNER JOIN student_section ss
            ON ss.sectionID = ta.sectionID

        INNER JOIN student s
            ON s.st_id = ss.st_id

        WHERE
            ta.assignment_id = ?
            AND ss.ay_id = ?

        ORDER BY
            s.st_lastname,
            s.st_name
    ");

    $stmt->execute([
        $assignmentId,
        $ayId
    ]);

    echo json_encode(
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        )
    );

    exit;
}

if ($action === 'load_existing') {

    $assignmentId = (int)$_POST['assignment_id'];
    $date         = $_POST['date'];
    $term         = $_POST['term'];

    $stmt = $pdo->prepare("
        SELECT
            attendance.Att_ID,
            attendance.st_id,
            attendance.status,
			attendance.assignment_id,
            student.st_lastname,
            student.st_name,
            student.st_middlename,
            student.st_suffix
        FROM attendance
        INNER JOIN student
            ON student.st_id = attendance.st_id
        WHERE attendance.assignment_id = ?
        AND attendance._date = ?
        AND attendance.term = ?
        ORDER BY student.st_lastname
    ");

    $stmt->execute([
        $assignmentId,
        $date,
        $term
    ]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}
    // ── Save attendance (INSERT) ───────────────────────────────────────────────
    if ($action === 'save') {
$records = json_decode($_POST['records'], true);
$date    = $_POST['date'];
$term    = $_POST['term'];
$ayId = current_ay_id($pdo);
$timeIn = date('Y-m-d H:i:s');
        try {
            $pdo->beginTransaction();
			$chk = $pdo->prepare("
    SELECT COUNT(*)
    FROM attendance
    WHERE assignment_id = ?
      AND _date = ?
      AND term = ?
");

$chk->execute([
    $records[0]['assignment_id'],
    $date,
    $term
]);

if ($chk->fetchColumn() > 0) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Attendance already exists for this date and term. Use Update instead.'
    ]);

    exit;
}
           $ins = $pdo->prepare("INSERT INTO attendance(st_id,sectionID,assignment_id,_date,status,term,ay_id,time_in) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($records as $r) {
                $ins->execute([$r['st_id'], $r['sectionID'], $r['assignment_id'], $date, $r['status'], $term, $ayId, $timeIn]);
            }
            $pdo->commit();


            echo json_encode(['success'=>true,'message'=>'Attendance saved successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'Error saving attendance: '.$e->getMessage()]);
        }
        exit;
    }

    // ── Load existing attendance for editing (EditAtt) ────────────────────────
    /*if ($action === 'load_edit') {*/
        $q    = trim($_POST['q'] ?? '');
        $term = $_POST['term'];
        $date = $_POST['date'];
        // Match VB: search by section or name, then load existing attendance status
        $stmt = $pdo->prepare("SELECT s.st_id AS ID, ss.sectionID AS SecID,
            CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename) AS FullName,
            sec.section,
            IFNULL(a.status,'Absent') AS status
            FROM student s
            INNER JOIN student_section ss ON s.st_id=ss.st_id
            INNER JOIN section sec ON sec.sectionID=ss.sectionID
            LEFT JOIN attendance a ON a.st_id=s.st_id AND a.sectionID=ss.sectionID AND a._date=:date AND a.term=:term
          WHERE (sec.section LIKE :pat OR s.st_lastname LIKE :pat2)
          {$sectionScope['sql']}
            ORDER BY sec.section, s.st_lastname");
        $stmt->execute(array_merge([':pat'=>$q.'%', ':pat2'=>"%$q%", ':date'=>$date, ':term'=>$term], $sectionScope['params']));
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ── Update attendance (Editattendance) ────────────────────────────────────
   if ($action === 'update') {
    $records = json_decode($_POST['records'], true);
    $date = $_POST['date'] ?? '';
    $term = $_POST['term'] ?? '';

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare("
            UPDATE attendance
            SET status = ?
            WHERE st_id = ?
              AND assignment_id = ?
              AND _date = ?
              AND term = ?
        ");

        foreach ($records as $r) {
            $upd->execute([
                $r['status'],
                $r['st_id'],
                $r['assignment_id'],
                $date,
                $term
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Attendance updated successfully!'
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error updating attendance: ' . $e->getMessage()
        ]);
	 }
			exit;
  }
}
?>

<div class="card">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-calendar-check-fill me-2 text-primary"></i>Attendance</h6>
  </div>
  <div class="card-body">

    <!-- Controls row — mirrors VB: TxtSearch, CBSearchSelect, CmbTerm, DtpAttendance -->
    <div class="row g-3 mb-3">

    <div class="col-md-6">

        <label class="form-label">
            Teaching Load
        </label>

        <select
            id="assignment_id"
            class="form-select">

            <option value="">
                Loading...
            </option>

        </select>

    </div>

    <div class="col-md-2">

        <label class="form-label">
            Term
        </label>
        <select
            class="form-select"
            id="att_term">
            <option>Prelim</option>
            <option>Midterm</option>
            <option>Pre-Finals</option>
            <option>Finals</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">
            Date
        </label>
        <input
            type="date"
            class="form-control"
            id="att_date"
            value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-2 d-grid">
        <label class="form-label">
            &nbsp;
        </label>
        <button
            class="btn btn-primary"
            onclick="loadStudentsByAssignment()">
            Load Students
        </button>
    </div>
</div>

    <!-- Select All checkbox + Edit/Update button row -->
    <div id="bulkRow" class="d-none d-flex flex-wrap gap-2 align-items-center mb-2">
      <div class="form-check me-3">
        <input class="form-check-input" type="checkbox" id="chkSelectAll" onchange="selectAll(this.checked)"/>
        <label class="form-check-label" for="chkSelectAll">Select All Present</label>
      </div>
      <button class="btn btn-sm btn-outline-success" onclick="setAllStatus('Present')"><i class="bi bi-check-all me-1"></i>All Present</button>
      <button class="btn btn-sm btn-outline-danger"  onclick="setAllStatus('Absent')"><i class="bi bi-x-circle me-1"></i>All Absent</button>
      <button class="btn btn-sm btn-outline-warning" onclick="setAllStatus('Late')"><i class="bi bi-clock me-1"></i>All Late</button>
    </div>

    <!-- Student grid -->
    <div id="attGrid">
      <!-- loaded via JS -->
    </div>

    <!-- Save / Edit / Update buttons -->
    <div id="attActions" class="d-none mt-3 d-flex gap-2 flex-wrap">
      <button id="btnSave" class="btn btn-success" onclick="saveAttendance()">
        <i class="bi bi-floppy-fill me-1"></i> Save Attendance
      </button>
      <button id="btnEdit" class="btn btn-warning text-white" onclick="editMode()">
        <i class="bi bi-pencil-fill me-1"></i> Edit
      </button>
      <button id="btnUpdate" class="btn btn-primary d-none" onclick="updateAttendance()">
        <i class="bi bi-check-lg me-1"></i> Update
      </button>
    </div>

  </div>
</div>

<script>
let attStudents = [];
let isEditMode  = false;

// On page load — load all students (mirrors AttendancePage_Load)
window.addEventListener(
    'DOMContentLoaded',
    () => {
        loadTeachingLoads();
    }
);


function loadTeachingLoads() {
	
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'load_teaching_loads'
        })
    })
    .then(r => r.json())
    .then(rows => {
        const ddl =
            document.getElementById(
                'assignment_id'
            );
        ddl.innerHTML =
            '<option value="">Select Teaching Load</option>';
        rows.forEach(row => {
            ddl.innerHTML += `
                <option
                    value="${row.assignment_id}"
                    data-section="${row.sectionID}">
                    ${row.section}
                    - ${row.sub_code}
                    (${row.inst_name})
                </option>
            `;
        });
    });
}

function loadStudentsByAssignment() {

    const assignmentId = document.getElementById('assignment_id').value;
    const date         = document.getElementById('att_date').value;
    const term         = document.getElementById('att_term').value;

    if (!assignmentId) {
        showToast('Select a teaching load.', 'warning');
        return;
    }

    // Step 1 — load the student roster for this assignment
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'load_students_by_assignment',
            assignment_id: assignmentId
        })
    })
    .then(r => r.json())
    .then(students => {

        if (!date || !term) {
            renderGrid(students, false);
            return;
        }

        // Step 2 — check if attendance already exists for this load + date + term
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'load_existing',
                assignment_id: assignmentId,
                date: date,
                term: term
            })
        })
        .then(r => r.json())
        .then(existing => {

            if (existing.length > 0) {
                // Merge saved statuses into the roster so the grid pre-fills correctly
                const statusMap = {};
                existing.forEach(e => { statusMap[e.st_id] = e.status; });
                students.forEach(s => { s.status = statusMap[s.ID] || 'Absent'; });

                renderGrid(students, true);
                showToast('Existing attendance found. Update mode enabled.', 'info');

            } else {
                renderGrid(students, false);
            }
        });
    });
}

function checkExistingAttendance() {

    const assignmentId = document.getElementById('assignment_id').value;
    const date         = document.getElementById('att_date').value;
    const term         = document.getElementById('att_term').value;

    if (!assignmentId || !date || !term) return;

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'load_existing',
            assignment_id: assignmentId,
            date: date,
            term: term
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.length > 0) {
            showToast('Attendance already recorded for this selection. Click Load Students to update.', 'info');
        }
    });

}

/*function searchStudents() {
  const q  = document.getElementById('att_search').value.trim();
  const by = document.getElementById('att_search_by').value;

  if (!by) { showToast('Please select a search option (Section or Name).','warning'); return; }

  const actionMap = {Section:'search_section', Name:'search_name'};
  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:actionMap[by], q})})
  .then(r=>r.json()).then(data => renderGrid(data, false));
}*/

function renderGrid(data, editData) {
  attStudents = data;
  isEditMode  = !!editData;

  if (!data.length) {
    document.getElementById('attGrid').innerHTML = '<div class="alert alert-info">No students found.</div>';
    document.getElementById('bulkRow').classList.add('d-none');
    document.getElementById('attActions').classList.add('d-none');
    return;
  }

  document.getElementById('bulkRow').classList.remove('d-none');
  document.getElementById('attActions').classList.remove('d-none');

  let html = `<div class="table-responsive">
    <table class="table table-sm table-hover table-bordered mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:35px">#</th>
        <th>Full Name</th>
        <th style="width:100px">Section</th>
        <th style="width:220px">Status</th>
      </tr>
    </thead><tbody>`;

  data.forEach((s, i) => {
    const status = editData ? (s.status || 'Absent') : 'Present';
    const pChk = status==='Present' ? 'checked' : '';
    const aChk = status==='Absent'  ? 'checked' : '';
    const lChk = status==='Late'    ? 'checked' : '';

    html += `<tr>
      <td class="text-muted">${i+1}</td>
      <td>${s.FullName}</td>
      <td><span class="badge bg-secondary-subtle text-secondary border">${s.section}</span></td>
      <td>
        <div class="btn-group btn-group-sm" role="group">
          <input type="radio" class="btn-check" name="st_${s.ID}" id="p_${s.ID}" value="Present" ${pChk}>
          <label class="btn btn-outline-success" for="p_${s.ID}">Present</label>
          <input type="radio" class="btn-check" name="st_${s.ID}" id="a_${s.ID}" value="Absent" ${aChk}>
          <label class="btn btn-outline-danger" for="a_${s.ID}">Absent</label>
          <input type="radio" class="btn-check" name="st_${s.ID}" id="l_${s.ID}" value="Late" ${lChk}>
          <label class="btn btn-outline-warning" for="l_${s.ID}">Late</label>
        </div>
      </td>
    </tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('attGrid').innerHTML = html;

  // Toggle buttons
  document.getElementById('btnSave').classList.toggle('d-none', isEditMode);
  document.getElementById('btnEdit').classList.toggle('d-none', isEditMode);
  document.getElementById('btnUpdate').classList.toggle('d-none', !isEditMode);
}

function selectAll(checked) {
  attStudents.forEach(s => {
    const el = document.getElementById(checked ? `p_${s.ID}` : `a_${s.ID}`);
    if (el) el.checked = true;
  });
}

function setAllStatus(status) {
  const prefix = {Present:'p', Absent:'a', Late:'l'}[status];
  attStudents.forEach(s => {
    const el = document.getElementById(`${prefix}_${s.ID}`);
    if (el) el.checked = true;
  });
  document.getElementById('chkSelectAll').checked = (status === 'Present');
}

function getRecords() {

  return attStudents.map(
    s => ({
      st_id:
        s.ID,
      sectionID:
        s.SecID,
      assignment_id:
        s.assignment_id,
      status:
        document.querySelector(
          `input[name="st_${s.ID}"]:checked`
        )?.value || 'Absent'
    })
  );
}

function saveAttendance() {
  const date = document.getElementById('att_date').value;
  const term = document.getElementById('att_term').value;
  if (!attStudents.length) { showToast('No students loaded.','warning'); return; }

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'save', date, term, records:JSON.stringify(getRecords())})})
  .then(r=>r.json()).then(res => {
    showToast(res.message, res.success?'success':'danger');
    if (res.success) resetPage();
  });
}

/* function editMode() {
  const q    = document.getElementById('att_search').value.trim();
  const term = document.getElementById('att_term').value;
  const date = document.getElementById('att_date').value;

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'load_edit', q, term, date})})
  .then(r=>r.json()).then(data => renderGrid(data, true));
}
 */
function updateAttendance() {

  const date =
      document.getElementById('att_date').value;

  const term =
      document.getElementById('att_term').value;

  fetch('', {
      method:'POST',
      headers:{
          'Content-Type':
              'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
          action:'update',
          date,
          term,
          records:JSON.stringify(
              getRecords()
          )
      })
  })
  .then(r=>r.json())
  .then(res => {
      showToast(
          res.message,
          res.success
              ? 'success'
              : 'danger'
      );

      if (res.success)
          resetPage();
  });
}


function resetPage() {

  const assignment =
    document.getElementById('assignment_id');
  if (assignment) {
    assignment.value = '';
  }
  const chk =
    document.getElementById('chkSelectAll');
  if (chk) {
    chk.checked = false;
  }
  document.getElementById('attActions')
    ?.classList.add('d-none');
  document.getElementById('bulkRow')
    ?.classList.add('d-none');
  isEditMode = false;
  attStudents = [];
  renderGrid([], false);
}

document
.getElementById('assignment_id')
.addEventListener(
    'change',
    checkExistingAttendance
);

document
.getElementById('att_date')
.addEventListener(
    'change',
    checkExistingAttendance
);

document
.getElementById('att_term')
.addEventListener(
    'change',
    checkExistingAttendance
);
</script>
