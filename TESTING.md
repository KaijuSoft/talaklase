# TalaKlase Testing Standards

## Purpose

Every change must be validated before it is considered complete.

A change is NOT complete just because it compiles.

---

# Required Validation

After modifying code:

## PHP

Run syntax checks on all modified PHP files.

Example:

php -l pages/students.php

---

## JavaScript

Run syntax checks on modified JavaScript.

Example:

node --check assets/js/sync.js

---

## Playwright

Run the relevant Playwright tests.

Examples:

npx playwright test tests/00_smoke

npx playwright test tests/07_students

npx playwright test tests/11_sync

If no module-specific test exists, run:

npx playwright test tests/00_smoke

---

# Reporting

Report:

- Files modified
- Commands executed
- Tests executed
- Tests passed
- Tests failed

Never claim success without reporting executed tests.

---

# Rules

Do not disable tests to make them pass.

Do not skip failing tests without explaining why.

Fix the code instead of modifying tests unless the test is incorrect.

---

# Safety

Never execute destructive database operations unless explicitly requested.

When testing data modifications:

- Prefer a dedicated test database.
- If unavailable, explain the risk before executing.

---

# Definition of Done

A task is complete only when:

✓ Code compiles.

✓ Relevant syntax checks pass.

✓ Relevant Playwright tests pass.

✓ No new console errors appear.

✓ No PHP warnings or fatal errors are introduced.

---

# Latest Validation Record — 2026-08-11

The administrative refactor was followed by a repository-wide static and HTTP regression pass.

## Results

- PHP syntax checked: 56 files; 0 failures.
- JavaScript syntax checked: 13 files; 0 failures.
- Application debug scan: CLEAN.
- Application artifact scan: CLEAN.
- Application mojibake scan: CLEAN.
- Refactored page separation checks: CLEAN.
- Unauthenticated protected-page checks: rejected/redirected as expected.
- New JavaScript assets returned HTTP 200.
- Direct SQL backup access returned HTTP 403.
- `git diff --check` produced no actual whitespace errors; only normal Windows line-ending warnings were reported.

## Refactored Modules Validated

- Academic Years
- Score Settings
- Student Import
- Database Backup
- System Check
- Database Integrity

## Security Checks

The pass specifically reviewed CSRF enforcement, permission enforcement, destructive backup request methods, backup-file exposure, debug output, and server-side validation.

## Functional Test Boundary

The development installation was used for endpoint and static validation. Authenticated destructive database operations were not executed against production data. This must remain explicit in future release reports.

## Definition-of-Done Clarification

A clean syntax scan does not equal functional completion. For future releases, authenticated Playwright/module tests should still be executed where credentials and safe test data are available.

# Analytics UI Regression - 2026-08-12

Analytics changes require both source-level and visual-artifact validation.

Required checks:

- php -l pages/analytics.php
- php -l includes/analytics_controller.php
- node --check assets/js/analytics.js
- Scan Analytics PHP/controller/JS/CSS for console.log, debugger, var_dump, print_r, TODO/FIXME, merge markers, and literal newline artifacts.
- Scan visible Analytics text for UTF-8 mojibake and replacement characters.
- Verify KPI styling is not duplicated.
- Run git diff --check for Analytics changes.
- Visually inspect action links, headings, KPI labels, chart labels, and status text after rendering.

The 2026-08-12 Analytics cleanup passed the syntax, artifact, debug, encoding, duplicate-KPI, and diff checks. Visual inspection identified and corrected corrupted UTF-8 action-link characters that were not reliably caught by the earlier repository-wide scan.
