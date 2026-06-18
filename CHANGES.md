## 1. Authentication and roles

### [includes/auth.php](includes/auth.php)
- Lines 8-19: changed the default admin password constant to `Admin@2025`.
- Lines 21-37: added the `instructor_accounts` page permission mapping.
- Lines 40-69: added the `instructor_admin` role with admin-like permissions, except user management.
- Lines 78-124: made the `users` table bootstrap idempotent, added `email`, increased `username` length, and created the username unique index only when missing.
- Lines 126-133: added `ensureSectionOwnershipColumn()` so `section.inst_id` exists.
- Lines 145-148: `authBootstrap()` now initializes both users and section ownership.
- Lines 177-187: added `current_user_owned_section_ids()` to fetch the logged-in instructor’s owned sections.

### [login.php](login.php)
- Lines 20-60: login now accepts either `username` or `email`.
- Lines 53-60: the authenticated session now stores the user email.

## 2. Main navigation

### [index.php](index.php)
- Lines 9-35: added `instructor_accounts` to allowed pages and title mapping.
- Lines 36-37: introduced management page tracking and access checks.
- Lines 56-126: cleaned up the sidebar management block and added the new `Instructor Accounts` link.
- Lines 160-177: added the new mobile bottom-nav management link state.

## 3. Instructor account management

### [pages/instructor_accounts.php](pages/instructor_accounts.php)
- Lines 1-120: created a dedicated admin page for instructor login accounts.
- Lines 15-68: add flow creates an instructor record plus a linked `users` login row.
- Lines 70-121: edit flow updates department, email, and password.
- Lines 131-159: lists existing instructor-admin accounts.
- Lines 161-320: add/edit modals and JavaScript handlers.

## 4. Section ownership and student visibility

### [includes/migration.sql](includes/migration.sql)
- Lines 1-49: added `section.inst_id` so sections can be owned by an instructor.
- Lines 21-49: kept the users table migration and default admin seed aligned with the new auth rules.

### [pages/sections.php](pages/sections.php)
- Lines 1-80: replaced the generic CRUD include with a custom ownership-aware sections page.
- Lines 18-120: add/update/delete now enforce section ownership for instructors.
- Lines 122-167: section listing now shows owner, course, and supports instructor-aware visibility.
- Lines 169-260: custom add/edit modals and JavaScript handlers for owned sections.

### [pages/students.php](pages/students.php)
- Lines 1-32: added helper logic to load the logged-in instructor’s owned section IDs.
- Lines 34-88: add/update/delete actions now block changes outside the instructor’s owned sections.
- Lines 102-154: student list queries now filter to owned sections only.
- Lines 156-193: section dropdown and summary counts now follow the same ownership scope.

### [pages/attendance.php](pages/attendance.php)
- Lines 1-31: added the same owned-section scope helper used by student records.
- Lines 34-71: load/search requests are filtered to the logged-in instructor’s sections.
- Lines 73-136: edit/load-attendance requests also use the same filter so the roster matches student records.

### [pages/view_attendance.php](pages/view_attendance.php)
- Lines 1-35: added the same owned-section scope helper used by student records and attendance.
- Lines 37-77: the section dropdown is now limited to sections owned by the logged-in instructor.
- Lines 79-151: count and report queries now include the ownership filter so only the instructor’s sections appear.
- Lines 153-249: the active-filter summary and pagination continue to respect the filtered section set.

## 5. Database connection behavior

### [includes/db.php](includes/db.php)
- This file was changed earlier to try the local database first and then fall back to online, but that edit was later undone in the workspace.
- Current workspace state still uses the original online-first behavior.

## 6. Important behavior summary

- Superadmin login is now `admin` / `Admin@2025`.
- Instructor-admin accounts can use the app with admin-like permissions, but cannot manage instructor accounts.
- Sections are now owned by an instructor through `section.inst_id`.
- Student records and attendance now show the same instructor-owned section roster.

## 7. Notes

- Existing sections created before the ownership change may need an owner assigned before they appear for instructors.
- The attendance page and student records are now aligned through the same section ownership rule.
- If you want this documentation merged into the main [README.md](README.md), the content above can be appended as a dedicated change-log section.
