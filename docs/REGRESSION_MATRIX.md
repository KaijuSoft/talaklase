# TalaKlase Regression Matrix

**Current line:** v1.0.3 / RC3.5.22
**Status:** Living test matrix

This document records the workflows that must be validated before a release. A row is not marked passed merely because the related code exists.

---

## Required Validation

| Area | Workflow | Validation | Current documentation update |
|---|---|---|---|
| Authentication | Administrator login | Manual / Playwright | PASS - Playwright |
| Authentication | Instructor login | Manual / Playwright | PASS - Playwright |
| Users | Create instructor account | Manual / Playwright | Not executed |
| Users | Edit instructor account | Manual / Playwright | Not executed |
| Users | Archive user account | Manual / Playwright | Not executed |
| Users | Lifecycle impact analysis | Manual / Playwright | Not executed |
| Sections | Instructor-owned section visibility | Manual / Playwright | Not executed |
| Students | Instructor-scoped student visibility | Manual / Playwright | Not executed |
| Attendance | Instructor-scoped attendance | Manual / Playwright | Not executed |
| Attendance | Attendance edit/load workflow | Playwright | Not executed |
| Enrollment | Student enrollment workflow | Playwright | Not executed |
| Teaching Loads | Teaching-load workflow | Playwright | Not executed |
| Grading | Grade encoding workflow | Playwright | Not executed |
| Integrity | Integrity scan | Manual / integration | Not executed |
| Integrity | Duplicate detection | Manual / integration | Not executed |
| Integrity | Reference inspection | Manual / integration | Not executed |
| Backup | Deterministic backup source | Manual / integration | Not executed |
| Sync | Smart Synchronization | Playwright / integration | Not executed |
| Sync | Restore Online Database | Manual / integration | Not executed |
| TALA | Schema analysis | Integration | Not executed |
| TALA | Execution planning | Integration | Not executed |
| TALA | Schema execution | Integration | Not executed |
| Release | Update detection | Integration | Not executed |
| Release | Package validation | Integration | Not executed |
| Release | Backup and installation | Integration | Not executed |

---

## Required Commands

PHP syntax checks should be run on every modified PHP file.

```text
php -l <modified-file.php>
```

JavaScript syntax checks should be run on every modified JavaScript file.

```text
node --check <modified-file.js>
```

Relevant Playwright suites should then be executed.

```text
npx playwright test tests/00_smoke
npx playwright test tests/11_sync
```

If a module-specific suite exists, it should be run in addition to smoke coverage.

---

## Release Gate

A release should not be declared complete until:

- Relevant syntax checks pass.
- Relevant Playwright tests pass.
- No new console errors appear.
- No PHP warnings or fatal errors are introduced.
- Smart Synchronization is validated.
- Instructor-scoped workflows are validated.
- Integrity workflows are validated.
- Backup/recovery workflows are validated.
- Release installation is validated when updater code changes.

This matrix intentionally reports the documentation update itself as **not executed** rather than claiming tests that were not run.


## Maintenance-Specific Validation

| Area | Workflow | Current status |
|---|---|---|
| Integrity | Duplicate merge preflight | PASS - controlled local fixture |
| Integrity | Duplicate merge execution | PASS - controlled local fixture |
| Integrity | Orphan deletion with reason | PASS - controlled local fixture |
| Integrity | Maintenance backup generation | PASS - backup created and verified |
| Integrity | Maintenance rollback | PASS - intentional trigger failure rolled back |
| Integrity | Non-transactional table safety gate | Code path implemented; live gate validation pending |
| Integrity | Maintenance audit log | PASS - audit entry verified |
| Integrity | AJAX JSON response handling | PASS - endpoint/browser validation |
| Users | Account filter default | Code path reviewed; live browser warning verification pending |