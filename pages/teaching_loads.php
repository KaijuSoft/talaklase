<?php
require_once __DIR__ . '/../includes/teaching_loads_controller.php';
?>

<div class="card" data-csrf-token="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

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
            <th class="d-none d-lg-table-cell">Start</th>
            <th class="d-none d-lg-table-cell">End</th>
            <th class="d-lg-none">Schedule</th>
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

                    <td class="d-none d-lg-table-cell">
                        <?= htmlspecialchars(formatTeachingLoadTime($load['start_time'])) ?>
                    </td>

                    <td class="d-none d-lg-table-cell">
                        <?= htmlspecialchars(formatTeachingLoadTime($load['end_time'])) ?>
                    </td>

                    <td class="d-lg-none">
                        <?= htmlspecialchars(
                            formatTeachingLoadTime($load['start_time']) && formatTeachingLoadTime($load['end_time'])
                                ? formatTeachingLoadTime($load['start_time']) . ' - ' . formatTeachingLoadTime($load['end_time'])
                                : ''
                        ) ?>
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
        <?= (int)$load['inst_id'] ?>,
        '<?= htmlspecialchars($load['start_time'] ?? '', ENT_QUOTES) ?>',
        '<?= htmlspecialchars($load['end_time'] ?? '', ENT_QUOTES) ?>'
    )">

    Edit

</button>

                    <button
                        class="btn btn-sm btn-outline-primary ms-1"
                        onclick="importSectionStudents(<?= (int)$load['assignment_id'] ?>)">

                        Import Section Students

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

    <div class="row g-2">

        <div class="col-md-6 mb-3">

            <label class="form-label">
                Start Time
            </label>

            <input
                type="time"
                class="form-control"
                id="add_start_time"
                required>

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">
                End Time
            </label>

            <input
                type="time"
                class="form-control"
                id="add_end_time"
                required>

        </div>

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

    <div class="row g-2">

        <div class="col-md-6 mb-3">

            <label class="form-label">
                Start Time
            </label>

            <input
                type="time"
                class="form-control"
                id="edit_start_time"
                required>

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">
                End Time
            </label>

            <input
                type="time"
                class="form-control"
                id="edit_end_time"
                required>

        </div>

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
