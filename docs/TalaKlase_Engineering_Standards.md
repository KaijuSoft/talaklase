# TalaKlase Engineering Standards

Version: 1.0 (Living Document)

## Purpose

This document defines the engineering standards for TalaKlase and TALA
Engine. Every contributor (human or AI) must follow these rules.

## Project Philosophy

-   TalaKlase is a college-oriented Student Information System.
-   Reliability is more important than adding features.
-   Prefer incremental improvements over rewrites.
-   Preserve backward compatibility whenever practical.
-   Attendance and grading should evolve toward an enrollment-driven
    architecture based on teaching assignments.

## Engineering Principles

Every change must improve at least one of: 1. Safety 2. Simplicity 3.
Maintainability

If it improves none of these, reconsider the change.

## Rules of Engagement

1.  Read the entire file before making changes.
2.  Understand the execution flow.
3.  Identify dependencies.
4.  Preserve working functionality.
5.  Make the smallest safe change.
6.  Test before shipping.
7.  Document assumptions.
8.  Ask instead of guessing.

## Audit Checklist

### Architecture

-   Review complete execution flow.
-   Avoid unnecessary rewrites.
-   Remove dead code only after verification.

### Database

-   Verify schema consistency.
-   Verify business keys.
-   Verify foreign keys.
-   Use transactions.

### Security

-   Prepared statements.
-   Permission checks.
-   Input validation.
-   No secrets in source control.

### TALA Engine

-   Health Check passes.
-   Synchronization is transactional.
-   Progress reporting works.
-   Conflict detection works.
-   Session summary generated.

### Release Criteria

-   No critical defects.
-   Regression tests pass.
-   Smart Merge validated.
-   Attendance workflow validated.
-   Import/export validated.

## AI Review Workflow

1.  Explain the issue.
2.  Locate the exact file and function.
3.  Propose the smallest safe fix.
4.  Verify no regressions.
5.  Summarize the change.

## Vision

TALA Engine is the synchronization backbone of TalaKlase. Future
capabilities include Health Check, Dry Run, Conflict Policies, Audit
History, and Recovery.


---

## Administrative Refactor Standards — 2026-08-11

Administrative presentation pages must remain thin. Request processing, authorization, validation, database operations, and redirect behavior belong in dedicated controller files.

### Required Security Boundary

Every mutating administrative controller must evaluate:

1. Authentication.
2. Appropriate permission.
3. HTTP method where the operation is mutating or destructive.
4. CSRF token validity.
5. Server-side input validation.

Destructive operations must never rely on GET links alone.

### Runtime Artifacts

Database backups are sensitive runtime artifacts. SQL backup files must not be directly served by the web server. Runtime storage controls must be tested whenever backup functionality changes.

### Debug Hygiene

Before release, scan application code for:

- `console.log`
- `debugger`
- `var_dump`
- diagnostic `print_r`
- temporary debug endpoints
- literal newline artifacts
- mojibake/corrupted text

Third-party dependencies may contain their own diagnostic strings; release scans should distinguish dependency code from application code.

### Verification Record

The 2026-08-11 hardening pass achieved zero PHP syntax failures across 56 checked files and zero JavaScript syntax failures across 13 checked files. Application debug, artifact, and mojibake scans were clean.

The pass also verified protected-page access boundaries and direct SQL backup denial.
---

## Analytics UI Standards - 2026-08-12

Analytics is an information hierarchy, not a collection of decorative charts.

- Use KPI components for headline institutional measures.
- Use line charts for trends, donuts for composition, bars for comparisons, and tables/lists for exact records.
- Avoid inventing percentage targets or trend deltas when reliable comparison data does not exist.
- Keep Analytics read-only and controller-backed unless a future workflow explicitly requires mutations.
- Treat UTF-8 correctness as part of UI quality. Visible symbols must be encoded consistently and checked after file transformations.
- Visual regression review is required for Analytics changes because syntax checks cannot detect layout or encoding artifacts.
