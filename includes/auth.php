<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/db.php';

const DEFAULT_ADMIN_USERNAME = 'Admin';
const DEFAULT_ADMIN_PASSWORD = 'Admin@2026';


function can_any(array $permissions): bool {
    foreach ($permissions as $permission) {
        if (can($permission)) {
            return true;
        }
    }
    return false;
}

function page_permission(string $page): ?string {
    $map = [
        'students' => 'view_students',
        'attendance' => 'edit_attendance',
        'view_attendance' => 'view_attendance',
        'print_attendance' => 'view_attendance',
        'grades' => 'view_grades',
        'score_settings' => 'manage_score_settings',
        'courses' => 'manage_courses',
        'departments' => 'manage_departments',
        'instructors' => 'manage_instructors',
        'instructor_accounts' => 'manage_users',
        'sections' => 'manage_sections',
        'subjects' => 'manage_subjects',
        'sync' => 'sync_settings',
		'db_backup' => 'sync_settings',
    ];

    return $map[$page] ?? null;
}

// ── Permission map ─────────────────────────────────────────────
$PERMISSIONS = [
    'admin' => [
    'view_students',    'edit_students',
    'view_attendance',  'edit_attendance',
    'view_grades',      'edit_grades',
    'manage_departments','manage_courses',
    'manage_sections',  'manage_subjects',
    'manage_instructors','manage_users',
    'sync_settings',    'print_reports',
    'manage_score_settings', 
    ],
    'instructor_admin' => [
        'view_students',    'edit_students',
        'view_attendance',  'edit_attendance',
        'view_grades',      'edit_grades',
        'manage_departments','manage_courses',
        'manage_sections',  'manage_subjects',
        'sync_settings',    'print_reports',
        'manage_score_settings',
    ],
   'instructor' => [
    'view_students',
    'edit_students',

    'view_attendance',
    'edit_attendance',

    'view_grades',
    'edit_grades',

    'manage_subjects',

    'print_reports',
],
    'viewer' => [
        'view_students',
        'view_attendance',
        'view_grades',
        'print_reports',
    ],
];

// ── Core helpers ───────────────────────────────────────────────
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function ensureUsersTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT 'viewer',
            inst_id INT(11) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $hasEmail = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchColumn();
    if (!$hasEmail) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER name");
    }

    $hasInstId = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'inst_id'")->fetchColumn();
    if (!$hasInstId) {
        $pdo->exec("ALTER TABLE users ADD COLUMN inst_id INT(11) DEFAULT NULL AFTER role");
    }

    $hasUsernameUnique = (bool)$pdo->query("
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'users'
          AND column_name = 'username'
          AND non_unique = 0
        LIMIT 1
    ")->fetchColumn();
    if (!$hasUsernameUnique) {
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)");
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'Administrator',
            DEFAULT_ADMIN_USERNAME,
            null,
            password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT),
            'admin'
        ]);
        return;
    }
	if ($count === 0) {
    $adminStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
    $adminStmt->execute([DEFAULT_ADMIN_USERNAME]);
    if ($adminStmt->fetchColumn()) {
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ? AND role = 'admin'")
            ->execute([password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT), DEFAULT_ADMIN_USERNAME]);
		}
	}
}

function ensureSectionOwnershipColumn(PDO $pdo): void {
    $hasOwner = (bool)$pdo->query("SHOW COLUMNS FROM section LIKE 'inst_id'")->fetchColumn();
    if (!$hasOwner) {
        $pdo->exec("ALTER TABLE section ADD COLUMN inst_id INT(11) DEFAULT NULL AFTER course_id");
    }
}


function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function send_no_cache_headers(): void {
    if (headers_sent()) {
        return;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function authBootstrap(): PDO {
    $pdo = getConnection();
    ensureUsersTable($pdo);
    ensureSectionOwnershipColumn($pdo);
    return $pdo;
}

function current_user(): ?array {
    return $_SESSION['auth_user'] ?? null;
}

function can(string $permission): bool {
    global $PERMISSIONS;
    $user = current_user();
    if (!$user || !(bool)$user['is_active']) {
        return false;
    }
    return in_array($permission, $PERMISSIONS[$user['role']] ?? [], true);
}

function require_permission(string $permission): void {
    require_login();
    if (!can($permission)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

function require_permission_any(array $permissions): void {
    require_login();
    if (!can_any($permissions)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}


// ── CSRF helpers ───────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function first_allowed_page(): string {
    foreach (['students', 'attendance', 'view_attendance', 'grades', 'departments'] as $page) {
        $permission = page_permission($page);
        if ($permission && can($permission)) {
            return $page;
        }
    }

    return 'students';
}

function current_user_owned_section_ids(PDO $pdo): array {
    $user = current_user();
    if (!$user || !in_array($user['role'] ?? '', ['instructor', 'instructor_admin'], true) || empty($user['inst_id'])) {
        return [];
    }

  $stmt = $pdo->prepare("
    SELECT sectionID
    FROM section_instructors
    WHERE inst_id = ?
");
    $stmt->execute([(int)$user['inst_id']]);

    return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}
