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

---

## Administrative Refactor Regression Record — 2026-08-11

| Area | Check | Result |
|---|---|---|
| PHP | Repository syntax pass | PASS — 56 files / 0 failures |
| JavaScript | Repository syntax pass | PASS — 13 files / 0 failures |
| Security | Unauthenticated protected pages | PASS — rejected/redirected |
| Security | CSRF/permission controller review | PASS |
| Security | Destructive backup methods | PASS — POST + CSRF |
| Security | SQL backup direct access | PASS — HTTP 403 |
| Cleanup | Debug output scan | PASS — clean |
| Cleanup | Literal artifact scan | PASS — clean |
| Cleanup | Mojibake scan | PASS — clean |
| Architecture | Page/controller separation | PASS — target modules clean |
| Frontend | New module assets | PASS — HTTP 200 |
| Integrity | Duplicate script inclusion | PASS — removed |

## Refactored Administrative Modules

The following modules were included in the current regression pass:

- Academic Years
- Student Import
- Database Backup
- System Check
- Database Integrity

## Functional Boundary

This record covers static, security-boundary, and HTTP endpoint validation. Authenticated destructive database operations were not executed against production data. Future release candidates must add authenticated Playwright/module coverage when safe credentials and test data are available.

## Analytics Regression Record - 2026-08-12

| Area | Check | Result |
|---|---|---|
| Analytics | PHP syntax | PASS |
| Analytics | Controller syntax | PASS |
| Analytics | JavaScript syntax | PASS |
| Analytics | Debug-code scan | PASS - clean |
| Analytics | Literal artifact scan | PASS - clean |
| Analytics | Mojibake scan | PASS - clean after visual defect fix |
| Analytics | KPI CSS duplication | PASS - one definition |
| Analytics | Diff validation | PASS |
| Analytics | Visual text inspection | PASS - corrupted arrows/middle dots corrected |

### Analytics UI Scope

The regression target includes KPI metrics, action links, attendance labels, student-risk labels, section comparison labels, academic labels, and operational labels. Visual inspection is required because source-level syntax checks cannot detect all encoding defects.
