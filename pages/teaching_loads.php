

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
            id="add_sectionID">

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

<script>

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

    fetch('', {
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

</script>