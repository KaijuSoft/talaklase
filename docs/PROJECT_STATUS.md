# TalaKlase Project Status

**Status:** Active Development
**Current Branch:** `development`
**Current Version:** 1.0.3
**Current RC Line:** RC3.5.22
**TALA Engine:** RC3 architecture / Engine 2.0 manifest line
**Owner:** KaijuSoft
**Lead Developer:** Julius Frederick C. Vendivil

---

## Purpose

This document records the current state of TalaKlase after the RC3.5 development work and consolidates the major architectural and product changes implemented in the repository.

It is intended to prevent older roadmap and release documents from being mistaken for the current implementation state.

---

## Current Product State

TalaKlase has evolved from an attendance application into an offline-first, college-oriented Student Information System.

Current academic areas include:

- Student records
- Departments, courses, subjects, and sections
- Teaching loads / assignments
- Student enrollment
- Attendance and attendance reporting
- Grading
- Instructor accounts and permissions
- Database synchronization
- Database integrity inspection
- Backup and recovery workflows
- Production release management

---

## Recent RC3.5 Work

### v1.0.3 Stability Release

The v1.0.3 release consolidated the first generation of the Release Manager, integrity tooling, synchronization improvements, and stability work.

Implemented areas include:

- TALA Engine execution integration
- Synchronization API improvements
- Database integrity inspection
- Duplicate detection
- Reference inspection
- Integrity reporting
- Improved student visibility
- Instructor access controls
- Release infrastructure

---

## Instructor and User Lifecycle

Instructor accounts now have an explicit administrative workflow.

Implemented behavior includes:

- Instructor login account creation
- Linked instructor and user records
- Instructor-admin role
- Permission mapping
- Username or email login
- Session email information
- Section ownership through `section.inst_id`
- Instructor-scoped section visibility
- Instructor-scoped student visibility
- Instructor-scoped attendance visibility
- User account archiving / lifecycle handling
- Impact-aware account operations
- Post/Redirect/Get workflow improvements

Instructor-admin users receive admin-like application permissions but do not manage instructor accounts.

---

## Data Integrity Center

The database integrity area has been expanded into a dedicated integrity workflow.

Implemented components include:

- `IntegrityChecker`
- `ReferenceInspector`
- `DuplicateDetector`
- `IntegrityReport`
- Database integrity dashboard
- Deterministic backup source selection
- Reference and relationship inspection
- Duplicate detection
- Impact analysis around account lifecycle operations

Integrity operations are intended to identify problems before destructive or corrective actions are taken.

---

## Smart Synchronization

The synchronization dashboard has received a substantial UI and workflow update.

The existing architectural direction remains:

```text
Frontend
    ↓
sync_api.php
    ↓
TALA Engine
    ↓
Local / Remote Database
```

Smart Sync remains the controlled Online-to-Local synchronization workflow. Local-to-Online recovery remains a separate administrative Restore Online Database workflow.

The synchronization UI now includes improved progress, state handling, reporting, and dashboard presentation.

---

## Release Manager

Production updates use GitHub Releases rather than Git branch synchronization.

The verified release workflow is:

```text
GitHub Release
    ↓
Manifest Detection
    ↓
Version Comparison
    ↓
Update Notification
    ↓
Package Download
    ↓
Backup
    ↓
ZIP Validation
    ↓
Installation
    ↓
Cleanup
    ↓
Version Verification
```

The release manager preserves runtime configuration and other installation-specific paths during updates.

---

## TALA Engine

The engine follows the Analyze → Plan → Validate → Execute philosophy.

The schema execution architecture now uses operation handlers rather than putting operation-specific SQL behavior directly into `SchemaExecutor`.

Core schema components include:

- `DatabaseSnapshot`
- `SchemaInspector`
- `SchemaMerger`
- `MergeValidator`
- `ExecutionPlanBuilder`
- `SchemaExecutor`
- Operation handlers

The broader architecture also defines the data pipeline around snapshots, inspection, conflict resolution, merging, execution, and verification.

---

## Current Architecture Rules

- Read the entire file before changing it.
- Preserve backward compatibility where practical.
- Prefer the smallest safe change.
- Keep presentation, API, business logic, and database responsibilities separated.
- Never bypass the synchronization API for synchronization workflows.
- Use business keys for synchronization decisions.
- Inspect and validate before execution.
- Do not claim a feature works without executing its relevant tests.
- Never execute destructive database operations without explicit authorization.

---

## Documentation State

Some older documents were written during RC1/RC2 and are historical references rather than current status documents.

In particular, older roadmap versions should not be interpreted as saying that RC1 is still the current release.

This file is the current project-state reference for the development branch.

---

## Next Development Direction

The repository is moving toward the RC4 direction, with emphasis on:

- User experience
- Interface polish
- Workflow improvements
- Performance
- Responsive design
- Teacher productivity
- Student experience
- Continued integrity and synchronization reliability

TALA Engine should remain stable except for bug fixes and deliberate architectural improvements.
