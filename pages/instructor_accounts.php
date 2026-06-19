<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_users');

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!verify_csrf()) {
        echo json_encode(['success' => false, 'message' => 'Invalid session token. Refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $deptId = trim($_POST['dept_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
		$role = trim($_POST['role'] ?? 'instructor');

        if ($name === '' || $deptId === '' || $email === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Name, department, email, and password are required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $check->execute([$email, $email]);
            if ($check->fetchColumn()) {
                throw new RuntimeException('An account with that email already exists.');
            }

            $stmt = $pdo->prepare('INSERT INTO instructor (inst_name, dept_id) VALUES (?, ?)');
            $stmt->execute([$name, $deptId]);
            $instId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, username, email, password_hash, role, inst_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $email,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $instId,
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Instructor account created.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $instId = (int)($_POST['inst_id'] ?? 0);
        $deptId = trim($_POST['dept_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($instId <= 0 || $deptId === '' || $email === '') {
            echo json_encode(['success' => false, 'message' => 'Department and email are required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $accountStmt = $pdo->prepare('SELECT id, name FROM users WHERE inst_id = ? AND role = ? LIMIT 1');
            $accountStmt->execute([$instId, 'instructor']);
            $account = $accountStmt->fetch();

            if (!$account) {
                throw new RuntimeException('Instructor account not found.');
            }

            $duplicate = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
            $duplicate->execute([$email, $email, $account['id']]);
            if ($duplicate->fetchColumn()) {
                throw new RuntimeException('Another account already uses that email.');
            }

            $stmt = $pdo->prepare('UPDATE instructor SET dept_id = ? WHERE inst_id = ?');
            $stmt->execute([$deptId, $instId]);

            $setParts = ['username = ?', 'email = ?'];
            $values = [$email, $email];

            if ($password !== '') {
                $setParts[] = 'password_hash = ?';
                $values[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $setParts[] = 'name = ?';
            $values[] = $account['name'];
            $values[] = $account['id'];

            $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ?');
            $stmt->execute($values);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Instructor account updated.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

$departments = $pdo->query('SELECT dept_id, dept_name FROM department ORDER BY dept_name')->fetchAll();
$rows = $pdo->query(
  "SELECT u.name, u.username AS email, u.role, u.inst_id, u.is_active, i.dept_id, d.dept_name
   FROM users u
   LEFT JOIN instructor i ON i.inst_id = u.inst_id
   LEFT JOIN department d ON d.dept_id = i.dept_id
   WHERE u.role <> 'admin'
   ORDER BY u.name"
)->fetchAll();
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h6 class="mb-1"><i class="bi bi-person-vcard-fill me-2 text-primary"></i>Instructor Accounts</h6>
      <div class="text-muted small">Create login access for instructors. They can use the system like admin users, but cannot add instructor accounts.</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg me-1"></i> Add Instructor Account
    </button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          
          <th>Name</th>
          <th>Department</th>
          <th>Email</th>
		  <th>Role</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">No instructor accounts found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              
              <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['dept_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
			  <td><?= htmlspecialchars($row['role'] ?? '') ?></td>
              <td class="text-end">
                <button
                  class="btn btn-sm btn-outline-primary"
                  onclick='openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'
                >
                  <i class="bi bi-pencil-fill me-1"></i>Edit
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
        <h5 class="modal-title">Add Instructor Account</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" id="add_name" />
        </div>
        <div class="mb-3">
          <label class="form-label">Department</label>
          <select class="form-select" id="add_dept_id">
            <option value="">Select department</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= htmlspecialchars((string)$department['dept_id']) ?>"><?= htmlspecialchars($department['dept_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
		<div class="mb-3">
    <label class="form-label">Role</label>
    <select class="form-select" id="add_role">
        <option value="instructor">Instructor</option>
        <option value="instructor_admin">Instructor Admin</option>
        <option value="viewer">Viewer</option>
    </select>
</div>
        <div class="mb-3">
          <label class="form-label">Email</label>
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
        <h5 class="modal-title">Edit Instructor Account</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_inst_id" />
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" id="edit_name" disabled />
        </div>
        <div class="mb-3">
          <label class="form-label">Department</label>
          <select class="form-select" id="edit_dept_id">
            <option value="">Select department</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= htmlspecialchars((string)$department['dept_id']) ?>"><?= htmlspecialchars($department['dept_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="edit_email" />
        </div>
        <div class="mb-0">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" id="edit_password" placeholder="Leave blank to keep current password" />
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-white" onclick="updateRecord()">Update</button>
      </div>
    </div>
  </div>
</div>

<script>
const csrfToken = <?= json_encode(csrf_token()) ?>;

function postAccount(params) {
  params.csrf_token = csrfToken;
  return fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(params)
  }).then(response => response.json());
}

function saveRecord() {
 postAccount({
    action: 'add',
    name: document.getElementById('add_name').value.trim(),
    dept_id: document.getElementById('add_dept_id').value,
    role: document.getElementById('add_role').value,
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
  document.getElementById('edit_inst_id').value = row.inst_id || '';
  document.getElementById('edit_name').value = row.name || '';
  document.getElementById('edit_dept_id').value = row.dept_id || '';
  document.getElementById('edit_email').value = row.email || '';
  document.getElementById('edit_password').value = '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateRecord() {
  postAccount({
    action: 'update',
    inst_id: document.getElementById('edit_inst_id').value,
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
</script>
