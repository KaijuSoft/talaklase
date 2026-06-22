

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('manage_teaching_loads');

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ]);

        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {

        $sectionID = (int)($_POST['sectionID'] ?? 0);
        $sub_id    = (int)($_POST['sub_id'] ?? 0);
        $inst_id   = (int)($_POST['inst_id'] ?? 0);

        try {

            $stmt = $pdo->prepare("
                INSERT INTO teaching_assignments
                (
                    sectionID,
                    sub_id,
                    inst_id
                )
                VALUES
                (
                    ?, ?, ?
                )
            ");

            $stmt->execute([
                $sectionID,
                $sub_id,
                $inst_id
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Teaching load assigned.'
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }
	
	if ($action === 'update') {

    $assignment_id =
        (int)($_POST['assignment_id'] ?? 0);

    $sectionID =
        (int)($_POST['sectionID'] ?? 0);

    $sub_id =
        (int)($_POST['sub_id'] ?? 0);

    $inst_id =
        (int)($_POST['inst_id'] ?? 0);

    try {

        $stmt = $pdo->prepare("
            UPDATE teaching_assignments
            SET
                sectionID = ?,
                sub_id    = ?,
                inst_id   = ?
            WHERE assignment_id = ?
        ");

        $stmt->execute([
            $sectionID,
            $sub_id,
            $inst_id,
            $assignment_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Teaching load updated.'
        ]);

    } catch (Throwable $e) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}
	
	if ($action === 'get_subjects') {

    $sectionID = (int)($_POST['sectionID'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT
            s.sub_id,
            s.sub_code,
            s.sub_name
        FROM section_subjects ss
        JOIN subject s
            ON s.sub_id = ss.sub_id
        WHERE ss.sectionID = ?
          AND s.is_active = 1
        ORDER BY s.sub_code
    ");

    $stmt->execute([$sectionID]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}

	if ($action === 'get_instructors') {

    $sectionID = (int)($_POST['sectionID'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT
            i.inst_id,
            i.inst_name
        FROM section_instructors si
        JOIN instructor i
            ON i.inst_id = si.inst_id
        WHERE si.sectionID = ?
        ORDER BY i.inst_name
    ");

    $stmt->execute([$sectionID]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    exit;
}

}

		$loads = $pdo->query("
		SELECT
        ta.assignment_id,
        ta.sectionID,
        ta.sub_id,
        ta.inst_id,
        ta.is_active,

        s.section,

        sub.sub_code,
        sub.sub_name,

        i.inst_name

    FROM teaching_assignments ta

    JOIN section s
        ON s.sectionID = ta.sectionID

    JOIN subject sub
        ON sub.sub_id = ta.sub_id

    JOIN instructor i
        ON i.inst_id = ta.inst_id

    ORDER BY
        s.section,
        sub.sub_code
	")->fetchAll();

	$sections = $pdo->query("
    SELECT sectionID, section
    FROM section
    ORDER BY section
	")->fetchAll();

	$subjects = $pdo->query("
    SELECT sub_id, sub_code, sub_name
    FROM subject
    WHERE is_active = 1
    ORDER BY sub_code
	")->fetchAll();

	$instructors = $pdo->query("
    SELECT inst_id, inst_name
    FROM instructor
    ORDER BY inst_name
		")->fetchAll();



?>

<script>
const csrfToken =
    <?= json_encode(csrf_token()) ?>;
</script>

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <div>

            <h6 class="mb-1">
                <i class="bi bi-person-workspace me-2 text-primary"></i>
                Teaching Loads
            </h6>

            <div class="text-muted small">
                Assign instructors to subjects within sections.
            </div>

        </div>

        <button
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#addModal">

            <i class="bi bi-plus-lg me-1"></i>
            Add Teaching Load

        </button>

    </div>

    <div class="table-responsive">

        <table class="table table-hover mb-0 align-middle">

            <thead>

                <tr>

                    <th>Section</th>
                    <th>Subject</th>
                    <th>Instructor</th>
                    <th>Status</th>
					<th>Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($loads as $load): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($load['section']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $load['sub_code']
                            . ' - '
                            . $load['sub_name']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($load['inst_name']) ?>
                    </td>

                    <td>

                        <?=
                            $load['is_active']
                            ? 'Active'
                            : 'Inactive'
                        ?>

                    </td>
					
					<td>

					<button
						class="btn btn-sm btn-warning"
							onclick="editLoad(
					<?= (int)$load['assignment_id'] ?>,
				<?= (int)$load['sectionID'] ?>,
			<?= (int)$load['sub_id'] ?>,
        <?= (int)$load['inst_id'] ?>
    )">

    Edit

</button>

</td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<div class="modal fade" id="addModal" tabindex="-1">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">
    <h5 class="modal-title">
        Add Teaching Load
    </h5>

    <button
        class="btn-close"
        data-bs-dismiss="modal">
    </button>
</div>

<div class="modal-body">

    <div class="mb-3">

        <label class="form-label">
            Section
        </label>

			<select
				class="form-select"
				id="add_sectionID"
				onchange="loadSectionData()">

            <?php foreach ($sections as $section): ?>

                <option
                    value="<?= (int)$section['sectionID'] ?>">

                    <?= htmlspecialchars(
                        $section['section']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Subject
        </label>

        <select
            class="form-select"
            id="add_sub_id">

            <?php foreach ($subjects as $subject): ?>

                <option
                    value="<?= (int)$subject['sub_id'] ?>">

                    <?= htmlspecialchars(
                        $subject['sub_code']
                        . ' - '
                        . $subject['sub_name']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Instructor
        </label>

        <select
            class="form-select"
            id="add_inst_id">

            <?php foreach ($instructors as $instructor): ?>

                <option
                    value="<?= (int)$instructor['inst_id'] ?>">

                    <?= htmlspecialchars(
                        $instructor['inst_name']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>

</div>

<div class="modal-footer">

    <button
        class="btn btn-secondary"
        data-bs-dismiss="modal">

        Cancel

    </button>

    <button
        class="btn btn-primary"
        onclick="saveLoad()">

        Assign

    </button>

</div>

</div>
</div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">

    <h5 class="modal-title">
        Edit Teaching Load
    </h5>

    <button
        class="btn-close"
        data-bs-dismiss="modal">
    </button>

</div>

<div class="modal-body">

    <input
        type="hidden"
        id="edit_assignment_id">

    <div class="mb-3">

        <label class="form-label">
            Section
        </label>

        <select
            class="form-select"
            id="edit_sectionID">

            <?php foreach ($sections as $section): ?>

                <option
                    value="<?= (int)$section['sectionID'] ?>">

                    <?= htmlspecialchars(
                        $section['section']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Subject
        </label>

        <select
            class="form-select"
            id="edit_sub_id">
        </select>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Instructor
        </label>

        <select
            class="form-select"
            id="edit_inst_id">
        </select>

    </div>

</div>

<div class="modal-footer">

    <button
        class="btn btn-secondary"
        data-bs-dismiss="modal">

        Cancel

    </button>

    <button
        class="btn btn-primary"
        onclick="updateLoad()">

        Save Changes

    </button>

</div>

</div>
</div>
</div>

<script>


function loadSectionData() {

    const sectionID =
        document.getElementById(
            'add_sectionID'
        ).value;

    loadSubjects(sectionID);

    loadInstructors(sectionID);
}

function loadSubjects(sectionID) {

    const params = new URLSearchParams();

    params.append('action', 'get_subjects');
    params.append('sectionID', sectionID);
    params.append('csrf_token', csrfToken);

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(subjects => {

        console.log('SUBJECTS', subjects);

        const select =
            document.getElementById('add_sub_id');

        select.innerHTML = '';
		
		if (subjects.length === 0) {

    const option =
        document.createElement('option');

    option.textContent =
        'No subjects assigned to this section';

    option.disabled = true;
    option.selected = true;

    select.appendChild(option);

    return;
}

        subjects.forEach(subject => {

            const option =
                document.createElement('option');

            option.value = subject.sub_id;

            option.textContent =
                subject.sub_code +
                ' - ' +
                subject.sub_name;

            select.appendChild(option);

        });

    });

}

function loadInstructors(sectionID) {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'get_instructors'
    );

    params.append(
        'sectionID',
        sectionID
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
            'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(instructors => {

        const select =
            document.getElementById(
                'add_inst_id'
            );

        select.innerHTML = '';
		
		if (instructors.length === 0) {

    const option =
        document.createElement('option');

    option.textContent =
        'No instructors assigned to this section';

    option.disabled = true;
    option.selected = true;

    select.appendChild(option);

    return;
}

        instructors.forEach(instructor => {

            const option =
                document.createElement('option');

            option.value =
                instructor.inst_id;

            option.textContent =
                instructor.inst_name;

            select.appendChild(option);

        });

    });

}


function loadEditSubjects(
    sectionID,
    selectedSubId
) {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'get_subjects'
    );

    params.append(
        'sectionID',
        sectionID
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(subjects => {

        const select =
            document.getElementById(
                'edit_sub_id'
            );

        select.innerHTML = '';

        subjects.forEach(subject => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                subject.sub_id;

            option.textContent =
                subject.sub_code +
                ' - ' +
                subject.sub_name;

            if (
                parseInt(subject.sub_id)
                ===
                parseInt(selectedSubId)
            ) {
                option.selected = true;
            }

            select.appendChild(
                option
            );

        });

    });

}

function saveLoad() {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'add'
    );

    params.append(
        'sectionID',
        document.getElementById(
            'add_sectionID'
        ).value
    );

    params.append(
        'sub_id',
        document.getElementById(
            'add_sub_id'
        ).value
    );

    params.append(
        'inst_id',
        document.getElementById(
            'add_inst_id'
        ).value
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(result => {

        alert(result.message);

        if (result.success) {

            location.reload();

        }

    });

}

function updateLoad() {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'update'
    );

    params.append(
        'assignment_id',
        document.getElementById(
            'edit_assignment_id'
        ).value
    );

    params.append(
        'sectionID',
        document.getElementById(
            'edit_sectionID'
        ).value
    );

    params.append(
        'sub_id',
        document.getElementById(
            'edit_sub_id'
        ).value
    );

    params.append(
        'inst_id',
        document.getElementById(
            'edit_inst_id'
        ).value
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(result => {

        alert(result.message);

        if (result.success) {

            location.reload();

        }

    });

}

document.addEventListener(
    'DOMContentLoaded',
    () => {

        loadSectionData();

    }
	
);

	document
    .getElementById('addModal')
    .addEventListener(
        'shown.bs.modal',
        function () {

            loadSectionData();

        }
    );
	
	
function loadEditInstructors(
    sectionID,
    selectedInstId
) {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'get_instructors'
    );

    params.append(
        'sectionID',
        sectionID
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(instructors => {

        const select =
            document.getElementById(
                'edit_inst_id'
            );

        select.innerHTML = '';

        instructors.forEach(
            instructor => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                instructor.inst_id;

            option.textContent =
                instructor.inst_name;

            if (
                parseInt(
                    instructor.inst_id
                )
                ===
                parseInt(
                    selectedInstId
                )
            ) {
                option.selected = true;
            }

            select.appendChild(
                option
            );

        });

    });

}
	
function editLoad(
    assignmentId,
    sectionId,
    subId,
    instId
) {

    document.getElementById(
        'edit_assignment_id'
    ).value = assignmentId;

    document.getElementById(
        'edit_sectionID'
    ).value = sectionId;
	
	loadEditSubjects(
    sectionId,
    subId
);

	loadEditInstructors(
    sectionId,
    instId
);

    const modal =
        new bootstrap.Modal(
            document.getElementById(
                'editModal'
            )
        );

    modal.show();

}


	
</script>