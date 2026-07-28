## 1. Authentication and roles

### [includes/auth.php](includes/auth.php)
- Kept the first-install administrator bootstrap behavior in place for backward compatibility.
- Added the `instructor_accounts` page permission mapping.
- Added the `instructor_admin` role with admin-like permissions, except user management.
- Made the `users` table bootstrap idempotent, added `email`, increased `username` length, and created the username unique index only when missing.
- Added `ensureSectionOwnershipColumn()` so `section.inst_id` exists.
- `authBootstrap()` now initializes both users and section ownership.

### [login.php](login.php)
- Login accepts either `username` or `email`.
- The authenticated session stores the user email.

## 2. Main navigation

### [index.php](index.php)
- Added `instructor_accounts` to allowed pages and title mapping.
- Introduced management page tracking and access checks.
- Cleaned up the sidebar management block and added the new `Instructor Accounts` link.
- Added the mobile bottom-nav management link state.

## 3. Instructor account management

### [pages/instructor_accounts.php](pages/instructor_accounts.php)
- Created a dedicated admin page for instructor login accounts.
- Add flow creates an instructor record plus a linked `users` login row.
- Edit flow updates department, email, and password.
- Lists existing instructor-admin accounts.
- Add/edit modals and JavaScript handlers.

## 4. Section ownership and student visibility

### [includes/migration.sql](includes/migration.sql)
- Added `section.inst_id` so sections can be owned by an instructor.
- Kept the users table migration and first-install administrator seed aligned with the auth rules.

### [pages/sections.php](pages/sections.php)
- Replaced the generic CRUD include with a custom ownership-aware sections page.
- Add/update/delete now enforce section ownership for instructors.
- Section listing now shows owner, course, and supports instructor-aware visibility.
- Custom add/edit modals and JavaScript handlers for owned sections.

### [pages/students.php](pages/students.php)
- Added helper logic to load the logged-in instructor’s owned section IDs.
- Add/update/delete actions now block changes outside the instructor’s owned sections.
- Student list queries now filter to owned sections only.
- Section dropdown and summary counts now follow the same ownership scope.

### [pages/attendance.php](pages/attendance.php)
- Added the same owned-section scope helper used by student records.
- Load/search requests are filtered to the logged-in instructor’s sections.
- Edit/load-attendance requests also use the same filter so the roster matches student records.

### [pages/view_attendance.php](pages/view_attendance.php)
- Added the same owned-section scope helper used by student records and attendance.
- The section dropdown is now limited to sections owned by the logged-in instructor.
- Count and report queries now include the ownership filter so only the instructor’s sections appear.
- The active-filter summary and pagination continue to respect the filtered section set.

## 5. Database connection behavior

### [includes/db.php](includes/db.php)
- The connection loader now reads centralized configuration from `includes/config.php`.
- The runtime behavior remains online-first with local fallback.

## 6. Important behavior summary

- The application maintains a first-install administrator bootstrap for clean deployments.
- Instructor-admin accounts can use the app with admin-like permissions, but cannot manage instructor accounts.
- Sections are now owned by an instructor through `section.inst_id`.
- Student records and attendance now show the same instructor-owned section roster.

## 7. Notes

- Existing sections created before the ownership change may need an owner assigned before they appear for instructors.
- The attendance page and student records are now aligned through the same section ownership rule.
