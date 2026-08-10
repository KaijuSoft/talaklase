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


