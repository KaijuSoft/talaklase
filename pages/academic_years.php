<?php
require_once __DIR__ . '/../includes/academic_years_controller.php';
?>
<div id="academicYearsPageConfig" data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"></div>
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
						<?= htmlspecialchars($year['status']) ?>
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
