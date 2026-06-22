<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/academic_year.php';

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    if (!verify_csrf()) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ]);

        exit;
    }

    $action = $_POST['action'] ?? '';

    try {

        if ($action === 'add') {

            $ayName = trim($_POST['ay_name'] ?? '');
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;

            if ($ayName === '') {

                echo json_encode([
                    'success' => false,
                    'message' => 'Academic year is required.'
                ]);

                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO academic_year
                (
                    ay_name,
                    start_date,
                    end_date,
                    is_active
                )
                VALUES
                (
                    ?, ?, ?, 0
                )
            ");

            $stmt->execute([
                $ayName,
                $startDate,
                $endDate
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Academic year added successfully.'
            ]);

            exit;
        }

        if ($action === 'activate') {

            $ayId = (int)($_POST['ay_id'] ?? 0);

            $pdo->exec("
                UPDATE academic_year
                SET is_active = 0
            ");

            $stmt = $pdo->prepare("
                UPDATE academic_year
                SET is_active = 1
                WHERE ay_id = ?
            ");

            $stmt->execute([$ayId]);

            echo json_encode([
                'success' => true,
                'message' => 'Academic year activated.'
            ]);

            exit;
        }

    } catch (Throwable $e) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

        exit;
    }
}

$years = $pdo->query("
    SELECT *
    FROM academic_year
    ORDER BY ay_id DESC
")->fetchAll();

?>

<div class="card">

   <div class="card-header d-flex justify-content-between align-items-center">

    <h6 class="mb-0">
        Academic Years
    </h6>
<button
        class="btn btn-primary btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#addModal">

        <i class="bi bi-plus-lg me-1"></i>
        Add Academic Year

    </button>
</div>
 
    <div class="table-responsive">

        <table class="table table-hover mb-0">

            <thead>

              <tr>
				<th>Academic Year</th>
				<th>Start</th>
				<th>End</th>
				<th>Status</th>
				<th>Actions</th>
			</tr>

            </thead>

            <tbody>

            <?php foreach ($years as $year): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($year['ay_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($year['start_date']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($year['end_date']) ?>
                    </td>

                    <td>

                        <?=
                            $year['is_active']
                            ? 'Active'
                            : 'Inactive'
                        ?>

                    </td>
					<td>

			<?php if (!$year['is_active']): ?>

			<button
				class="btn btn-sm btn-success"
				onclick="activateYear(
				<?= (int)$year['ay_id'] ?>
			)">

				Activate

			</button>

		<?php else: ?>

		<span class="badge bg-success">
			Current
		</span>

	<?php endif; ?>

</td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>
<div class="modal fade"
     id="addModal"
     tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Add Academic Year
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

    <label class="form-label">
        Academic Year
    </label>

    <input
        type="text"
        class="form-control"
        id="add_ay_name"
        placeholder="AY 2026-2027">

</div>

<div class="mb-3">

    <label class="form-label">
        Start Date
    </label>

    <input
        type="date"
        class="form-control"
        id="add_start_date">

</div>

<div class="mb-3">

    <label class="form-label">
        End Date
    </label>

    <input
        type="date"
        class="form-control"
        id="add_end_date">

</div>

            </div>
<div class="modal-footer">

    <button
        type="button"
        class="btn btn-secondary"
        data-bs-dismiss="modal">

        Cancel

    </button>

    <button
        type="button"
        class="btn btn-primary"
        onclick="saveAcademicYear()">

        Save

    </button>

</div>
        </div>

				</div>
			</div>
        </div>

    </div>

</div>
</div>
    </div>

</div>

<script>
const csrfToken =
    <?= json_encode(csrf_token()) ?>;
</script>

<script>

function saveAcademicYear() {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'add'
    );

    params.append(
        'ay_name',
        document.getElementById(
            'add_ay_name'
        ).value.trim()
    );

    params.append(
        'start_date',
        document.getElementById(
            'add_start_date'
        ).value
    );

    params.append(
        'end_date',
        document.getElementById(
            'add_end_date'
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

function activateYear(ayId) {

    if (!confirm(
        'Activate this academic year?'
    )) {
        return;
    }

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'activate'
    );

    params.append(
        'ay_id',
        ayId
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