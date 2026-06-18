<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

authBootstrap();
require_login();
include 'includes/update_banner.php';

$page = $_GET['page'] ?? 'students';
$allowed = ['students','attendance','view_attendance','print_attendance','grades','score_settings','courses','departments','instructors','instructor_accounts','sections','subjects','sync'];
if (!in_array($page, $allowed)) $page = 'students';

$permission = page_permission($page);
if (!$permission || !can($permission)) {
    $page = first_allowed_page();
    $permission = page_permission($page);
}

// If this is a POST (AJAX) request, just include the page and exit -
// no HTML layout needed, the page will output JSON and call exit()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission($permission);
    include "pages/{$page}.php";
    exit;
}

$titles = [
  'students'=>'Student Records','attendance'=>'Attendance','view_attendance'=>'View Attendance',
  'print_attendance'=>'Print Attendance','grades'=>'Grading Form','score_settings'=>'Score Settings',
  'courses'=>'Courses','departments'=>'Departments','instructors'=>'Instructors','instructor_accounts'=>'Instructor Accounts',
  'sections'=>'Sections','subjects'=>'Subjects','sync'=>'Database Sync'
];
$currentTitle = $titles[$page] ?? 'Dashboard';
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
    (function () {
      const savedTheme = localStorage.getItem('talaklase-theme') || 'light';
      document.documentElement.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light';
    })();
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay"></div>

<div id="wrapper">

  <!-- ── SIDEBAR ── -->
  <div id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <span>TalaKlase</span>
      </div>
      <div class="sidebar-subtitle">Your Records, Your Way</div>
      <a href="logout.php" class="sidebar-logout">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>

    <nav class="sidebar-nav">
      <?php if (can('view_students') || can('edit_attendance') || can('view_attendance') || can('view_grades') || can('sync_settings')): ?>
      <div class="nav-section-label">Students</div>
      <?php endif; ?>
      <?php if (can('view_students')): ?>
      <a href="?page=students" class="nav-link <?= $page==='students'?'active':'' ?>">
        <i class="bi bi-people-fill"></i> Student Records
      </a>
      <?php endif; ?>
     <?php if (can('edit_attendance')): ?>
	<a href="?page=attendance" class="nav-link <?= $page==='attendance'?'active':'' ?>">
	<i class="bi bi-calendar-check-fill"></i> Attendance
	</a>
	<?php endif; ?>
      <?php if (can('view_attendance')): ?>
      <a href="?page=view_attendance" class="nav-link <?= $page==='view_attendance'?'active':'' ?>">
        <i class="bi bi-eye-fill"></i> View Attendance
      </a>
      <?php endif; ?>
      <?php if (can('view_grades')): ?>
      <a href="?page=grades" class="nav-link <?= $page==='grades'?'active':'' ?>">
        <i class="bi bi-journal-text"></i> Grading Form
      </a>
      <?php endif; ?>
	  
      <?php if (can('sync_settings')): ?>
	<a href="?page=sync" class="nav-link <?= $page==='sync'?'active':'' ?>">
	<i class="bi bi-arrow-left-right"></i> DB Sync
	</a>
	<?php endif; ?>

      <?php if ($hasManagementAccess): ?>
    <div class="nav-section-label mt-3">Management</div>
      <?php if (can('manage_departments')): ?>
    <a href="?page=departments" class="nav-link <?= $page==='departments'?'active':'' ?>">
      <i class="bi bi-building"></i> Departments
    </a>
    <?php endif; ?>
      <?php if (can('manage_courses')): ?>
    <a href="?page=courses" class="nav-link <?= $page==='courses'?'active':'' ?>">
      <i class="bi bi-book-fill"></i> Courses
    </a>
    <?php endif; ?>
      <?php if (can('manage_sections')): ?>
    <a href="?page=sections" class="nav-link <?= $page==='sections'?'active':'' ?>">
      <i class="bi bi-grid-fill"></i> Sections
    </a>
    <?php endif; ?>
      <?php if (can('manage_subjects')): ?>
    <a href="?page=subjects" class="nav-link <?= $page==='subjects'?'active':'' ?>">
      <i class="bi bi-file-earmark-text-fill"></i> Subjects
    </a>
    <?php endif; ?>
      <?php if (can('manage_instructors')): ?>
    <a href="?page=instructors" class="nav-link <?= $page==='instructors'?'active':'' ?>">
      <i class="bi bi-person-badge-fill"></i> Instructors
    </a>
    <?php endif; ?>
      <?php if (can('manage_users')): ?>
    <a href="?page=instructor_accounts" class="nav-link <?= $page==='instructor_accounts'?'active':'' ?>">
      <i class="bi bi-person-vcard-fill"></i> Users
    </a>
      <?php endif; ?>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <i class="bi bi-person-circle"></i>
        <div>
          <div class="sidebar-user-label">Logged in as</div>
          <div class="sidebar-user-name"><?= htmlspecialchars(current_user()['name'] ?? 'User') ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars(current_user()['role'] ?? '') ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── PAGE CONTENT ── -->
  <div id="page-content">

    <!-- Top bar -->
    <div class="top-bar">
      <button class="btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-list" style="font-size:1.2rem;"></i>
      </button>

      <h5 class="mb-0 page-title"><?= htmlspecialchars($currentTitle) ?></h5>

      <div class="top-actions">
        <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch to dark mode">
          <i class="bi bi-moon-stars-fill"></i>
          <span>Dark</span>
        </button>

        <div class="db-badge">
          <i class="bi bi-wifi"></i>
          <span><?= htmlspecialchars($_SESSION['db_source'] ?? '...') ?></span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content-area">
      <?php include "pages/{$page}.php"; ?>
    </div>
  </div>

</div>

<!-- ── BOTTOM NAV (mobile shortcut) ── -->
<nav class="bottom-nav">
  <div class="bottom-nav-items">
    <?php if (can('view_students')): ?>
    <a href="?page=students" class="bottom-nav-item <?= $page==='students'?'active':'' ?>">
      <i class="bi bi-people-fill"></i> Students
    </a>
    <?php endif; ?>
    <?php if (can('edit_attendance')): ?>
    <a href="?page=attendance" class="bottom-nav-item <?= $page==='attendance'?'active':'' ?>">
      <i class="bi bi-calendar-check-fill"></i> Attend.
    </a>
    <?php endif; ?>
    <?php if (can('view_grades')): ?>
    <a href="?page=grades" class="bottom-nav-item <?= $page==='grades'?'active':'' ?>">
      <i class="bi bi-journal-text"></i> Grades
    </a>
    <?php endif; ?>
    <?php if ($hasManagementAccess): ?>
    <a href="?page=departments" class="bottom-nav-item <?= in_array($page,$managementPages)?'active':'' ?>">
      <i class="bi bi-building"></i> Manage
    </a>
    <?php endif; ?>
    <a href="#" class="bottom-nav-item" id="moreMenuBtn">
      <i class="bi bi-grid-3x3-gap-fill"></i> More
    </a>
  </div>
</nav>

<!-- Global Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="globalToast" class="toast align-items-center border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastMsg"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
