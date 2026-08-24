## Score Settings UI Removal � 2026-08-20

- Removed the user-facing Score Settings page and route.
- Removed the Score Settings shortcut from the Grades interface.
- Removed the manage_score_settings application permission.
- Retained the score_settings database table as an internal grading dependency so existing Grades and Student Profile calculations continue to work.

# TALA Engine Changelog

## RC1

### Added

- Smart Sync
- Student Enrollment
- Playwright Regression
- Modular JavaScript

### Changed

- Refactored Smart Sync frontend
- Engine frozen

### Fixed

- Attendance edit mode
- Sync summary
- SSE progress handling

## RC2.0.0 --- Schema Analysis & Planning Engine

**Overview**

RC2 introduced the core architecture of the TALA Engine, enabling schema
inspection, comparison, and merge planning between two databases. This
release established the analysis pipeline but did not execute any schema
changes.

### Added

#### Core Engine

-   Initial `TalaEngine` architecture
-   `EngineSession` for synchronization sessions
-   `DatabaseSnapshot` for source and destination metadata

#### Schema Analysis

-   `SchemaInspector`
    -   Detect missing tables
    -   Detect missing columns
    -   Detect modified columns
    -   Detect missing indexes
    -   Detect schema differences

#### Merge Planning

-   `SchemaMerger`
    -   Generate merge operations from schema differences
    -   Create logical execution plans
    -   Preserve operation ordering

#### Validation

-   `MergeValidator`
    -   Validate generated merge plans
    -   Detect invalid operations
    -   Prepare plans for execution

### Changed

-   Introduced modular architecture separating Inspection, Planning, and
    Validation.
-   Replaced direct schema comparison with structured difference
    objects.

### Architecture

``` text
Source Database
        │
        ▼
SchemaInspector
        │
        ▼
SchemaMerger
        │
        ▼
MergeValidator
```

### Status

-   ✅ Schema analysis
-   ✅ Merge planning
-   ✅ Validation
-   ❌ Execution (planned for RC2.9)

------------------------------------------------------------------------

## RC2.9.0 --- Executable Planning Framework

**Overview**

RC2.9 transformed merge plans into executable execution plans. While SQL
execution was intentionally deferred, this release introduced the
execution framework that became the foundation for RC3.

### Added

#### Execution Planning

-   `ExecutionPlanBuilder`
    -   Converts validated merge plans into execution plans
    -   Assigns operation IDs
    -   Orders execution by priority
    -   Produces deterministic execution sequences

#### Schema Executor

-   Initial `SchemaExecutor`
    -   Executes execution plans
    -   Tracks execution statistics
    -   Reports executed, skipped, and failed operations
    -   Placeholder execution implementation

#### Execution Reporting

-   Standardized execution reports containing:
    -   Executed operations
    -   Skipped operations
    -   Failed operations
    -   Execution timing
    -   Operation metadata

#### Supporting Components

-   `PlanValidator`
-   Execution result reporting
-   Operation priority handling
-   Execution session tracking

### Changed

-   Merge plans became execution plans.
-   Introduced operation IDs (`OP-0001`, `OP-0002`, etc.).
-   Added execution ordering logic.
-   Improved execution reporting structure.

### Architecture

``` text
SchemaInspector
        │
        ▼
SchemaMerger
        │
        ▼
MergeValidator
        │
        ▼
ExecutionPlanBuilder
        │
        ▼
SchemaExecutor
```

### Status

-   ✅ Executable plans
-   ✅ Ordered operations
-   ✅ Execution reports
-   ✅ Execution framework
-   ❌ SQL execution (implemented in RC3)

------------------------------------------------------------------------

## Release Timeline

``` text
RC1.0.0  → Engine Foundation
RC2.0.0  → Schema Analysis & Planning
RC2.9.0  → Executable Planning Framework
RC3.0.0  → First Executable Schema Synchronization
```


## RC3.0.0 - First Executable Schema Synchronization

### Added
- Handler-based execution architecture
- CreateTableHandler implementation
- OperationHandlerInterface
- Executable schema plans

### Changed
- Refactored SchemaExecutor into a dispatcher
- Moved CREATE TABLE execution into dedicated handlers
- Standardized execution pipeline

### Verified
- End-to-end schema synchronization
- Successful CREATE TABLE execution
- Proper execution reporting

---

## RC3.5.5 — Release Manager Verified

### Release Infrastructure

- GitHub Releases became the production update source.
- Release manifest parsing and validation were implemented.
- Version comparison and stable-channel handling were added.
- Release packages are downloaded and validated as ZIP archives.
- Automatic installation backups are created before replacement.
- Package extraction, cleanup, and installed-version verification were implemented.
- Runtime configuration and persistent application paths are preserved.

### UI

- Global application footer added.
- Application version displayed dynamically.
- TALA Engine version displayed dynamically.
- KaijuSoft branding consolidated.


## v1.0.3 / RC3.5 Stability Work

### TALA Engine and Synchronization

- Integrated schema execution into the current engine architecture.
- Standardized execution results and API reporting.
- Improved synchronization API handling.
- Improved Smart Synchronization dashboard presentation and state handling.
- Preserved the Engine → API → Frontend separation.

### Database Integrity

- Added `IntegrityChecker`.
- Added `ReferenceInspector`.
- Added `DuplicateDetector`.
- Added structured `IntegrityReport` output.
- Added a database integrity dashboard.
- Added deterministic backup source selection.


## RC3.5.21

### User Lifecycle and Integrity Center

- Added account archiving and user lifecycle handling.
- Expanded instructor account administration.
- Added impact analysis around lifecycle operations.
- Improved integrity-center presentation and workflows.
- Improved instructor, section, enrollment, attendance, and teaching-load interactions.

## RC3.5.22

### Final User Lifecycle and PRG Work

- Finalized user lifecycle flow.
- Finalized impact-analysis presentation and integration.
- Improved Post/Redirect/Get behavior for administrative workflows.
- Refined database integrity page behavior.
- Refined navigation and management-page state handling.


## RC3.5.23 — Database Maintenance Safety

- Added controlled duplicate-student merge workflow with preflight impact analysis.
- Added required merge and orphan-deletion reasons.
- Added automatic local backups before destructive maintenance.
- Added transactional reference reassignment with rollback on failure.
- Added protection against running destructive maintenance across non-transactional tables.
- Added maintenance audit logging.
- Hardened Database Integrity AJAX JSON handling.
- Aligned runtime `version.json` with v1.0.3.

Validation: PHP and JavaScript syntax checks passed. Destructive maintenance has not been executed against production data.


---

## Administrative Refactor and Security Hardening — 2026-08-11

### Added

- Dedicated controllers for Academic Years, Score Settings, Student Import, Database Backup, System Check, and Database Integrity.
- Dedicated Academic Years and Score Settings frontend modules.
- CSRF and permission enforcement at administrative controller boundaries.
- Protected backup storage from direct SQL-file access.

### Changed

- Backup deletion and restoration are now protected POST workflows.
- Administrative validation and error handling were moved out of presentation pages.
- Database Integrity no longer loads its frontend script twice.
- Synchronization debug actions and exposed diagnostic output were removed.

### Fixed

- Removed stray `console.log()` debugging from teaching-load JavaScript.
- Removed literal newline artifacts from the students page.
- Repaired corrupted text in the restore workflow.
- Hardened student import and backup workflows against unauthenticated or forged requests.

### Verification

- PHP syntax: 56 files checked, 0 failures.
- JavaScript syntax: 13 files checked, 0 failures.
- Application debug/artifact/mojibake scans: clean.
- Protected endpoint checks: passed.
- Direct SQL backup access: HTTP 403.

No destructive production database operation was performed during this validation pass.
---

## Analytics KPI and Encoding Cleanup - 2026-08-12

### Added

- KPI-oriented Analytics presentation for the executive summary.
- Focused analytical views for attendance, academics, students, and operations.

### Changed

- Analytics visual hierarchy now distinguishes KPIs, trends, composition, comparisons, and exact-value lists.
- Analytics remains separated into presentation, controller, JavaScript, and CSS responsibilities.

### Fixed

- Corrected UTF-8 mojibake in Analytics action links and labels, including corrupted arrow and middle-dot characters.
- Verified absence of Analytics debug and artifact code.

### Verification

- Analytics PHP/controller syntax passed.
- Analytics JavaScript syntax passed.
- Artifact/debug scan clean.
- Mojibake scan clean after the visual defect was corrected.
- KPI CSS duplication check passed.
- git diff --check passed for the Analytics changes.
