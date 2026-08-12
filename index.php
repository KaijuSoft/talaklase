<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

authBootstrap();
require_login();

/* Database Integrity AJAX must be handled before the HTML layout. */
if (($_GET['page'] ?? '') === 'database_integrity' && isset($_GET['action'])) {
    require __DIR__ . '/pages/database_integrity.php';
    exit;
}

date_default_timezone_set('Asia/Manila');

// Analytics is the authenticated landing page. Detailed student records remain a separate module.
$page = $_GET['page'] ?? 'analytics';
$allowed = ['analytics','students','student_profile','attendance_v2','view_attendance_v2','print_attendance_v2','grades','score_settings','courses','departments','instructors','instructor_accounts','sections','subjects','sync','db_backup','database_integrity','teaching_loads','academic_years','student_enrollment'];
if (!in_array($page, $allowed, true)) $page = 'analytics';

$permission = page_permission($page);
if (!$permission || !can($permission)) {
    $page = first_allowed_page();
    $permission = page_permission($page);
}

$isOrphanDeletion = $page === 'database_integrity' && ($_POST['action'] ?? '') === 'delete_orphan';

/* Account actions are handled here so the header menu can manage the signed-in user without opening User Accounts. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_my_password') {
    if (!verify_csrf()) {
        $_SESSION['account_message'] = ['type' => 'danger', 'text' => 'Your session token is invalid. Please try again.'];
    } else {
        $currentUser = current_user();
        $currentUserId = (int) ($currentUser['id'] ?? 0);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentUserId < 1 || $currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $_SESSION['account_message'] = ['type' => 'danger', 'text' => 'All password fields are required.'];
        } elseif (strlen($newPassword) < 8) {
            $_SESSION['account_message'] = ['type' => 'danger', 'text' => 'Your new password must be at least 8 characters long.'];
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['account_message'] = ['type' => 'danger', 'text' => 'The new passwords do not match.'];
        } else {
            $userStmt = getConnection()->prepare('SELECT password_hash FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
            $userStmt->execute([$currentUserId]);
            $storedHash = (string) ($userStmt->fetchColumn() ?: '');

            if ($storedHash === '' || !password_verify($currentPassword, $storedHash)) {
                $_SESSION['account_message'] = ['type' => 'danger', 'text' => 'Your current password is incorrect.'];
            } else {
                $updateStmt = getConnection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $updateStmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $currentUserId]);
                $_SESSION['account_message'] = ['type' => 'success', 'text' => 'Your password has been changed successfully.'];
            }
        }
    }
    header('Location: index.php?page=analytics');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isOrphanDeletion) {
    require_permission($permission);
    include "pages/{$page}.php";
    exit;
}
if ($isOrphanDeletion) {
    require_permission($permission);
    include 'pages/database_integrity.php';
    exit;
}

include 'includes/update_banner.php';

$titles = [
  'analytics'=>'Analytics','students'=>'Student Records','student_profile'=>'Student Profile','attendance_v2'=>'Attendance','view_attendance_v2'=>'View Attendance',
  'print_attendance_v2'=>'Print Attendance','grades'=>'Grading Form','score_settings'=>'Score Settings',
  'courses'=>'Courses','departments'=>'Departments','instructors'=>'Instructors','instructor_accounts'=>'User Accounts',
  'sections'=>'Sections','subjects'=>'Subjects','sync'=>'Smart Sync','db_backup'=>'Database Backup','teaching_loads'=>'Teaching Loads',
  'academic_years'=>'Academic Years','database_integrity'=>'Database Integrity','student_enrollment'=>'Enroll Student',
];
$currentTitle = $titles[$page] ?? 'Analytics';
$managementPages = ['departments','courses','sections','subjects','instructors','instructor_accounts'];
$hasManagementAccess = can_any(['manage_departments','manage_courses','manage_sections','manage_subjects','manage_instructors','manage_users']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TalaKlase - <?= htmlspecialchars($currentTitle) ?></title>
  <script>
    (function () { const savedTheme = localStorage.getItem('talaklase-theme') || 'light'; document.documentElement.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light'; })();
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <link rel="stylesheet" href="assets/css/talaklase-2.css?v=1.0.3"/>
  <?php if ($page === 'students'): ?><script defer src="/talaklase-dev/assets/js/students.js?v=5"></script><?php endif; ?>
</head>
<body>
<div id="sidebar-overlay"></div>
<div id="wrapper">
  <div id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo"><div class="logo-icon"><i class="bi bi-mortarboard-fill"></i></div><div><span>TalaKlase</span><small>ACADEMIC OPERATIONS</small></div></div>
      <a href="logout.php" class="sidebar-logout"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
    <nav class="sidebar-nav">
      <?php if (can('view_students') || can('edit_attendance') || can('view_attendance') || can('view_grades')): ?>
      <div class="nav-section-label">Workspace</div>
      <?php endif; ?>
      <?php if (can('view_students')): ?>
      <a href="?page=analytics" class="nav-link <?= $page==='analytics'?'active':'' ?>"><i class="bi bi-grid-1x2-fill"></i> Analytics</a>
      <a href="?page=students" class="nav-link <?= $page==='students'?'active':'' ?>"><i class="bi bi-people-fill"></i> Students</a>
      <?php endif; ?>
      <?php if (can('manage_teaching_loads') || can('view_students')): ?>
      <a href="?page=teaching_loads" class="nav-link <?= $page==='teaching_loads'?'active':'' ?>"><i class="bi bi-mortarboard-fill"></i> Classes</a>
      <?php endif; ?>
      <?php if (can('edit_attendance')): ?>
      <a href="?page=attendance_v2" class="nav-link <?= $page==='attendance_v2'?'active':'' ?>"><i class="bi bi-calendar2-check-fill"></i> Attendance</a>
      <?php endif; ?>
      <?php if (can('view_grades')): ?>
      <a href="?page=grades" class="nav-link <?= $page==='grades'?'active':'' ?>"><i class="bi bi-journal-check"></i> Grades</a>
      <?php endif; ?>
      <?php if (can('view_attendance')): ?>
      <a href="?page=view_attendance_v2" class="nav-link <?= $page==='view_attendance_v2'?'active':'' ?>"><i class="bi bi-file-bar-graph-fill"></i> Reports</a>
      <?php endif; ?>

      <?php if ($hasManagementAccess): ?>
      <div class="nav-section-label mt-3">Management</div>
      <?php if (can('manage_student_enrollment')): ?><a href="?page=student_enrollment" class="nav-link <?= $page==='student_enrollment'?'active':'' ?>"><i class="bi bi-person-plus-fill"></i> Enrollment</a><?php endif; ?>
      <?php if (can('manage_sections')): ?><a href="?page=sections" class="nav-link <?= $page==='sections'?'active':'' ?>"><i class="bi bi-diagram-3-fill"></i> Sections</a><?php endif; ?>
      <?php if (can('manage_subjects')): ?><a href="?page=subjects" class="nav-link <?= $page==='subjects'?'active':'' ?>"><i class="bi bi-book-fill"></i> Subjects</a><?php endif; ?>
      <?php if (can('manage_courses')): ?><a href="?page=courses" class="nav-link <?= $page==='courses'?'active':'' ?>"><i class="bi bi-collection-fill"></i> Courses</a><?php endif; ?>
      <?php if (can('manage_instructors')): ?><a href="?page=instructors" class="nav-link <?= $page==='instructors'?'active':'' ?>"><i class="bi bi-person-badge-fill"></i> Instructors</a><?php endif; ?>
      <?php if (can('manage_users')): ?><a href="?page=instructor_accounts" class="nav-link <?= $page==='instructor_accounts'?'active':'' ?>"><i class="bi bi-person-vcard-fill"></i> Users & Access</a><?php endif; ?>
      <?php if (can('manage_departments')): ?><a href="?page=departments" class="nav-link <?= $page==='departments'?'active':'' ?>"><i class="bi bi-building-fill"></i> Departments</a><?php endif; ?>
      <?php if (can('manage_academic_years')): ?><a href="?page=academic_years" class="nav-link <?= $page==='academic_years'?'active':'' ?>"><i class="bi bi-calendar3"></i> Academic Year</a><?php endif; ?>
      <?php endif; ?>

      <?php if (can('sync_settings')): ?>
      <div class="nav-section-label mt-3">System</div>
      <a href="?page=database_integrity" class="nav-link <?= $page==='database_integrity'?'active':'' ?>"><i class="bi bi-shield-check"></i> Database Integrity</a>
      <a href="?page=db_backup" class="nav-link <?= $page==='db_backup'?'active':'' ?>"><i class="bi bi-cloud-arrow-down-fill"></i> Backups</a>
      <a href="?page=sync" class="nav-link <?= $page==='sync'?'active':'' ?>"><i class="bi bi-arrow-repeat"></i> Smart Sync</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer"><div class="sidebar-user"><i class="bi bi-person-circle"></i><div><div class="sidebar-user-label">Logged in as</div><div class="sidebar-user-name"><?= htmlspecialchars(current_user()['name'] ?? 'User') ?></div><div class="sidebar-user-role"><?= htmlspecialchars(current_user()['role'] ?? '') ?></div></div></div></div>
  </div>

  <div id="page-content">
    <header class="top-bar">
      <div class="top-bar-start">
        <button class="btn" id="sidebarToggle" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
        <div class="page-context">
          <span class="page-context__eyebrow">TalaKlase</span>
          <h1 class="page-title mb-0"><?= htmlspecialchars($currentTitle) ?></h1>
        </div>
      </div>
      <div class="top-actions">
        <div class="db-badge" title="Active database source"><span class="db-status-dot"></span><i class="bi bi-database-check"></i><span><?= htmlspecialchars($_SESSION['db_source'] ?? 'Database') ?></span></div>
        <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch to dark mode"><i class="bi bi-moon-stars-fill"></i><span>Dark</span></button>
        <div class="dropdown">
          <button class="top-user" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
            <span class="top-user__avatar"><?= htmlspecialchars(strtoupper(substr((string)(current_user()['name'] ?? 'U'),0,1))) ?></span>
            <span class="top-user__meta"><strong><?= htmlspecialchars((string)(current_user()['name'] ?? 'User')) ?></strong><small><?= htmlspecialchars(ucwords(str_replace('_',' ',(string)(current_user()['role'] ?? 'user')))) ?></small></span>
            <i class="bi bi-chevron-down"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end account-menu shadow-sm">
            <li><div class="account-menu__header"><strong><?= htmlspecialchars((string)(current_user()['name'] ?? 'User')) ?></strong><small><?= htmlspecialchars((string)(current_user()['email'] ?? '')) ?></small></div></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?page=teaching_loads"><i class="bi bi-mortarboard me-2"></i>My Classes</a></li>
            <?php if (can('manage_users')): ?><li><a class="dropdown-item" href="?page=instructor_accounts"><i class="bi bi-people me-2"></i>Users & Access</a></li><?php endif; ?>
            <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key me-2"></i>Change Password</button></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
          </ul>
        </div>
      </div>
    </header>
    <div class="content-area"><?php include "pages/{$page}.php"; ?></div>
    <?php include __DIR__ . '/includes/global_footer.php'; ?>
  </div>
</div>

<nav class="bottom-nav">
  <div class="bottom-nav-items">
    <?php if (can('view_students')): ?>
    <a href="?page=analytics" class="bottom-nav-item <?= $page==='analytics'?'active':'' ?>"><i class="bi bi-bar-chart-line-fill"></i> Analytics</a>
    <?php endif; ?>
    <?php if (can('edit_attendance')): ?>
    <a href="?page=attendance_v2" class="bottom-nav-item <?= $page==='attendance_v2'?'active':'' ?>"><i class="bi bi-calendar-check-fill"></i> Attend.</a>
    <?php endif; ?>
    <?php if (can('view_grades')): ?>
    <a href="?page=grades" class="bottom-nav-item <?= $page==='grades'?'active':'' ?>"><i class="bi bi-journal-text"></i> Grades</a>
    <?php endif; ?>
    <?php if ($hasManagementAccess): ?>
    <a href="?page=departments" class="bottom-nav-item <?= in_array($page,$managementPages,true)?'active':'' ?>"><i class="bi bi-building"></i> Manage</a>
    <?php endif; ?>
    <a href="#" class="bottom-nav-item" id="moreMenuBtn"><i class="bi bi-grid-3x3-gap-fill"></i> More</a>
  </div>
</nav>

<?php if (!empty($_SESSION['account_message'])): $accountMessage = $_SESSION['account_message']; unset($_SESSION['account_message']); ?>
<div class="alert alert-<?= htmlspecialchars($accountMessage['type'] ?? 'info') ?> alert-dismissible fade show account-flash" role="alert">
  <?= htmlspecialchars($accountMessage['text'] ?? '') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div><h5 class="modal-title mb-1" id="changePasswordModalLabel">Change Password</h5><div class="small text-muted">Update your TalaKlase account password.</div></div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="index.php?page=analytics" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_my_password">
        <div class="modal-body">
          <div class="mb-3"><label class="form-label" for="currentPassword">Current password</label><input class="form-control" id="currentPassword" name="current_password" type="password" required autocomplete="current-password"></div>
          <div class="mb-3"><label class="form-label" for="newPassword">New password</label><input class="form-control" id="newPassword" name="new_password" type="password" minlength="8" required autocomplete="new-password"><div class="form-text">Use at least 8 characters.</div></div>
          <div><label class="form-label" for="confirmPassword">Confirm new password</label><input class="form-control" id="confirmPassword" name="confirm_password" type="password" minlength="8" required autocomplete="new-password"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i>Change Password</button></div>
      </form>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="globalToast" class="toast align-items-center border-0" role="alert"><div class="d-flex"><div class="toast-body" id="toastMsg"></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<?php if ($page === 'academic_years'): ?><script src="assets/js/academic_years.js?v=1"></script><?php endif; ?>
<?php if ($page === 'score_settings'): ?><script src="assets/js/score_settings.js?v=1"></script><?php endif; ?>
<?php if ($page === 'database_integrity'): ?><script src="assets/js/database-integrity.js?v=1"></script><?php endif; ?>
<?php if ($page === 'attendance_v2'): ?><script src="assets/js/attendance_v2.js?v=1"></script><?php endif; ?>
<?php if ($page === 'grades'): ?><script src="assets/js/grades.js?v=1"></script><?php endif; ?>
<?php if ($page === 'teaching_loads'): ?><script src="assets/js/teaching_loads.js?v=1"></script><?php endif; ?>
<?php if ($page === 'student_enrollment'): ?><script src="assets/js/student_enrollment.js?v=1"></script><?php endif; ?>
<?php if ($page === 'sections'): ?><script src="assets/js/sections.js?v=1"></script><?php endif; ?>
</body>
</html>

<?php if ($page === 'instructor_accounts'): ?><script defer src="assets/js/instructor_accounts.js?v=1"></script><?php endif; ?><?php if ($page === 'analytics'): ?><script src="assets/js/analytics.js?v=1"></script><?php endif; ?>
