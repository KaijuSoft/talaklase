# Security Policy

If you discover a security issue in TalaKlase, please report it privately to the maintainers before opening a public issue.

Please include:

- A short description of the issue
- The affected file or workflow
- Steps to reproduce
- Any relevant screenshots or logs

Do not share secrets, credentials, or live database details in public reports.


## Current Security Hardening Baseline — 2026-08-11

The latest administrative refactor established the following baseline:

- Protected administrative mutations with server-side permission checks and CSRF validation.
- Destructive backup deletion and restoration require POST requests and valid CSRF tokens.
- Backup creation requires CSRF validation and an administrative permission.
- Student import requires authenticated administrative permission, POST, CSRF, and server-side file/input validation.
- Database Integrity actions remain permission-gated and CSRF-protected.
- System diagnostics require the appropriate administrative permission.
- SQL backup files under `storage/backups` are denied direct Apache access.
- Legacy synchronization debug output and debug actions were removed.
- Raw database exception details are not intentionally exposed by the refactored administrative controllers.
- User-visible output is escaped where filenames or database-derived values are rendered.

## Verification

The development installation was checked for PHP syntax, JavaScript syntax, debug statements, literal artifacts, mojibake, authentication boundaries, and direct backup-file exposure.

Results for the latest pass:

- PHP syntax: 0 failures across 56 checked files.
- JavaScript syntax: 0 failures across 13 checked files.
- Application debug scan: clean.
- Application artifact scan: clean.
- Application mojibake scan: clean.
- Unauthenticated protected-page requests: rejected/redirected.
- Direct SQL backup request: HTTP 403.

Functional destructive operations remain subject to the project's explicit authorization and test-database safety rules.

## Analytics Integrity and Output Hygiene - 2026-08-12

Analytics is read-only from the dashboard perspective and remains controller-backed. Database-derived values rendered in Analytics must continue to be escaped and must not expose raw database exceptions.

The Analytics QA pass also established that output encoding is part of UI integrity: corrupted UTF-8 can create misleading or visibly broken administrative controls even when PHP and JavaScript syntax are valid. Analytics output must therefore be checked for mojibake during UI changes.
