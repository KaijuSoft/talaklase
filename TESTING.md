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