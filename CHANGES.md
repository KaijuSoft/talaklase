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

## 8. RC3.5.5 — Release Manager Verified

### Date
2026-07-29

### Status
Completed

### Overview
The TalaKlase Release Manager successfully completed its first end-to-end self-update using GitHub Releases.

This milestone confirms that TalaKlase can detect, download, install, and verify official software releases without requiring manual file replacement.

The project has now transitioned from manual deployments to a production-ready release infrastructure.

### Completed Features

#### GitHub Release Integration
- GitHub Releases support completed
- Release manifest parsing implemented
- Release metadata validation
- Version comparison
- Stable release channel support

#### Release Detection
- Automatic update checks
- Current version detection
- Latest version detection
- Release notification banner
- Dynamic release notes

#### Installation Pipeline
- Release package download
- ZIP validation
- Automatic backup creation
- Package extraction
- File replacement
- Cleanup process
- Version verification

#### User Interface
- Shared global application footer
- Dynamic version display
- Dynamic TALA Engine version display
- KaijuSoft branding

#### Security
- Runtime configuration centralization
- Public repository hardening
- Configuration template support
- Release package validation

### Architecture
This milestone officially completes the first generation of the TalaKlase Release Manager.

Verified workflow:

Developer
    ↓
GitHub Release
    ↓
Manifest Detection
    ↓
Version Comparison
    ↓
Update Notification
    ↓
Download Package
    ↓
Backup Current Installation
    ↓
Install Release
    ↓
Cleanup
    ↓
Version Verification

### Repository
Official Repository

https://github.com/KaijuSoft/talaklase

License

GPL-3.0

Organization

KaijuSoft

### Credits
Designed and Architected by

Julius Frederick C. Vendivil

Founder & Owner
KaijuSoft

AI-assisted development using OpenAI ChatGPT and Codex.

### Next Milestone
RC4

Primary focus:

- User Experience
- Interface Polish
- Workflow Improvements
- Performance
- Responsive Design
- Teacher Productivity
- Student Experience

TALA Engine development should remain stable unless bug fixes or architectural improvements are required.


---

## RC3.5.21–RC3.5.22 — Current Development Updates

### User Lifecycle

- Added user account lifecycle management and account archiving.
- Added lifecycle impact analysis so administrative changes can be evaluated before completion.
- Finalized the related Post/Redirect/Get workflow.

### Integrity and Recovery

- Expanded the Database Integrity Center.
- Improved duplicate and reference inspection.
- Added deterministic backup source selection.
- Improved integrity reporting and administrative presentation.

### Synchronization

- Polished the Smart Synchronization dashboard.
- Improved synchronization state, progress, and workflow presentation while keeping the Engine → API → Frontend architecture intact.

### Instructor Workflows

- Continued instructor-scoped section, student, attendance, enrollment, and teaching-load behavior.
- Preserved instructor-admin permissions and ownership rules.

### Release State

The development branch now contains the RC3.5.22 work following the v1.0.3 stability release. See `docs/PROJECT_STATUS.md` for the consolidated current state.
