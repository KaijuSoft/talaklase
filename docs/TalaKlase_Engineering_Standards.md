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
