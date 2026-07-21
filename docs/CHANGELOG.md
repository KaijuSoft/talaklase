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