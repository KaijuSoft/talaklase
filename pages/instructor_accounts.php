<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_users');

$pdo = getConnection();
$currentUser = current_user();
$currentUserId = (int) ($currentUser['id'] ?? 0);

function users_module_roles(): array
{
    return ['admin', 'instructor', 'instructor_admin'];
}

function user_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'instructor_admin' => 'Instructor Admin',
        default => 'Instructor',
    };
}

function user_status_label(array $row): string
{
    return ((int) ($row['is_active'] ?? 1) === 1) ? 'Active' : 'Archived';
}

function users_module_count(PDO $pdo, string $where = '1=1', array $params = []): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function users_module_active_admin_count(PDO $pdo): int
{
    return users_module_count($pdo, "role = 'admin' AND is_active = 1");
}

function users_module_account_exists(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.username, u.email, u.role, u.inst_id, u.is_active,
               u.archive_reason, u.archived_at, u.archived_by,
               u.restored_at, u.restored_by, u.restore_reason,
               i.dept_id, d.dept_name,
               ab.name AS archived_by_name,
               rb.name AS restored_by_name
        FROM users u
        LEFT JOIN instructor i ON i.inst_id = u.inst_id
        LEFT JOIN department d ON d.dept_id = i.dept_id
        LEFT JOIN users ab ON ab.id = u.archived_by
        LEFT JOIN users rb ON rb.id = u.restored_by
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function users_module_save_password(PDO $pdo, int $userId, string $password): void
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!verify_csrf()) {
        echo json_encode(['success' => false, 'message' => 'Invalid session token. Refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? 'instructor');
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $deptId = trim($_POST['dept_id'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
            exit;
        }

        if (!in_array($role, users_module_roles(), true)) {
            echo json_encode(['success' => false, 'message' => 'Unsupported account role.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $duplicate = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $duplicate->execute([$email, $email]);
            if ($duplicate->fetchColumn()) {
                throw new RuntimeException('An account with that email already exists.');
            }

            $instId = null;
            if ($role !== 'admin') {
                if ($deptId === '') {
                    throw new RuntimeException('Department is required for instructor accounts.');
                }
                $stmt = $pdo->prepare('INSERT INTO instructor (inst_name, dept_id) VALUES (?, ?)');
                $stmt->execute([$name, $deptId]);
                $instId = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare('
                INSERT INTO users (name, username, email, password_hash, role, inst_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([
                $name,
                $email,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $instId,
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Account created.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $deptId = trim($_POST['dept_id'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($userId <= 0 || $name === '' || $role === '' || $email === '') {
            echo json_encode(['success' => false, 'message' => 'Name, role, and email are required.']);
            exit;
        }

        if (!in_array($role, users_module_roles(), true)) {
            echo json_encode(['success' => false, 'message' => 'Unsupported account role.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $existing = users_module_account_exists($pdo, $userId);
            if (!$existing) {
                throw new RuntimeException('Account not found.');
            }

            $duplicate = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
            $duplicate->execute([$email, $email, $userId]);
            if ($duplicate->fetchColumn()) {
                throw new RuntimeException('Another account already uses that email.');
            }

            if ($role === 'admin') {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, username = ?, email = ?, role = ?, inst_id = NULL WHERE id = ?');
                $stmt->execute([$name, $email, $email, $role, $userId]);
            } else {
                if ($deptId === '') {
                    throw new RuntimeException('Department is required for instructor accounts.');
                }

                if ((int) ($existing['inst_id'] ?? 0) > 0) {
                    $stmt = $pdo->prepare('UPDATE instructor SET inst_name = ?, dept_id = ? WHERE inst_id = ?');
                    $stmt->execute([$name, $deptId, (int) $existing['inst_id']]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO instructor (inst_name, dept_id) VALUES (?, ?)');
                    $stmt->execute([$name, $deptId]);
                    $instId = (int) $pdo->lastInsertId();
                    $existing['inst_id'] = $instId;
                }

                $stmt = $pdo->prepare('UPDATE users SET name = ?, username = ?, email = ?, role = ?, inst_id = ? WHERE id = ?');
                $stmt->execute([$name, $email, $email, $role, (int) $existing['inst_id'], $userId]);
            }

            if ($password !== '') {
                users_module_save_password($pdo, $userId, $password);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Account updated.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'archive') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $reason = trim($_POST['archive_reason'] ?? '');
        $account = users_module_account_exists($pdo, $userId);

        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit;
        }

        if ($userId === $currentUserId) {
            echo json_encode(['success' => false, 'message' => 'You cannot archive the account currently in use.']);
            exit;
        }

        if (($account['role'] ?? '') === 'admin' && users_module_active_admin_count($pdo) <= 1) {
            echo json_encode(['success' => false, 'message' => 'At least one active administrator account must remain in the system.']);
            exit;
        }

        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => 'Archive reason is required.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('UPDATE users SET is_active = 0, archive_reason = ?, archived_at = NOW(), archived_by = ? WHERE id = ?');
            $stmt->execute([$reason, $currentUserId, $userId]);
            echo json_encode(['success' => true, 'message' => 'User archived.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'restore') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $reason = trim($_POST['restore_reason'] ?? '');
        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => 'Restore reason is required.']);
            exit;
        }
        try {
            $stmt = $pdo->prepare('UPDATE users SET is_active = 1, restored_at = NOW(), restored_by = ?, restore_reason = ? WHERE id = ?');
            $stmt->execute([$currentUserId, $reason, $userId]);
            echo json_encode(['success' => true, 'message' => 'User restored.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'reset_password') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');
        if ($password === '') {
            echo json_encode(['success' => false, 'message' => 'A new password is required.']);
            exit;
        }

        try {
            users_module_save_password($pdo, $userId, $password);
            echo json_encode(['success' => true, 'message' => 'Password reset.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

$filters = ['all', 'admin', 'instructor', 'archived'];
$selectedFilter = $_GET['filter'] ?? 'all';
$filter = in_array($selectedFilter, $filters, true)
		? $selectedFilter
		: 'all';

$departments = $pdo->query('SELECT dept_id, dept_name FROM department ORDER BY dept_name')->fetchAll();
$accountRows = $pdo->query("
    SELECT u.id, u.name, u.username, u.email, u.role, u.inst_id, u.is_active,
           u.archive_reason, u.archived_at, u.archived_by,
           u.restored_at, u.restored_by, u.restore_reason,
           i.dept_id, d.dept_name,
           ab.name AS archived_by_name,
           rb.name AS restored_by_name
    FROM users u
    LEFT JOIN instructor i ON i.inst_id = u.inst_id
    LEFT JOIN department d ON d.dept_id = i.dept_id
    LEFT JOIN users ab ON ab.id = u.archived_by
    LEFT JOIN users rb ON rb.id = u.restored_by
    WHERE u.role IN ('admin', 'instructor', 'instructor_admin')
    ORDER BY u.role, u.name
")->fetchAll();

switch ($filter) {
    case 'admin':
        $rows = array_values(array_filter($accountRows, static fn (array $row): bool => $row['role'] === 'admin'));
        break;
    case 'instructor':
        $rows = array_values(array_filter($accountRows, static fn (array $row): bool => in_array($row['role'], ['instructor', 'instructor_admin'], true)));
        break;
    case 'archived':
        $rows = array_values(array_filter($accountRows, static fn (array $row): bool => (int) $row['is_active'] === 0));
        break;
    default:
        $rows = $accountRows;
}

$summary = [
    'total' => count($accountRows),
    'admin' => count(array_filter($accountRows, static fn (array $row): bool => $row['role'] === 'admin')),
    'instructor' => count(array_filter($accountRows, static fn (array $row): bool => in_array($row['role'], ['instructor', 'instructor_admin'], true))),
    'archived' => count(array_filter($accountRows, static fn (array $row): bool => (int) $row['is_active'] === 0)),
];
$csrfToken = csrf_token();
?>

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

<script>
const csrfToken = <?= json_encode($csrfToken) ?>;

function postAccount(params) {
  params.csrf_token = csrfToken;
  return fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(params)
  }).then(response => response.json());
}

function toggleDeptForRole() {
  const role = document.getElementById('add_role')?.value;
  const wrap = document.getElementById('addDeptWrap');
  if (wrap) wrap.classList.toggle('d-none', role === 'admin');
}

function toggleEditDeptForRole() {
  const role = document.getElementById('edit_role')?.value;
  const wrap = document.getElementById('editDeptWrap');
  if (wrap) wrap.classList.toggle('d-none', role === 'admin');
}

function saveRecord() {
  postAccount({
    action: 'add',
    name: document.getElementById('add_name').value.trim(),
    role: document.getElementById('add_role').value,
    dept_id: document.getElementById('add_dept_id').value,
    email: document.getElementById('add_email').value.trim(),
    password: document.getElementById('add_password').value,
  }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function openEdit(row) {
  document.getElementById('edit_user_id').value = row.id || '';
  document.getElementById('edit_name').value = row.name || '';
  document.getElementById('edit_role').value = row.role || 'instructor';
  document.getElementById('edit_dept_id').value = row.dept_id || '';
  document.getElementById('edit_email').value = row.email || row.username || '';
  document.getElementById('edit_password').value = '';
  const history = document.getElementById('edit_history');
  const historyBody = document.getElementById('edit_history_body');
  const notes = [];
  if ((intVal(row.is_active) === 0)) {
    notes.push(`<div><strong>Archived</strong></div>`);
    if (row.archived_at) notes.push(`<div>Date: ${escapeHtml(row.archived_at)}</div>`);
    if (row.archived_by_name) notes.push(`<div>Archived By: ${escapeHtml(row.archived_by_name)}</div>`);
    if (row.archive_reason) notes.push(`<div>Reason: ${escapeHtml(row.archive_reason)}</div>`);
    if (row.restored_at) notes.push(`<hr class="my-2"><div><strong>Last Restored</strong></div>`);
    if (row.restored_at) notes.push(`<div>Date: ${escapeHtml(row.restored_at)}</div>`);
    if (row.restored_by_name) notes.push(`<div>Restored By: ${escapeHtml(row.restored_by_name)}</div>`);
    if (row.restore_reason) notes.push(`<div>Reason: ${escapeHtml(row.restore_reason)}</div>`);
  }
  if (history && historyBody) {
    history.classList.toggle('d-none', notes.length === 0);
    historyBody.innerHTML = notes.join('');
  }
  toggleEditDeptForRole();
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateRecord() {
  postAccount({
    action: 'update',
    user_id: document.getElementById('edit_user_id').value,
    name: document.getElementById('edit_name').value.trim(),
    role: document.getElementById('edit_role').value,
    dept_id: document.getElementById('edit_dept_id').value,
    email: document.getElementById('edit_email').value.trim(),
    password: document.getElementById('edit_password').value,
  }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function openArchive(row) {
  document.getElementById('archive_user_id').value = row.id || '';
  document.getElementById('archive_reason').value = '';
  new bootstrap.Modal(document.getElementById('archiveModal')).show();
}

function archiveUser() {
  const userId = document.getElementById('archive_user_id').value;
  const reason = document.getElementById('archive_reason').value.trim();
  if (!reason) {
    showToast('Archive reason is required.', 'danger');
    return;
  }
  postAccount({ action: 'archive', user_id: userId, archive_reason: reason }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('archiveModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function openRestore(row) {
  document.getElementById('restore_user_id').value = row.id || '';
  document.getElementById('restore_reason').value = '';
  new bootstrap.Modal(document.getElementById('restoreModal')).show();
}

function restoreUser() {
  const userId = document.getElementById('restore_user_id').value;
  const reason = document.getElementById('restore_reason').value.trim();
  if (!reason) {
    showToast('Restore reason is required.', 'danger');
    return;
  }
  postAccount({ action: 'restore', user_id: userId, restore_reason: reason }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('restoreModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function resetPassword(userId) {
  const password = prompt('Enter a new password for this account:');
  if (!password) return;
  postAccount({ action: 'reset_password', user_id: userId, password }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
  });
}

toggleDeptForRole();

function intVal(value) {
  return parseInt(value || '0', 10) || 0;
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[ch]));
}
</script>
