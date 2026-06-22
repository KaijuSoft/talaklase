<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_sections');

$pdo = getConnection();
$currentUser = current_user();
$canManageAllSections = can('manage_users');
$currentInstId = (int)($currentUser['inst_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!verify_csrf()) {
        echo json_encode(['success' => false, 'message' => 'Invalid session token. Refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sectionName = trim($_POST['section'] ?? '');
        $courseId = trim($_POST['course_id'] ?? '');

        if ($sectionName === '' || $courseId === '') {
            echo json_encode(['success' => false, 'message' => 'Section name and course are required.']);
            exit;
        }

        $ownerInstId = null;
        if ($canManageAllSections) {
            $ownerInstId = trim($_POST['inst_id'] ?? '');
            $ownerInstId = $ownerInstId === '' ? null : (int)$ownerInstId;
        } elseif (in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true)) {
            $ownerInstId = $currentInstId ?: null;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO section (section, course_id, inst_id) VALUES (?, ?, ?)');
            $stmt->execute([$sectionName, $courseId, $ownerInstId]);
			$sectionId = $pdo->lastInsertId();

$instructors = $_POST['instructors'] ?? [];

if (!is_array($instructors)) {
    $instructors = [$instructors];
}

foreach ($instructors as $instId) {
    $stmt = $pdo->prepare("
        INSERT INTO section_instructors
        (sectionID, inst_id)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $sectionId,
        $instId
    ]);
}

			$subjects = $_POST['subjects'] ?? [];

				if (!is_array($subjects)) {
					$subjects = [$subjects];
}

			$subjects = array_filter($subjects);
			$subjects = array_unique($subjects);
			


				foreach ($subjects as $subId) {

					$stmt = $pdo->prepare("
					INSERT INTO section_subjects
						(sectionID, sub_id)
						VALUES (?, ?)
					");

    $stmt->execute([
        $sectionId,
        $subId
    ]);
}
			
            echo json_encode(['success' => true, 'message' => 'Section created.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $sectionId = (int)($_POST['sectionID'] ?? 0);
        $sectionName = trim($_POST['section'] ?? '');
        $courseId = trim($_POST['course_id'] ?? '');

        if ($sectionId <= 0 || $sectionName === '' || $courseId === '') {
            echo json_encode(['success' => false, 'message' => 'Section name and course are required.']);
            exit;
        }

        $rowStmt = $pdo->prepare('SELECT sectionID, inst_id FROM section WHERE sectionID = ? LIMIT 1');
        $rowStmt->execute([$sectionId]);
        $row = $rowStmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Section not found.']);
            exit;
        }

        if (!$canManageAllSections && (int)($row['inst_id'] ?? 0) !== $currentInstId) {
            echo json_encode(['success' => false, 'message' => 'You can only edit sections you created.']);
            exit;
        }

        $ownerInstId = (int)($row['inst_id'] ?? 0) ?: null;
        if ($canManageAllSections) {
            $ownerRaw = trim($_POST['inst_id'] ?? '');
            $ownerInstId = $ownerRaw === '' ? null : (int)$ownerRaw;
        } elseif (in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true)) {
            $ownerInstId = $currentInstId ?: null;
        }

        try {
            $stmt = $pdo->prepare('UPDATE section SET section = ?, course_id = ?, inst_id = ? WHERE sectionID = ?');
            $stmt->execute([$sectionName, $courseId, $ownerInstId, $sectionId]);
			
// Remove old instructor assignments
$pdo->prepare(
    "DELETE FROM section_instructors
     WHERE sectionID = ?"
)->execute([$sectionId]);

// Remove old subject assignments
$pdo->prepare(
    "DELETE FROM section_subjects
     WHERE sectionID = ?"
)->execute([$sectionId]);

// Add new subject assignments
$subjects = $_POST['subjects'] ?? [];

if (!is_array($subjects)) {
    $subjects = [$subjects];
}

$subjects = array_filter($subjects);
$subjects = array_unique($subjects);



foreach ($subjects as $subId) {

    $stmt = $pdo->prepare(
        "INSERT INTO section_subjects
         (sectionID, sub_id)
         VALUES (?, ?)"
    );

    $stmt->execute([
        $sectionId,
        $subId
    ]);
}

// Add new instructor assignments
$instructors = $_POST['instructors'] ?? [];

if (!is_array($instructors)) {
    $instructors = [$instructors];
}

foreach ($instructors as $instId) {

    $stmt = $pdo->prepare(
        "INSERT INTO section_instructors
         (sectionID, inst_id)
         VALUES (?, ?)"
    );

    $stmt->execute([
        $sectionId,
        $instId
    ]);
}
            echo json_encode(['success' => true, 'message' => 'Section updated.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $sectionId = (int)($_POST['sectionID'] ?? 0);
        if ($sectionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid section.']);
            exit;
        }

        $rowStmt = $pdo->prepare('SELECT sectionID, inst_id FROM section WHERE sectionID = ? LIMIT 1');
        $rowStmt->execute([$sectionId]);
        $row = $rowStmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Section not found.']);
            exit;
        }

        if (!$canManageAllSections && (int)($row['inst_id'] ?? 0) !== $currentInstId) {
            echo json_encode(['success' => false, 'message' => 'You can only delete sections you created.']);
            exit;
        }

      try {

    $pdo->prepare(
        "DELETE FROM section_instructors
         WHERE sectionID = ?"
    )->execute([$sectionId]);

    $pdo->prepare(
        'DELETE FROM section
         WHERE sectionID = ?'
    )->execute([$sectionId]);

    echo json_encode([
        'success' => true,
        'message' => 'Section deleted.'
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

$courses = $pdo->query('SELECT course_id, course_acronym FROM course ORDER BY course_acronym')->fetchAll();
$instructors = $pdo->query('SELECT inst_id, inst_name FROM instructor ORDER BY inst_name')->fetchAll();
$subjects = $pdo->query(
    'SELECT sub_id, sub_code, sub_name
     FROM subject
     WHERE is_active = 1
     ORDER BY sub_code'
)->fetchAll();

$sectionSubjects = [];

$stmt = $pdo->query("
    SELECT sectionID, sub_id
    FROM section_subjects
");

foreach ($stmt->fetchAll() as $row) {

    $sectionSubjects[$row['sectionID']][] =
        (int)$row['sub_id'];
}

if ($canManageAllSections) {
    $sectionsStmt = $pdo->query(
    "SELECT
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym,

        GROUP_CONCAT(
            i.inst_name
            ORDER BY i.inst_name
            SEPARATOR ', '
        ) AS inst_name

     FROM section s

     LEFT JOIN course c
        ON c.course_id = s.course_id

     LEFT JOIN section_instructors si
        ON si.sectionID = s.sectionID

     LEFT JOIN instructor i
        ON i.inst_id = si.inst_id

     GROUP BY
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym

     ORDER BY s.section"
);

$sections = $sectionsStmt->fetchAll();
} else {
    $ownedIds = current_user_owned_section_ids($pdo);
    if (empty($ownedIds)) {
        $sections = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($ownedIds), '?'));
        $sectionsStmt = $pdo->prepare(
    "SELECT
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym,

        GROUP_CONCAT(
            i.inst_name
            ORDER BY i.inst_name
            SEPARATOR ', '
        ) AS inst_name

     FROM section s

     LEFT JOIN course c
        ON c.course_id = s.course_id

     LEFT JOIN section_instructors si
        ON si.sectionID = s.sectionID

     LEFT JOIN instructor i
        ON i.inst_id = si.inst_id

     WHERE s.sectionID IN ($placeholders)

     GROUP BY
        s.sectionID,
        s.section,
        s.course_id,
        c.course_acronym

     ORDER BY s.section"
);
	}
        $sectionsStmt->execute($ownedIds);
        $sections = $sectionsStmt->fetchAll();
    }

?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h6 class="mb-1"><i class="bi bi-grid-fill me-2 text-primary"></i>Sections</h6>
      <div class="text-muted small">Create the section first, then add the students who belong to it.</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg me-1"></i> Add Section
    </button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Section</th>
          <th>Course</th>
          <th>Owner</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sections)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">No sections found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($sections as $index => $section): ?>
            <tr>
              <td class="text-muted"><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($section['section'] ?? '') ?></td>
              <td><?= htmlspecialchars($section['course_acronym'] ?? '') ?></td>
              <td><?= htmlspecialchars($section['inst_name'] ?? 'Unassigned') ?></td>
              <td class="text-end">
			  <?php
				$section['subjects'] =
					$sectionSubjects[$section['sectionID']] ?? [];
				?>
				
                <button class="btn btn-sm btn-outline-primary" onclick='openEdit(<?= htmlspecialchars(json_encode($section), ENT_QUOTES, "UTF-8") ?>)'>
                  <i class="bi bi-pencil-fill me-1"></i>Edit
                </button>
                <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteRecord(<?= (int)$section['sectionID'] ?>, '<?= htmlspecialchars($section['section'] ?? '', ENT_QUOTES, 'UTF-8') ?>')">
                  <i class="bi bi-trash-fill me-1"></i>Delete
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Section</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Section Name</label>
          <input type="text" class="form-control" id="add_section" />
        </div>
        <div class="mb-3">
          <label class="form-label">Course</label>
          <select class="form-select" id="add_course_id">
            <option value="">Select course</option>
            <?php foreach ($courses as $course): ?>
              <option value="<?= htmlspecialchars((string)$course['course_id']) ?>"><?= htmlspecialchars($course['course_acronym']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($canManageAllSections): ?>
        <div class="mb-0">
          
         <div class="mb-3">
    <label class="form-label">Instructors</label>

    <?php foreach ($instructors as $instructor): ?>

        <div class="form-check">

            <input
                class="form-check-input"
                type="checkbox"
                name="add_instructors[]"
                value="<?= (int)$instructor['inst_id'] ?>">

            <label class="form-check-label">
                <?= htmlspecialchars($instructor['inst_name']) ?>
            </label>

        </div>

    <?php endforeach; ?>
<div class="mb-3">

    <label class="form-label">Subjects</label>

    <?php foreach ($subjects as $subject): ?>

        <div class="form-check">

            <input
				class="form-check-input add-subject"
				type="checkbox"
				name="subjects[]"
				value="<?= (int)$subject['sub_id'] ?>">

            <label class="form-check-label">

                <?= htmlspecialchars(
                    $subject['sub_code'] . ' - ' . $subject['sub_name']
                ) ?>

            </label>

        </div>

    <?php endforeach; ?>

</div>
</div>

        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveRecord()">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Section</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_sectionID" />
        <div class="mb-3">
          <label class="form-label">Section Name</label>
          <input type="text" class="form-control" id="edit_section" />
        </div>
        <div class="mb-3">
          <label class="form-label">Course</label>
          <select class="form-select" id="edit_course_id">
            <?php foreach ($courses as $course): ?>
              <option value="<?= htmlspecialchars((string)$course['course_id']) ?>"><?= htmlspecialchars($course['course_acronym']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($canManageAllSections): ?>
        <div class="mb-0">
          
         <div class="mb-0">

    <label class="form-label">Instructors</label>

    <?php foreach ($instructors as $instructor): ?>

        <div class="form-check">

            <input
                class="form-check-input edit-instructor"
                type="checkbox"
                value="<?= (int)$instructor['inst_id'] ?>">

            <label class="form-check-label">
                <?= htmlspecialchars($instructor['inst_name']) ?>
            </label>

        </div>

    <?php endforeach; ?>

</div>

			<div class="mb-3">

    <label class="form-label">Subjects</label>

    <?php foreach ($subjects as $subject): ?>

        <div class="form-check">

           <input
			class="form-check-input edit-subject"
			type="checkbox"
			name="subjects[]"
			value="<?= (int)$subject['sub_id'] ?>">

            <label class="form-check-label">

                <?= htmlspecialchars(
                    $subject['sub_code']
                    . ' - '
                    . $subject['sub_name']
                ) ?>

            </label>

        </div>

    <?php endforeach; ?>

</div>
		
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-white" onclick="updateRecord()">Update</button>
      </div>
    </div>
  </div>
</div>

<script>
const canManageAllSections = <?= $canManageAllSections ? 'true' : 'false' ?>;
const csrfToken = <?= json_encode(csrf_token()) ?>;

function postSection(params) {
  params.csrf_token = csrfToken;
  return fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(params)
  }).then(response => response.json());
}

function saveRecord() {

  const params = new URLSearchParams();

  params.append('action', 'add');
  params.append('section', document.getElementById('add_section').value.trim());
  params.append('course_id', document.getElementById('add_course_id').value);

  document
    .querySelectorAll('#addModal input[name="add_instructors[]"]:checked')
    .forEach(cb => {
        params.append('instructors[]', cb.value);
    });
	
	document
  .querySelectorAll('#addModal input[name="subjects[]"]:checked')
  .forEach(cb => {
      params.append('subjects[]', cb.value);
  });




  params.append('csrf_token', csrfToken);

  fetch('', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: params
  })
  .then(r => r.json())

  .then(result => {
      showToast(result.message, result.success ? 'success' : 'danger');

      if (result.success) {
          bootstrap.Modal
              .getInstance(document.getElementById('addModal'))
              .hide();

          setTimeout(() => location.reload(), 700);
      }
  });
}

function openEdit(row) {

    document.getElementById('edit_sectionID').value =
        row.sectionID || '';

    document.getElementById('edit_section').value =
        row.section || '';

    document.getElementById('edit_course_id').value =
        row.course_id || '';

    document.querySelectorAll('.edit-instructor')
        .forEach(cb => cb.checked = false);
		
		document.querySelectorAll('.edit-subject')
    .forEach(cb => cb.checked = false);

    if (row.instructors) {

        row.instructors.forEach(id => {

            document.querySelectorAll('.edit-instructor')
                .forEach(cb => {

                    if (cb.value == id) {
                        cb.checked = true;
                    }

                });

        });

    }
	
	if (row.subjects) {

    row.subjects.forEach(id => {

        document.querySelectorAll('.edit-subject')
            .forEach(cb => {

                if (cb.value == id) {
                    cb.checked = true;
                }

            });

    });

}

    new bootstrap.Modal(
        document.getElementById('editModal')
    ).show();
}


function updateRecord() {

  const params = new URLSearchParams();

  params.append('action', 'update');
  params.append('sectionID',
      document.getElementById('edit_sectionID').value);

  params.append('section',
      document.getElementById('edit_section').value.trim());

  params.append('course_id',
      document.getElementById('edit_course_id').value);

  document
    .querySelectorAll('.edit-instructor:checked')
    .forEach(cb => {
        params.append('instructors[]', cb.value);
    });

document
  .querySelectorAll('.edit-subject:checked')
  .forEach(cb => {
      params.append('subjects[]', cb.value);
  });
   
  params.append('csrf_token', csrfToken);

  fetch('', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: params
  })
  .then(r => r.json())
  .then(result => {

      showToast(result.message,
          result.success ? 'success' : 'danger');

      if (result.success) {

          bootstrap.Modal
              .getInstance(document.getElementById('editModal'))
              .hide();

          setTimeout(() => location.reload(), 700);
      }

  });
}
function deleteRecord(id, name) {
  if (!confirm(`Delete section "${name}"?`)) return;
  postSection({ action: 'delete', sectionID: id }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) setTimeout(() => location.reload(), 700);
  });
}
</script>
