<?php
require_once __DIR__ . '/../includes/sections_controller.php';
?>
<div id="sectionsPageConfig" data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>" data-manage-all="<?= $canManageAllSections ? '1' : '0' ?>"></div>
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
          <th>Adviser</th>
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
