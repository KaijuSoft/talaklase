<?php
require_once __DIR__ . '/../includes/instructor_accounts_controller.php';
?>
<div id="instructorAccountsConfig" data-csrf="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" hidden></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h6 class="mb-1"><i class="bi bi-person-vcard-fill me-2 text-primary"></i>Users</h6>
      <div class="text-muted small">Manage administrator and instructor accounts.</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg me-1"></i> Add Account
    </button>
  </div>
  <div class="card-body border-bottom">
    <div class="row g-3">
      <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Total Accounts</div><div class="stat-value fs-4"><?= (int) $summary['total'] ?></div></div></div>
      <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Administrator Accounts</div><div class="stat-value fs-4"><?= (int) $summary['admin'] ?></div></div></div>
      <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Instructor Accounts</div><div class="stat-value fs-4"><?= (int) $summary['instructor'] ?></div></div></div>
      <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Archived Accounts</div><div class="stat-value fs-4"><?= (int) $summary['archived'] ?></div></div></div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <?php foreach ([
        'all' => 'All',
        'admin' => 'Administrators',
        'instructor' => 'Instructors',
        'archived' => 'Archived',
      ] as $key => $label): ?>
        <a href="?page=instructor_accounts&filter=<?= htmlspecialchars($key) ?>" class="btn btn-sm <?= $filter === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">No accounts found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
              <td><?= htmlspecialchars(user_role_label((string) ($row['role'] ?? ''))) ?></td>
              <td>
                <?php if ((int) $row['is_active'] === 1): ?>
                  <span class="badge bg-success">🟢 Active</span>
                <?php else: ?>
                  <span class="badge bg-secondary">⚫ Archived</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ((int) $row['is_active'] === 1): ?>
                  <button class="btn btn-sm btn-outline-primary" onclick='openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>Edit</button>
                  <button class="btn btn-sm btn-outline-secondary ms-1" onclick="resetPassword(<?= (int) $row['id'] ?>)">Reset Password</button>
                  <button class="btn btn-sm btn-outline-warning ms-1" onclick='openArchive(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>Archive</button>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-success" onclick='openRestore(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>Restore</button>
                  <button class="btn btn-sm btn-outline-primary ms-1" onclick='openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>Edit</button>
                <?php endif; ?>
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
        <h5 class="modal-title">Add Account</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" id="add_name" />
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" id="add_role" onchange="toggleDeptForRole()">
            <option value="instructor">Instructor</option>
            <option value="instructor_admin">Instructor Admin</option>
            <option value="admin">Administrator</option>
          </select>
        </div>
        <div class="mb-3" id="addDeptWrap">
          <label class="form-label">Department</label>
          <select class="form-select" id="add_dept_id">
            <option value="">Select department</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= htmlspecialchars((string) $department['dept_id']) ?>"><?= htmlspecialchars($department['dept_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Username / Email</label>
          <input type="email" class="form-control" id="add_email" />
        </div>
        <div class="mb-0">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" id="add_password" />
        </div>
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
        <h5 class="modal-title">Edit Account</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_user_id" />
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" id="edit_name" />
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" id="edit_role" onchange="toggleEditDeptForRole()">
            <option value="instructor">Instructor</option>
            <option value="instructor_admin">Instructor Admin</option>
            <option value="admin">Administrator</option>
          </select>
        </div>
        <div class="mb-3" id="editDeptWrap">
          <label class="form-label">Department</label>
          <select class="form-select" id="edit_dept_id">
            <option value="">Select department</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= htmlspecialchars((string) $department['dept_id']) ?>"><?= htmlspecialchars($department['dept_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Username / Email</label>
          <input type="email" class="form-control" id="edit_email" />
        </div>
        <div class="mb-0">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control" id="edit_password" placeholder="Leave blank to keep current password" />
        </div>
        <div id="edit_history" class="mt-3 d-none">
          <div class="card bg-light">
            <div class="card-body">
              <div class="fw-600 mb-2">Account History</div>
              <div id="edit_history_body" class="small text-muted"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-white" onclick="updateRecord()">Update</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Archive User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">This account will no longer be able to log in.</p>
        <p class="text-muted">Historical records will be preserved.</p>
        <input type="hidden" id="archive_user_id" />
        <div class="mb-0">
          <label class="form-label">Archive Reason</label>
          <textarea class="form-control" id="archive_reason" rows="3" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-white" onclick="archiveUser()">Archive User</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Restore User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="restore_user_id" />
        <div class="mb-0">
          <label class="form-label">Restore Reason</label>
          <textarea class="form-control" id="restore_reason" rows="3" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" onclick="restoreUser()">Restore User</button>
      </div>
    </div>
  </div>
</div>
